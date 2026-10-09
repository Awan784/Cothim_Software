<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\InventoryService;
use App\Services\SettingsService;
use App\Support\AmountInWords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): View
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'vendor', 'items'])
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View|RedirectResponse
    {
        $suppliers = Supplier::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        if ($suppliers->isEmpty() && $vendors->isEmpty()) {
            return redirect()
                ->route('vendors.create')
                ->with('error', 'Add a supplier or vendor first, then create a purchase.');
        }

        return view('purchase-orders.create', [
            'suppliers' => $suppliers,
            'vendors' => $vendors,
            'stockItems' => $this->inventoryItems(true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPurchaseOrder($request);

        DB::transaction(function () use ($data) {
            $total = $this->calculateItemsTotal($data['items']);

            $po = PurchaseOrder::create([
                'po_no' => PurchaseOrder::nextNumber(),
                'party_type' => $data['party_type'],
                'supplier_id' => $data['supplier_id'],
                'vendor_id' => $data['vendor_id'],
                'po_date' => $data['po_date'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $total,
            ]);

            $this->persistItems($po, $data['items'], $data['po_date']);
            $this->adjustPartyBalance($data['party_type'], $data['party_type'] === 'vendor' ? $data['vendor_id'] : $data['supplier_id'], $total);
        });

        $message = ($data['party_type'] ?? '') === 'vendor'
            ? 'Purchase saved on the vendor ledger. Inventory was not changed.'
            : 'Purchase saved. Inventory quantity increased.';

        return redirect()->route('purchase-orders.index')->with('success', $message);
    }

    public function print(PurchaseOrder $purchaseOrder, SettingsService $settings): View
    {
        $purchaseOrder->load(['supplier', 'vendor', 'items']);

        return view('purchase-orders.print', [
            'purchaseOrder' => $purchaseOrder,
            'settings' => $settings,
            'amountInWords' => AmountInWords::rupees((float) $purchaseOrder->total_amount),
            'printedAt' => now(),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return redirect()->route('purchase-orders.edit', $purchaseOrder);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load('items.stockItem', 'supplier', 'vendor');

        return view('purchase-orders.edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'stockItems' => $this->inventoryItems(false),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $this->validatedPurchaseOrder($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $oldType = $purchaseOrder->party_type ?: 'supplier';
            $oldPartyId = $oldType === 'vendor' ? $purchaseOrder->vendor_id : $purchaseOrder->supplier_id;
            $this->adjustPartyBalance($oldType, $oldPartyId, -((float) $purchaseOrder->total_amount));

            $this->inventory->revertPurchaseOrder($purchaseOrder);
            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();

            $newTotal = $this->calculateItemsTotal($data['items']);

            $purchaseOrder->update([
                'party_type' => $data['party_type'],
                'supplier_id' => $data['supplier_id'],
                'vendor_id' => $data['vendor_id'],
                'po_date' => $data['po_date'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $newTotal,
            ]);

            $this->persistItems($purchaseOrder, $data['items'], $data['po_date']);
            $this->adjustPartyBalance($data['party_type'], $data['party_type'] === 'vendor' ? $data['vendor_id'] : $data['supplier_id'], $newTotal);
        });

        $message = $data['party_type'] === 'vendor'
            ? 'Purchase updated on the vendor ledger.'
            : 'Purchase updated. Inventory quantity recalculated.';

        return redirect()->route('purchase-orders.index')->with('success', $message);
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($purchaseOrder) {
            $type = $purchaseOrder->party_type ?: 'supplier';
            $partyId = $type === 'vendor' ? $purchaseOrder->vendor_id : $purchaseOrder->supplier_id;
            $this->adjustPartyBalance($type, $partyId, -((float) $purchaseOrder->total_amount));

            $this->inventory->revertPurchaseOrder($purchaseOrder);
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
        });

        return back()->with('success', 'Purchase deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPurchaseOrder(Request $request): array
    {
        $request->merge([
            'items' => $this->presentPurchaseRows($request->input('items')),
        ]);

        $data = $request->validate([
            'party_type' => ['required', 'in:supplier,vendor'],
            'supplier_id' => ['nullable', 'required_if:party_type,supplier', 'exists:suppliers,id'],
            'vendor_id' => ['nullable', 'required_if:party_type,vendor', 'exists:vendors,id'],
            'po_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['nullable', 'exists:stock_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.batch_no' => ['nullable', 'string', 'max:100'],
            'items.*.manufactured_at' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.note' => ['nullable', 'string'],
        ], [
            'supplier_id.required_if' => 'Select a supplier.',
            'vendor_id.required_if' => 'Select a vendor.',
            'items.required' => 'Add at least one line.',
            'items.min' => 'Add at least one line.',
        ]);

        $isVendor = $data['party_type'] === 'vendor';
        $data['supplier_id'] = $isVendor ? null : (int) $data['supplier_id'];
        $data['vendor_id'] = $isVendor ? (int) $data['vendor_id'] : null;

        $normalized = [];

        foreach ($data['items'] as $index => $row) {
            if ($isVendor) {
                $name = trim((string) ($row['item_name'] ?? ''));
                if ($name === '') {
                    throw ValidationException::withMessages([
                        'items.'.$index.'.item_name' => 'Enter the item name on row '.($index + 1).'.',
                    ]);
                }

                $normalized[] = [
                    'stock_item_id' => null,
                    'item_name' => $name,
                    'unit' => trim((string) ($row['unit'] ?? '')) ?: null,
                    'unit_price' => (float) $row['unit_price'],
                    'quantity' => (float) $row['quantity'],
                    'batch_no' => null,
                    'manufactured_at' => null,
                    'expiry_date' => null,
                    'note' => $row['note'] ?? null,
                ];

                continue;
            }

            $stockId = (int) ($row['stock_item_id'] ?? 0);
            $item = $stockId > 0 ? StockItem::whereKey($stockId)->first() : null;

            if (! $item) {
                throw ValidationException::withMessages([
                    'items.'.$index.'.stock_item_id' => 'Select an inventory item on row '.($index + 1).'.',
                ]);
            }

            $normalized[] = [
                'stock_item_id' => $stockId,
                'item_name' => $item->purchaseLabel(),
                'unit' => $item->unit,
                'unit_price' => (float) $row['unit_price'],
                'quantity' => (float) $row['quantity'],
                'batch_no' => trim((string) ($row['batch_no'] ?? '')) ?: null,
                'manufactured_at' => $row['manufactured_at'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
                'note' => $row['note'] ?? null,
            ];
        }

        $data['items'] = $normalized;

        return $data;
    }

    /**
     * @return \Illuminate\Support\Collection<int, StockItem>
     */
    private function inventoryItems(bool $activeOnly)
    {
        $query = StockItem::query()
            ->with([
                'stockCategory',
                'lots' => fn ($lots) => $lots->orderBy('received_at')->orderBy('id'),
            ])
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Drop unused extra rows so a blank first line does not fail validation.
     *
     * @return list<array<string, mixed>>
     */
    private function presentPurchaseRows(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $rows = [];

        foreach ($items as $row) {
            if (! is_array($row) || ! $this->purchaseRowIsPresent($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function purchaseRowIsPresent(array $row): bool
    {
        if (filled($row['stock_item_id'] ?? null)) {
            return true;
        }

        if (trim((string) ($row['item_name'] ?? '')) !== '') {
            return true;
        }

        if (trim((string) ($row['note'] ?? '')) !== '') {
            return true;
        }

        $price = $row['unit_price'] ?? null;
        if ($price !== null && $price !== '' && is_numeric($price) && (float) $price != 0.0) {
            return true;
        }

        $qty = $row['quantity'] ?? null;
        if ($qty !== null && $qty !== '' && is_numeric($qty) && (float) $qty > 0 && (float) $qty != 1.0) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateItemsTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $row) {
            $total += $row['unit_price'] * $row['quantity'];
        }

        return $total;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function persistItems(PurchaseOrder $po, array $items, string $poDate): void
    {
        foreach ($items as $row) {
            $unitPrice = (float) $row['unit_price'];
            $qty = (float) $row['quantity'];

            $lot = null;
            if (! $po->isVendorPurchase() && ! empty($row['stock_item_id'])) {
                $lot = $this->inventory->receive((int) $row['stock_item_id'], $qty, [
                    'unit_cost' => $unitPrice,
                    'moved_at' => $poDate,
                    'reference' => $po->po_no,
                    'notes' => $row['item_name'] ?? 'Purchase',
                    'source_type' => 'purchase_order',
                    'source_id' => $po->id,
                    'batch_no' => $row['batch_no'] ?? null,
                    'manufactured_at' => $row['manufactured_at'] ?? null,
                    'expiry_date' => $row['expiry_date'] ?? null,
                ]);
            }

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'stock_item_id' => $row['stock_item_id'],
                'stock_item_lot_id' => $lot?->id,
                'item_name' => $row['item_name'],
                'unit' => $row['unit'] ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $unitPrice * $qty,
                'batch_no' => $row['batch_no'] ?? null,
                'manufactured_at' => $row['manufactured_at'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
                'note' => $row['note'] ?? null,
            ]);
        }
    }

    private function adjustPartyBalance(string $partyType, ?int $partyId, float $delta): void
    {
        if (! $partyId || $delta == 0.0) {
            return;
        }

        $party = $partyType === 'vendor'
            ? Vendor::whereKey($partyId)->lockForUpdate()->first()
            : Supplier::whereKey($partyId)->lockForUpdate()->first();

        if (! $party) {
            return;
        }

        if ($delta > 0) {
            $party->increment('current_balance', $delta);
        } else {
            $party->decrement('current_balance', abs($delta));
        }
    }
}

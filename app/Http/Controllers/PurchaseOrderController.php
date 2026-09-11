<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockItem;
use App\Models\Supplier;
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
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items'])
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View|RedirectResponse
    {
        $suppliers = Supplier::orderBy('name')->get();
        if ($suppliers->isEmpty()) {
            return redirect()
                ->route('suppliers.create')
                ->with('error', 'Add a supplier first, then create a purchase.');
        }

        $stockItems = $this->inventoryItems(true);
        if ($stockItems->isEmpty()) {
            return redirect()
                ->route('stock-items.create')
                ->with('error', 'Add inventory items first, then create a purchase.');
        }

        return view('purchase-orders.create', [
            'suppliers' => $suppliers,
            'stockItems' => $stockItems,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPurchaseOrder($request);

        DB::transaction(function () use ($data) {
            $supplier = Supplier::whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
            $total = $this->calculateItemsTotal($data['items']);

            $po = PurchaseOrder::create([
                'po_no' => PurchaseOrder::nextNumber(),
                'supplier_id' => $supplier->id,
                'po_date' => $data['po_date'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $total,
            ]);

            $this->persistItems($po, $data['items'], $data['po_date']);
            $supplier->increment('current_balance', $total);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase saved. Inventory quantity increased.');
    }

    public function print(PurchaseOrder $purchaseOrder, SettingsService $settings): View
    {
        $purchaseOrder->load(['supplier', 'items']);

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
        $purchaseOrder->load('items.stockItem', 'supplier');

        return view('purchase-orders.edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('name')->get(),
            'stockItems' => $this->inventoryItems(false),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $this->validatedPurchaseOrder($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $oldSupplier = Supplier::whereKey($purchaseOrder->supplier_id)->lockForUpdate()->firstOrFail();
            $oldTotal = (float) $purchaseOrder->total_amount;
            $oldSupplier->decrement('current_balance', $oldTotal);

            $this->inventory->revertPurchaseOrder($purchaseOrder);
            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();

            $newTotal = $this->calculateItemsTotal($data['items']);

            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'po_date' => $data['po_date'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $newTotal,
            ]);

            $this->persistItems($purchaseOrder, $data['items'], $data['po_date']);

            $newSupplier = Supplier::whereKey($purchaseOrder->supplier_id)->lockForUpdate()->firstOrFail();
            $newSupplier->increment('current_balance', $newTotal);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase updated. Inventory quantity recalculated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($purchaseOrder) {
            $supplier = Supplier::whereKey($purchaseOrder->supplier_id)->lockForUpdate()->firstOrFail();
            $supplier->decrement('current_balance', (float) $purchaseOrder->total_amount);

            $this->inventory->revertPurchaseOrder($purchaseOrder);
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
        });

        return back()->with('success', 'Purchase deleted. Inventory quantity reversed.');
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
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string'],
        ], [
            'items.required' => 'Add at least one inventory item.',
            'items.min' => 'Add at least one inventory item.',
            'items.*.stock_item_id.required' => 'Select an inventory item.',
            'items.*.stock_item_id.exists' => 'Select a valid inventory item.',
        ]);

        $normalized = [];

        foreach ($data['items'] as $index => $row) {
            $stockId = (int) $row['stock_item_id'];
            $item = StockItem::whereKey($stockId)->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'items' => 'Select an inventory item on row '.($index + 1).'.',
                ]);
            }

            $normalized[] = [
                'stock_item_id' => $stockId,
                'item_name' => $item->purchaseLabel(),
                'unit' => $item->unit,
                'unit_price' => (float) $row['unit_price'],
                'quantity' => (float) $row['quantity'],
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
            ->with('stockCategory')
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

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'stock_item_id' => $row['stock_item_id'],
                'item_name' => $row['item_name'],
                'unit' => $row['unit'] ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $unitPrice * $qty,
                'note' => $row['note'] ?? null,
            ]);

            $this->inventory->receive((int) $row['stock_item_id'], $qty, [
                'unit_cost' => $unitPrice,
                'moved_at' => $poDate,
                'reference' => $po->po_no,
                'notes' => $row['item_name'] ?? 'Purchase',
                'source_type' => 'purchase_order',
                'source_id' => $po->id,
            ]);
        }
    }
}

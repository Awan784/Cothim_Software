<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
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
use InvalidArgumentException;

class PurchaseReturnController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): View
    {
        $purchaseReturns = PurchaseReturn::with(['supplier', 'items'])
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('purchase-returns.index', compact('purchaseReturns'));
    }

    public function create(): View|RedirectResponse
    {
        $suppliers = Supplier::orderBy('name')->get();
        if ($suppliers->isEmpty()) {
            return redirect()
                ->route('suppliers.create')
                ->with('error', 'Add a supplier first, then create a purchase return.');
        }

        $stockItems = $this->inventoryItems(true);
        if ($stockItems->isEmpty()) {
            return redirect()
                ->route('stock-items.create')
                ->with('error', 'Add inventory items first, then create a purchase return.');
        }

        return view('purchase-returns.create', [
            'suppliers' => $suppliers,
            'stockItems' => $stockItems,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedReturn($request);

        try {
            DB::transaction(function () use ($data) {
                $supplier = Supplier::whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
                $total = $this->calculateItemsTotal($data['items']);

                $return = PurchaseReturn::create([
                    'return_no' => PurchaseReturn::nextNumber(),
                    'supplier_id' => $supplier->id,
                    'return_date' => $data['return_date'],
                    'notes' => $data['notes'] ?? null,
                    'total_amount' => $total,
                ]);

                $this->persistItems($return, $data['items'], $data['return_date']);
                $supplier->decrement('current_balance', $total);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchase-returns.index')->with('success', 'Purchase return saved. Inventory quantity decreased.');
    }

    public function print(PurchaseReturn $purchaseReturn, SettingsService $settings): View
    {
        $purchaseReturn->load(['supplier', 'items']);

        return view('purchase-returns.print', [
            'purchaseReturn' => $purchaseReturn,
            'settings' => $settings,
            'amountInWords' => AmountInWords::rupees((float) $purchaseReturn->total_amount),
            'printedAt' => now(),
        ]);
    }

    public function show(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        return redirect()->route('purchase-returns.edit', $purchaseReturn);
    }

    public function edit(PurchaseReturn $purchaseReturn): View
    {
        $purchaseReturn->load('items.stockItem', 'supplier');

        return view('purchase-returns.edit', [
            'purchaseReturn' => $purchaseReturn,
            'suppliers' => Supplier::orderBy('name')->get(),
            'stockItems' => $this->inventoryItems(false),
        ]);
    }

    public function update(Request $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $data = $this->validatedReturn($request);

        try {
            DB::transaction(function () use ($purchaseReturn, $data) {
                $oldSupplier = Supplier::whereKey($purchaseReturn->supplier_id)->lockForUpdate()->firstOrFail();
                $oldSupplier->increment('current_balance', (float) $purchaseReturn->total_amount);

                $this->inventory->revertPurchaseReturn($purchaseReturn);
                PurchaseReturnItem::where('purchase_return_id', $purchaseReturn->id)->delete();

                $newTotal = $this->calculateItemsTotal($data['items']);

                $purchaseReturn->update([
                    'supplier_id' => $data['supplier_id'],
                    'return_date' => $data['return_date'],
                    'notes' => $data['notes'] ?? null,
                    'total_amount' => $newTotal,
                ]);

                $this->persistItems($purchaseReturn, $data['items'], $data['return_date']);

                $newSupplier = Supplier::whereKey($purchaseReturn->supplier_id)->lockForUpdate()->firstOrFail();
                $newSupplier->decrement('current_balance', $newTotal);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchase-returns.index')->with('success', 'Purchase return updated. Inventory quantity recalculated.');
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        DB::transaction(function () use ($purchaseReturn) {
            $supplier = Supplier::whereKey($purchaseReturn->supplier_id)->lockForUpdate()->firstOrFail();
            $supplier->increment('current_balance', (float) $purchaseReturn->total_amount);

            $this->inventory->revertPurchaseReturn($purchaseReturn);
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });

        return back()->with('success', 'Purchase return deleted. Inventory quantity restored.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedReturn(Request $request): array
    {
        $request->merge([
            'items' => $this->presentRows($request->input('items')),
        ]);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['required', 'exists:stock_items,id'],
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
     * @return list<array<string, mixed>>
     */
    private function presentRows(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $rows = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (filled($row['stock_item_id'] ?? null)) {
                $rows[] = $row;
                continue;
            }
            $price = $row['unit_price'] ?? null;
            if ($price !== null && $price !== '' && is_numeric($price) && (float) $price != 0.0) {
                $rows[] = $row;
            }
        }

        return $rows;
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
    private function persistItems(PurchaseReturn $return, array $items, string $returnDate): void
    {
        foreach ($items as $row) {
            $unitPrice = (float) $row['unit_price'];
            $qty = (float) $row['quantity'];

            PurchaseReturnItem::create([
                'purchase_return_id' => $return->id,
                'stock_item_id' => $row['stock_item_id'],
                'item_name' => $row['item_name'],
                'unit' => $row['unit'] ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $unitPrice * $qty,
                'note' => $row['note'] ?? null,
            ]);

            $this->inventory->issue((int) $row['stock_item_id'], $qty, [
                'unit_cost' => $unitPrice,
                'moved_at' => $returnDate,
                'reference' => $return->return_no,
                'notes' => $row['item_name'] ?? 'Purchase return',
                'source_type' => 'purchase_return',
                'source_id' => $return->id,
            ]);
        }
    }
}

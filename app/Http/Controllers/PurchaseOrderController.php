<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $stockItems = StockItem::orderBy('name')->get();

        return view('purchase-orders.create', compact('suppliers', 'stockItems'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPurchaseOrder($request);
        $poNo = 'PO-'.now()->format('Ymd-His').'-'.random_int(100, 999);

        DB::transaction(function () use ($data, $poNo) {
            $supplier = Supplier::whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
            $total = $this->calculateItemsTotal($data['items']);

            $po = PurchaseOrder::create([
                'po_no' => $poNo,
                'supplier_id' => $supplier->id,
                'po_date' => $data['po_date'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $total,
            ]);

            $this->persistItems($po, $data['items'], $data['po_date']);
            $supplier->increment('current_balance', $total);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return redirect()->route('purchase-orders.edit', $purchaseOrder);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.stockItem', 'supplier');
        $suppliers = Supplier::orderBy('name')->get();
        $stockItems = StockItem::orderBy('name')->get();

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'stockItems'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $this->validatedPurchaseOrder($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $oldSupplier = Supplier::whereKey($purchaseOrder->supplier_id)->lockForUpdate()->firstOrFail();
            $oldTotal = (float) $purchaseOrder->total_amount;

            $oldSupplier->decrement('current_balance', $oldTotal);

            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();
            StockMovement::where('source_type', 'purchase_order')->where('source_id', $purchaseOrder->id)->delete();

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

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        DB::transaction(function () use ($purchaseOrder) {
            $supplier = Supplier::whereKey($purchaseOrder->supplier_id)->lockForUpdate()->firstOrFail();
            $total = (float) $purchaseOrder->total_amount;

            $supplier->decrement('current_balance', $total);

            StockMovement::where('source_type', 'purchase_order')->where('source_id', $purchaseOrder->id)->delete();
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();
        });

        return back()->with('success', 'Purchase order deleted.');
    }

    private function validatedPurchaseOrder(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_item_id' => ['nullable', 'exists:stock_items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $normalized = [];

        foreach ($data['items'] as $index => $row) {
            $stockId = ! empty($row['stock_item_id']) ? (int) $row['stock_item_id'] : null;
            $itemName = trim((string) ($row['item_name'] ?? ''));

            if (! $stockId && $itemName === '') {
                throw ValidationException::withMessages([
                    'items' => 'Each line needs a stock item or a manual item name (row '.($index + 1).').',
                ]);
            }

            if ($stockId && $itemName === '') {
                $itemName = StockItem::whereKey($stockId)->value('name') ?? '';
            }

            $normalized[] = [
                'stock_item_id' => $stockId,
                'item_name' => $itemName,
                'unit' => $row['unit'] ?? null,
                'unit_price' => (float) $row['unit_price'],
                'quantity' => (float) $row['quantity'],
                'note' => $row['note'] ?? null,
            ];
        }

        $data['items'] = $normalized;

        return $data;
    }

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
            $lineTotal = $unitPrice * $qty;

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'stock_item_id' => $row['stock_item_id'],
                'item_name' => $row['item_name'],
                'unit' => $row['unit'] ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'note' => $row['note'] ?? null,
            ]);

            if (! empty($row['stock_item_id'])) {
                StockMovement::create([
                    'stock_item_id' => $row['stock_item_id'],
                    'type' => 'in',
                    'quantity' => $qty,
                    'unit_cost' => $unitPrice,
                    'moved_at' => $poDate,
                    'reference' => $po->po_no,
                    'notes' => $row['item_name'] ?? 'Purchase order',
                    'source_type' => 'purchase_order',
                    'source_id' => $po->id,
                ]);
            }
        }
    }
}

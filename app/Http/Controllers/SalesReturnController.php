<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockItem;
use App\Services\InventoryService;
use App\Services\SettingsService;
use App\Support\AmountInWords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class SalesReturnController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): View
    {
        $salesReturns = SalesReturn::with(['customer', 'items'])
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('sales-returns.index', compact('salesReturns'));
    }

    public function create(): View|RedirectResponse
    {
        $customers = Customer::orderByRaw('COALESCE(NULLIF(company_name, ""), name)')->orderBy('name')->get();
        if ($customers->isEmpty()) {
            return redirect()
                ->route('customers.create')
                ->with('error', 'Add a customer first, then create a sales return.');
        }

        $stockItems = $this->inventoryItems(true);
        if ($stockItems->isEmpty()) {
            return redirect()
                ->route('stock-items.create')
                ->with('error', 'Add inventory items first, then create a sales return.');
        }

        return view('sales-returns.create', [
            'customers' => $customers,
            'stockItems' => $stockItems,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedReturn($request);

        try {
            DB::transaction(function () use ($data) {
                $customer = Customer::whereKey($data['customer_id'])->lockForUpdate()->firstOrFail();
                $total = $this->calculateItemsTotal($data['items']);

                $return = SalesReturn::create([
                    'return_no' => SalesReturn::nextNumber(),
                    'customer_id' => $customer->id,
                    'return_date' => $data['return_date'],
                    'notes' => $data['notes'] ?? null,
                    'total_amount' => $total,
                ]);

                $this->persistItems($return, $data['items'], $data['return_date']);
                $customer->decrement('current_balance', $total);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-returns.index')->with('success', 'Sales return saved. Stock increased.');
    }

    public function print(SalesReturn $salesReturn, SettingsService $settings): View
    {
        $salesReturn->load(['customer', 'items']);

        return view('sales-returns.print', [
            'salesReturn' => $salesReturn,
            'settings' => $settings,
            'amountInWords' => AmountInWords::rupees((float) $salesReturn->total_amount),
            'printedAt' => now(),
        ]);
    }

    public function show(SalesReturn $salesReturn): RedirectResponse
    {
        return redirect()->route('sales-returns.edit', $salesReturn);
    }

    public function edit(SalesReturn $salesReturn): View
    {
        $salesReturn->load('items.stockItem', 'customer');

        return view('sales-returns.edit', [
            'salesReturn' => $salesReturn,
            'customers' => Customer::orderByRaw('COALESCE(NULLIF(company_name, ""), name)')->orderBy('name')->get(),
            'stockItems' => $this->inventoryItems(false),
        ]);
    }

    public function update(Request $request, SalesReturn $salesReturn): RedirectResponse
    {
        $data = $this->validatedReturn($request);

        try {
            DB::transaction(function () use ($salesReturn, $data) {
                $oldCustomer = Customer::whereKey($salesReturn->customer_id)->lockForUpdate()->firstOrFail();
                $oldCustomer->increment('current_balance', (float) $salesReturn->total_amount);

                $this->inventory->revertSalesReturn($salesReturn);
                SalesReturnItem::where('sales_return_id', $salesReturn->id)->delete();

                $newTotal = $this->calculateItemsTotal($data['items']);

                $salesReturn->update([
                    'customer_id' => $data['customer_id'],
                    'return_date' => $data['return_date'],
                    'notes' => $data['notes'] ?? null,
                    'total_amount' => $newTotal,
                ]);

                $this->persistItems($salesReturn, $data['items'], $data['return_date']);

                $newCustomer = Customer::whereKey($salesReturn->customer_id)->lockForUpdate()->firstOrFail();
                $newCustomer->decrement('current_balance', $newTotal);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-returns.index')->with('success', 'Sales return updated. Stock recalculated.');
    }

    public function destroy(SalesReturn $salesReturn): RedirectResponse
    {
        DB::transaction(function () use ($salesReturn) {
            $customer = Customer::whereKey($salesReturn->customer_id)->lockForUpdate()->firstOrFail();
            $customer->increment('current_balance', (float) $salesReturn->total_amount);

            $this->inventory->revertSalesReturn($salesReturn);
            $salesReturn->items()->delete();
            $salesReturn->delete();
        });

        return back()->with('success', 'Sales return deleted. Stock reversed.');
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
            'customer_id' => ['required', 'exists:customers,id'],
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
    private function persistItems(SalesReturn $return, array $items, string $returnDate): void
    {
        foreach ($items as $row) {
            $unitPrice = (float) $row['unit_price'];
            $qty = (float) $row['quantity'];

            SalesReturnItem::create([
                'sales_return_id' => $return->id,
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
                'moved_at' => $returnDate,
                'reference' => $return->return_no,
                'notes' => $row['item_name'] ?? 'Sales return',
                'source_type' => 'sales_return',
                'source_id' => $return->id,
            ]);
        }
    }
}

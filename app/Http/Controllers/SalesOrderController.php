<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\StockItem;
use App\Services\SalesOrderService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class SalesOrderController extends Controller
{
    public function __construct(private SalesOrderService $orders) {}

    public function index(): View
    {
        $orders = SalesOrder::with(['customer', 'salesman', 'invoice'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->limit(400)
            ->get();

        $pendingCount = SalesOrder::query()->where('status', SalesOrder::STATUS_PENDING)->count();

        return view('sales-orders.index', compact('orders', 'pendingCount'));
    }

    public function show(SalesOrder $sales_order): View
    {
        $sales_order->load(['customer', 'salesman', 'lines.stockItem', 'invoice', 'confirmer']);

        return view('sales-orders.show', [
            'order' => $sales_order,
        ]);
    }

    public function edit(SalesOrder $sales_order, SettingsService $settings): View|RedirectResponse
    {
        if (! $sales_order->isPending()) {
            return redirect()->route('sales-orders.show', $sales_order)
                ->with('error', 'Only pending orders can be edited.');
        }

        $sales_order->load(['lines', 'salesman']);

        return view('sales-orders.edit', [
            'order' => $sales_order,
            'customers' => Customer::query()->orderByRaw('COALESCE(NULLIF(company_name, ""), name)')->get(),
            'stockItems' => $this->stockItems(),
            'vatRate' => $settings->vatRate(),
        ]);
    }

    public function update(Request $request, SalesOrder $sales_order): RedirectResponse
    {
        if (! $sales_order->isPending()) {
            return redirect()->route('sales-orders.show', $sales_order)
                ->with('error', 'Only pending orders can be edited.');
        }

        $data = $this->validated($request);
        $salesman = $sales_order->salesman;

        if (! $salesman) {
            return back()->withInput()->with('error', 'This order has no salesman.');
        }

        try {
            $this->orders->save($sales_order, $data, $data['lines'], $salesman);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-orders.show', $sales_order)->with('success', 'Order updated.');
    }

    public function pendingFeed(): JsonResponse
    {
        $pending = SalesOrder::with(['customer', 'salesman'])
            ->where('status', SalesOrder::STATUS_PENDING)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return response()->json([
            'count' => SalesOrder::query()->where('status', SalesOrder::STATUS_PENDING)->count(),
            'orders' => $pending->map(fn (SalesOrder $order) => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'salesman' => $order->salesman?->name ?: 'Salesman',
                'customer' => $order->customer?->displayName() ?: 'Customer',
                'total' => ams_num($order->total),
                'url' => route('sales-orders.show', $order),
            ])->values(),
        ]);
    }

    public function confirm(SalesOrder $sales_order): RedirectResponse
    {
        try {
            $invoice = $this->orders->confirm($sales_order, request()->user());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Order confirmed and invoice generated.');
    }

    public function reject(Request $request, SalesOrder $sales_order): RedirectResponse
    {
        $data = $request->validate([
            'reject_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->orders->reject($sales_order, $data['reject_reason'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-orders.index')->with('success', 'Order rejected.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'lines.*.vat_rate' => ['required', 'numeric', 'gte:0', 'lte:100'],
        ]);

        $data['lines'] = array_values(array_filter(
            $data['lines'],
            fn ($line) => filled($line['description'] ?? null) || filled($line['stock_item_id'] ?? null)
        ));

        foreach ($data['lines'] as &$line) {
            $line = $this->orders->hydrateLine($line);
        }
        unset($line);

        if ($data['lines'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Add at least one order line.',
            ]);
        }

        return $data;
    }

    /**
     * @return Collection<int, StockItem>
     */
    private function stockItems(): Collection
    {
        return StockItem::query()
            ->where('is_active', true)
            ->with('variants')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'sale_price', 'quantity', 'has_variants']);
    }
}

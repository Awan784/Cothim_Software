<?php

namespace App\Http\Controllers\Salesman;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Salesman as SalesmanModel;
use App\Models\SalesOrder;
use App\Models\StockItem;
use App\Services\SalesOrderService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private SalesOrderService $orders) {}

    public function index(): View
    {
        $orders = SalesOrder::with('customer')
            ->where('salesman_id', $this->salesman()->id)
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        return view('salesman.orders.index', compact('orders'));
    }

    public function create(SettingsService $settings): View
    {
        return view('salesman.orders.create', $this->formData($settings));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $order = $this->orders->save(null, $data, $data['lines'], $this->salesman());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('salesman.orders.show', $order)->with('success', 'Order submitted. Admin will confirm it as an invoice.');
    }

    public function show(SalesOrder $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['customer', 'lines', 'invoice']);

        return view('salesman.orders.show', compact('order'));
    }

    public function edit(SalesOrder $order, SettingsService $settings): View|RedirectResponse
    {
        $this->authorizeOrder($order);

        if (! $order->isPending()) {
            return redirect()->route('salesman.orders.show', $order)->with('error', 'Only pending orders can be edited.');
        }

        $order->load('lines');

        return view('salesman.orders.edit', $this->formData($settings, $order));
    }

    public function update(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        $data = $this->validated($request);

        try {
            $this->orders->save($order, $data, $data['lines'], $this->salesman());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('salesman.orders.show', $order)->with('success', 'Order updated.');
    }

    public function destroy(SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        try {
            $this->orders->deletePending($order);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('salesman.orders.index')->with('success', 'Order deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(SettingsService $settings, ?SalesOrder $order = null): array
    {
        return [
            'order' => $order,
            'customers' => $this->salesman()->assignableCustomers()->get(),
            'stockItems' => $this->stockItems(),
            'vatRate' => $settings->vatRate(),
        ];
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

        $customer = Customer::query()->findOrFail($data['customer_id']);
        if (! $this->salesman()->canSellTo($customer)) {
            abort(403, 'You can only sell to customers in your city.');
        }

        $data['lines'] = array_values(array_filter(
            $data['lines'],
            fn ($line) => filled($line['description'] ?? null) || filled($line['stock_item_id'] ?? null)
        ));

        foreach ($data['lines'] as &$line) {
            $line = $this->orders->hydrateLine($line);
        }

        if ($data['lines'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Add at least one order line.',
            ]);
        }

        return $data;
    }

    private function stockItems(): Collection
    {
        return StockItem::query()
            ->where('is_active', true)
            ->with('variants')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'sale_price', 'quantity', 'has_variants']);
    }

    private function salesman(): SalesmanModel
    {
        return auth('salesman')->user();
    }

    private function authorizeOrder(SalesOrder $order): void
    {
        abort_unless((int) $order->salesman_id === (int) $this->salesman()->id, 403);
    }
}

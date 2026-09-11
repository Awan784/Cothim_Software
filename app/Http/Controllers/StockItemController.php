<?php

namespace App\Http\Controllers;

use App\Models\StockCategory;
use App\Models\StockItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockItemController extends Controller
{
    public function index(): View
    {
        $stockItems = StockItem::with('stockCategory')
            ->withCount('variants')
            ->orderBy('name')
            ->get();

        return view('stock-items.index', compact('stockItems'));
    }

    public function create(): View|RedirectResponse
    {
        $stockCategories = StockCategory::where('is_active', true)->orderBy('name')->get();

        if ($stockCategories->isEmpty()) {
            return redirect()
                ->route('stock-categories.create')
                ->with('error', 'Create a category first, then add inventory items.');
        }

        return view('stock-items.create', [
            'stockCategories' => $stockCategories,
            'stockItem' => new StockItem([
                'is_active' => true,
                'unit' => 'pcs',
                'quantity' => 0,
                'stock_category_id' => request('stock_category_id'),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $variants = $data['variants'] ?? [];
            unset($data['variants']);

            $item = StockItem::create($data);
            $this->syncVariants($item, $data['has_variants'], $variants);
        });

        return redirect()->route('stock-items.index')->with('success', 'Item created.');
    }

    public function show(StockItem $stockItem): RedirectResponse
    {
        return redirect()->route('stock-items.edit', $stockItem);
    }

    public function edit(StockItem $stockItem): View
    {
        $stockItem->load('variants');

        return view('stock-items.edit', [
            'stockCategories' => StockCategory::orderBy('name')->get(),
            'stockItem' => $stockItem,
        ]);
    }

    public function update(Request $request, StockItem $stockItem): RedirectResponse
    {
        $data = $this->validated($request, $stockItem);

        DB::transaction(function () use ($stockItem, $data) {
            $variants = $data['variants'] ?? [];
            unset($data['variants']);

            $stockItem->update($data);
            $this->syncVariants($stockItem, $data['has_variants'], $variants);
        });

        return redirect()->route('stock-items.index')->with('success', 'Item updated.');
    }

    public function destroy(StockItem $stockItem): RedirectResponse
    {
        $stockItem->delete();

        return back()->with('success', 'Item deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?StockItem $item = null): array
    {
        $orgId = organization_id();
        $skuRule = Rule::unique('stock_items', 'sku')->where(fn ($q) => $q->where('organization_id', $orgId));
        if ($item) {
            $skuRule->ignore($item->id);
        }

        $data = $request->validate([
            'stock_category_id' => ['required', 'exists:stock_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', $skuRule],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', Rule::in(array_keys(StockItem::UNITS))],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'has_variants' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'potency' => ['nullable', 'string', 'max:50'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'composition' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'storage_note' => ['nullable', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'variants' => ['exclude_unless:has_variants,1', 'required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.size' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['has_variants'] = (bool) ($data['has_variants'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        foreach (['sku', 'batch_no', 'description', 'potency', 'pack_size', 'manufacturer', 'composition', 'barcode', 'storage_note', 'hs_code'] as $field) {
            if (($data[$field] ?? '') === '') {
                $data[$field] = null;
            }
        }

        if (! $data['has_variants']) {
            $data['variants'] = [];
        }

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(StockItem $item, bool $hasVariants, array $variants): void
    {
        if (! $hasVariants) {
            $item->variants()->delete();

            return;
        }

        $keep = [];
        foreach (array_values($variants) as $index => $row) {
            $payload = [
                'name' => $row['name'],
                'size' => ($row['size'] ?? '') === '' ? null : $row['size'],
                'price' => $row['price'],
                'sort_order' => $index,
            ];

            if (! empty($row['id'])) {
                $variant = $item->variants()->whereKey($row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keep[] = $variant->id;
                    continue;
                }
            }

            $keep[] = $item->variants()->create($payload)->id;
        }

        $item->variants()->whereNotIn('id', $keep)->delete();
    }
}

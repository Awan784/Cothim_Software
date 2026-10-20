<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturnItem;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockItemController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('stock', 'all');
        if (! in_array($filter, ['all', 'low', 'zero'], true)) {
            $filter = 'all';
        }

        $query = StockItem::with('stockCategory')->withCount('variants');

        if ($filter === 'low') {
            $query->lowStock();
        } elseif ($filter === 'zero') {
            $query->outOfStock();
        }

        $stockItems = $query->orderBy('name')->get();
        $counts = [
            'all' => StockItem::query()->count(),
            'low' => StockItem::query()->lowStock()->count(),
            'zero' => StockItem::query()->outOfStock()->count(),
        ];

        return view('stock-items.index', compact('stockItems', 'filter', 'counts'));
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

            $manufacturedAt = $data['manufactured_at'] ?? null;
            unset($data['manufactured_at']);

            $item = StockItem::create($data);
            $item->seedOpeningLot($manufacturedAt);
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
        $stockItem->load(['variants', 'lots' => fn ($query) => $query->orderBy('received_at')->orderBy('id')]);

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

            $manufacturedAt = $data['manufactured_at'] ?? null;
            $lotQuantities = $data['lots'] ?? [];
            unset($data['manufactured_at'], $data['lots']);

            $stockItem->update($data);
            $this->syncVariants($stockItem, $data['has_variants'], $variants);
            $stockItem->applyEditedQuantities($lotQuantities, $manufacturedAt);
        });

        return redirect()->route('stock-items.index')->with('success', 'Item updated.');
    }

    public function destroy(StockItem $stockItem): RedirectResponse
    {
        $reason = $this->deleteBlockedReason($stockItem);
        if ($reason !== null) {
            return back()->with('error', $reason);
        }

        try {
            $stockItem->delete();
        } catch (QueryException) {
            return back()->with('error', 'This item cannot be deleted because it is still used on other records.');
        }

        return back()->with('success', 'Item deleted.');
    }

    private function deleteBlockedReason(StockItem $stockItem): ?string
    {
        $id = $stockItem->id;
        $parts = array_filter([
            $this->usagePhrase($this->distinctCount(PurchaseOrderItem::class, 'purchase_order_id', $id), 'purchase', 'purchases'),
            $this->usagePhrase($this->distinctCount(PurchaseReturnItem::class, 'purchase_return_id', $id), 'purchase return', 'purchase returns'),
            $this->usagePhrase($this->distinctCount(SalesReturnItem::class, 'sales_return_id', $id), 'sales return', 'sales returns'),
            $this->usagePhrase($this->movementCount($id, 'sales_invoice'), 'sales invoice', 'sales invoices'),
            $this->usagePhrase($this->movementCount($id, null), 'stock movement', 'stock movements'),
        ]);

        if ($parts === []) {
            return null;
        }

        return 'This item cannot be deleted because it is used on '.$this->joinReasons(array_values($parts)).'.';
    }

    private function distinctCount(string $model, string $column, int $itemId): int
    {
        return (int) $model::query()
            ->where('stock_item_id', $itemId)
            ->distinct()
            ->count($column);
    }

    private function movementCount(int $itemId, ?string $sourceType): int
    {
        $known = ['sales_invoice', 'purchase_order', 'purchase_return', 'sales_return'];
        $query = StockMovement::query()->where('stock_item_id', $itemId);

        if ($sourceType === null) {
            $query->where(function ($inner) use ($known) {
                $inner->whereNull('source_type')->orWhereNotIn('source_type', $known);
            });

            return (int) $query->count();
        }

        return (int) $query->where('source_type', $sourceType)->distinct()->count('source_id');
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinReasons(array $parts): string
    {
        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts).' and '.$last;
    }

    private function usagePhrase(int $count, string $singular, string $plural): ?string
    {
        if ($count < 1) {
            return null;
        }

        return $count.' '.($count === 1 ? $singular : $plural);
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
            'quantity' => ['required', 'numeric'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'has_variants' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'potency' => ['nullable', 'string', 'max:50'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'manufactured_at' => ['nullable', 'date'],
            'lots' => ['nullable', 'array'],
            'lots.*.quantity' => ['nullable', 'numeric'],
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

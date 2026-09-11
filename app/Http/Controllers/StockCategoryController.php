<?php

namespace App\Http\Controllers;

use App\Models\StockCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class StockCategoryController extends Controller
{
    public function index(): View
    {
        $stockCategories = StockCategory::withCount('stockItems')->orderBy('name')->get();

        return view('stock-categories.index', compact('stockCategories'));
    }

    public function create(): View
    {
        return view('stock-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        StockCategory::create($data);

        return redirect()->route('stock-categories.index')->with('success', 'Category created. You can add items now.');
    }

    public function show(StockCategory $stockCategory): RedirectResponse
    {
        return redirect()->route('stock-categories.edit', $stockCategory);
    }

    public function edit(StockCategory $stockCategory): View
    {
        return view('stock-categories.edit', compact('stockCategory'));
    }

    public function update(Request $request, StockCategory $stockCategory): RedirectResponse
    {
        $data = $this->validated($request, $stockCategory);

        $stockCategory->update($data);

        return redirect()->route('stock-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(StockCategory $stockCategory): RedirectResponse
    {
        try {
            $stockCategory->delete();
        } catch (Throwable) {
            return back()->with('error', 'This category has items. Move or delete those items first.');
        }

        return back()->with('success', 'Category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?StockCategory $category = null): array
    {
        $nameRule = Rule::unique('stock_categories', 'name')->where(fn ($q) => $q->where('organization_id', organization_id()));
        if ($category) {
            $nameRule->ignore($category->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'kind' => ['nullable', Rule::in(array_keys(StockCategory::KINDS))],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['kind'] = $data['kind'] ?: null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}

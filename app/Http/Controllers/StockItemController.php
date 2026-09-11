<?php

namespace App\Http\Controllers;

use App\Models\StockCategory;
use App\Models\StockItem;
use Illuminate\Http\Request;

class StockItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockItems = StockItem::with('stockCategory')->orderBy('name')->get();

        return view('stock-items.index', compact('stockItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stockCategories = StockCategory::orderBy('name')->get();

        return view('stock-items.create', compact('stockCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'stock_category_id' => ['required', 'exists:stock_categories,id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:stock_items,sku'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        StockItem::create($data);

        return redirect()->route('stock-items.index')->with('success', 'Stock item created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StockItem $stockItem)
    {
        return redirect()->route('stock-items.edit', $stockItem);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockItem $stockItem)
    {
        $stockCategories = StockCategory::orderBy('name')->get();

        return view('stock-items.edit', compact('stockItem', 'stockCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockItem $stockItem)
    {
        $data = $request->validate([
            'stock_category_id' => ['required', 'exists:stock_categories,id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:stock_items,sku,'.$stockItem->id],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $stockItem->update($data);

        return redirect()->route('stock-items.index')->with('success', 'Stock item updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockItem $stockItem)
    {
        $stockItem->delete();

        return back()->with('success', 'Stock item deleted.');
    }
}

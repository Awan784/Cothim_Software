<?php

namespace App\Http\Controllers;

use App\Models\StockCategory;
use Illuminate\Http\Request;

class StockCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockCategories = StockCategory::orderBy('name')->get();

        return view('stock-categories.index', compact('stockCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('stock-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:stock_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        StockCategory::create($data);

        return redirect()->route('stock-categories.index')->with('success', 'Stock category created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StockCategory $stockCategory)
    {
        return redirect()->route('stock-categories.edit', $stockCategory);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockCategory $stockCategory)
    {
        return view('stock-categories.edit', compact('stockCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockCategory $stockCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:stock_categories,name,'.$stockCategory->id],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $stockCategory->update($data);

        return redirect()->route('stock-categories.index')->with('success', 'Stock category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockCategory $stockCategory)
    {
        $stockCategory->delete();

        return back()->with('success', 'Stock category deleted.');
    }
}

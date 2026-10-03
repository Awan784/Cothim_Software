@extends('template.layout')
@section('title', 'Inventory Items')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Items</h1>
                <p class="mb-0">Homeopathic medicines in stock.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('stock-categories.index') }}" class="btn btn-sm btn-outline-secondary">Categories</a>
                <a href="{{ route('stock-items.create') }}" class="btn btn-sm btn-gray-800">Add Item</a>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('stock-items.index') }}" @class(['btn', 'btn-sm', 'btn-primary' => $filter === 'all', 'btn-outline-secondary' => $filter !== 'all'])>
                All <span class="ms-1">{{ $counts['all'] }}</span>
            </a>
            <a href="{{ route('stock-items.index', ['stock' => 'low']) }}" @class(['btn', 'btn-sm', 'btn-primary' => $filter === 'low', 'btn-outline-secondary' => $filter !== 'low'])>
                Low stock <span class="ms-1">{{ $counts['low'] }}</span>
            </a>
            <a href="{{ route('stock-items.index', ['stock' => 'zero']) }}" @class(['btn', 'btn-sm', 'btn-primary' => $filter === 'zero', 'btn-outline-secondary' => $filter !== 'zero'])>
                Qty 0 <span class="ms-1">{{ $counts['zero'] }}</span>
            </a>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Potency</th>
                            <th>Unit</th>
                            <th class="text-end">Qty</th>
                            <th>Batch</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Sale</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockItems as $item)
                            <tr @class(['ams-stock-zero' => $item->isOutOfStock()])>
                                <td class="text-gray-900">{{ $item->sku ?: '—' }}</td>
                                <td class="text-gray-900">
                                    {{ $item->name }}
                                    @if($item->has_variants)
                                        <div class="small text-muted">{{ $item->variants_count }} variants</div>
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ $item->stockCategory?->name }}</td>
                                <td class="text-gray-900">{{ $item->potency ?: '—' }}</td>
                                <td class="text-gray-900">{{ strtoupper($item->unit ?: '') }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                                <td class="stock-batches" data-content="{{ $item->lotsSummary() }}">
                                    @php
                                        $visibleLots = $item->lots->filter(fn ($lot) => (float) $lot->quantity > 0);
                                    @endphp
                                    @forelse($visibleLots as $lot)
                                        <div class="stock-batch-line">
                                            <span class="stock-batch-no">{{ $lot->batchLabel() }}</span>
                                            <span class="stock-batch-qty">qty {{ number_format((float) $lot->quantity, 2) }}</span>
                                        </div>
                                    @empty
                                        @if($item->batch_no)
                                            <div class="stock-batch-line">
                                                <span class="stock-batch-no">{{ $item->batch_no }}</span>
                                                <span class="stock-batch-qty">qty {{ number_format((float) $item->quantity, 2) }}</span>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    @endforelse
                                </td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $item->cost_price, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ $item->sale_price !== null ? number_format((float) $item->sale_price, 2) : '—' }}</td>
                                <td class="text-gray-900">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('stock-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('stock-items.destroy', $item) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-gray-600">
                                    @if($filter === 'low')
                                        No low-stock items.
                                    @elseif($filter === 'zero')
                                        No items with quantity 0.
                                    @else
                                        No items yet. Create a category first, then add items.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .table td.stock-batches {
            min-width: 160px;
            white-space: normal !important;
            vertical-align: top;
        }
        .stock-batch-line {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            line-height: 1.35;
            margin: 0 0 0.2rem;
        }
        .stock-batch-line:last-child { margin-bottom: 0; }
        .stock-batch-no {
            color: #dc2626;
            font-weight: 800;
            letter-spacing: 0.01em;
        }
        .stock-batch-qty {
            color: #111827;
            font-weight: 600;
            white-space: nowrap;
        }
    </style>
@endpush

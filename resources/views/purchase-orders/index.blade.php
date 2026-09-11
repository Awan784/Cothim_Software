@extends('template.layout')
@section('title', 'Purchase Orders')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Purchase Orders</h1>
                <p class="mb-0">Supplier purchase notes with items.</p>
            </div>
            <div>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-gray-800">Create Purchase Order</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>PO No</th>
                            <th>Supplier</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr>
                                <td class="text-gray-900">{{ ams_date($po->po_date) }}</td>
                                <td class="text-gray-900">{{ $po->po_no }}</td>
                                <td class="text-gray-900">
                                    <a href="{{ route('suppliers.show', $po->supplier_id) }}">{{ $po->supplier?->name }}</a>
                                </td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $po->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('purchase-orders.edit', $po) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this purchase order?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No purchase orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


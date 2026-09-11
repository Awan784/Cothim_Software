@extends('template.layout')
@section('title', 'Journal Vouchers')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">General Journal</h1>
                <p class="mb-0">Journal vouchers (debit / credit).</p>
            </div>
            <div>
                <a href="{{ route('journal-vouchers.create') }}" class="btn btn-sm btn-gray-800">Create Journal Voucher</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Voucher No</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th>Notes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journalVouchers as $jv)
                            <tr>
                                <td class="text-gray-900">{{ ams_date($jv->voucher_date) }}</td>
                                <td class="text-gray-900">{{ $jv->voucher_no }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $jv->total_debit, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $jv->total_credit, 2) }}</td>
                                <td class="text-gray-900">{{ \Illuminate\Support\Str::limit($jv->notes, 60) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('journal-vouchers.edit', $jv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('journal-vouchers.destroy', $jv) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this journal voucher?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No journal vouchers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

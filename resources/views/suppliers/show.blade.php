@extends('template.layout')
@section('title', 'Supplier Ledger')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">{{ $supplier->name }}</h1>
                <p class="mb-0">
                    Current Payable Balance:
                    <strong>{{ number_format((float) ($supplier->current_balance ?? 0), 2) }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Ref</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $e)
                            <tr @class([
                                'ledger-row-receive' => ($e['type'] ?? '') === 'cash_receive',
                                'ledger-row-payment' => ($e['type'] ?? '') === 'cash_payment',
                            ])>
                                <td class="text-gray-900">{{ ams_date($e['date']) }}</td>
                                <td class="text-gray-900">{{ $e['ref'] }}</td>
                                <td class="text-gray-900">{{ $e['description'] }}</td>
                                <td class="text-gray-900 text-end">{{ $e['debit'] ? number_format((float) $e['debit'], 2) : '' }}</td>
                                <td class="text-gray-900 text-end">{{ $e['credit'] ? number_format((float) $e['credit'], 2) : '' }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $e['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No ledger entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


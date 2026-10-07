@extends('template.layout')
@section('title', 'Edit Salesman')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Salesman</h1>
                <p class="mb-0">
                    {{ $salesman->citiesLabel() !== '' ? $salesman->citiesLabel() : 'No city' }} · This month:
                    <strong>{{ number_format((float) $monthSales, 2) }}</strong>
                    of {{ number_format((float) $salesman->monthly_target, 2) }}
                </p>
            </div>
            <div>
                <a href="{{ route('salesmen.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted">Current advance</div>
                    <div class="fs-5 fw-semibold">{{ number_format((float) $salesman->advance_balance, 2) }}</div>
                </div>
                <a href="{{ route('salesman-settlements.create', ['salesman_id' => $salesman->id]) }}" class="btn btn-sm btn-outline-primary">New settlement</a>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <form method="post" action="{{ route('salesmen.update', $salesman) }}">
                @csrf
                @method('put')
                @include('salesmen._fields')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>

        <div class="card p-4">
            <h2 class="h6 mb-3">Customers in {{ $salesman->citiesLabel() !== '' ? $salesman->citiesLabel() : 'assigned cities' }}</h2>
            <p class="small text-muted mb-3">After salesman login, orders can only be made for customers in these cities.</p>
            <div class="table-responsive">
                <table class="table table-flush mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Shop</th>
                            <th>City</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cityCustomers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('customers.edit', $customer) }}">{{ $customer->displayName() }}</a>
                                </td>
                                <td>{{ $customer->city ?: '—' }}</td>
                                <td>{{ $customer->mobile ?: $customer->phone ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-600">No customers in this city yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

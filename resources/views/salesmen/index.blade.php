@extends('template.layout')
@section('title', 'Salesmen')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Salesmen</h1>
                <p class="mb-0">Assign cities, username, password, and monthly target.</p>
            </div>
            <div>
                <a href="{{ route('salesmen.create') }}" class="btn btn-sm btn-gray-800">Add Salesman</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th class="text-end">Shops in city</th>
                            <th class="text-end">Monthly target</th>
                            <th class="text-end">Opening</th>
                            <th class="text-end">Advance</th>
                            <th class="text-end">This month</th>
                            <th class="text-end">Commission</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesmen as $salesman)
                            @php
                                $cities = $salesman->cityList();
                                $shops = 0;
                                foreach ($cities as $city) {
                                    $shops += (int) ($shopsByCity[$city] ?? 0);
                                }
                                $bySalesman = $monthSalesBySalesman[$salesman->id] ?? null;
                                $achieved = (float) ($bySalesman->month_sales ?? 0);
                                $extraCities = max(0, count($cities) - 1);
                            @endphp
                            <tr class="salesman-row" data-href="{{ route('salesmen.show', $salesman) }}" tabindex="0">
                                <td class="text-gray-900">
                                    <a href="{{ route('salesmen.show', $salesman) }}" class="fw-semibold text-gray-900">{{ $salesman->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $salesman->username }}</td>
                                <td class="text-gray-900 salesman-cities-cell">
                                    @if($cities === [])
                                        —
                                    @else
                                        <span class="salesman-city-primary">{{ $cities[0] }}</span>
                                        @if($extraCities > 0)
                                            <button
                                                type="button"
                                                class="btn btn-link btn-sm p-0 align-baseline salesman-cities-more"
                                                data-bs-toggle="modal"
                                                data-bs-target="#salesmanCitiesModal{{ $salesman->id }}"
                                            >+{{ $extraCities }} more</button>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ $salesman->mobile ?: $salesman->phone ?: '—' }}</td>
                                <td class="text-gray-900 text-end">{{ $shops }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $salesman->monthly_target, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $salesman->opening_balance, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $salesman->advance_balance, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format($achieved, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format($bySalesman ? (float) $bySalesman->month_commission : 0, 2) }}</td>
                                <td class="text-gray-900">{{ $salesman->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end salesman-row-actions">
                                    <a href="{{ route('salesmen.show', $salesman) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
                                    <a href="{{ route('salesmen.edit', $salesman) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="{{ route('salesman-settlements.create', ['salesman_id' => $salesman->id]) }}" class="btn btn-sm btn-outline-secondary">Settle</a>
                                    <form action="{{ route('salesmen.destroy', $salesman) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this salesman?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-gray-600">No salesmen yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach($salesmen as $salesman)
        @php $cities = $salesman->cityList(); @endphp
        @if(count($cities) > 1)
            <div class="modal fade" id="salesmanCitiesModal{{ $salesman->id }}" tabindex="-1" aria-labelledby="salesmanCitiesModalLabel{{ $salesman->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="salesmanCitiesModalLabel{{ $salesman->id }}">{{ $salesman->name }} · Cities</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="list-unstyled mb-0 salesman-cities-modal-list">
                                @foreach($cities as $city)
                                    <li>{{ $city }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
@push('styles')
    <style>
        .salesman-row { cursor: pointer; }
        .salesman-row:hover { filter: brightness(0.97); }
        .salesman-cities-cell {
            white-space: nowrap;
            max-width: 11rem;
        }
        .salesman-city-primary {
            display: inline-block;
            max-width: 7.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
        }
        .salesman-cities-more {
            font-size: 12px;
            font-weight: 600;
            text-decoration: none !important;
            margin-left: 4px;
        }
        .salesman-cities-modal-list li {
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .salesman-cities-modal-list li:last-child { border-bottom: 0; }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const row = event.target.closest('.salesman-row');
            if (!row) return;
            if (event.target.closest('.salesman-row-actions, .salesman-cities-more, a, button, form')) return;
            window.location.href = row.dataset.href;
        });
    </script>
@endpush

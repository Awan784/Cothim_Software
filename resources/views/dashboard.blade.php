@extends('template.layout')
@section('title', 'Dashboard')

@section('content')
    <div class="pb-4 ams-dashboard">
        <div class="ams-dash-hero">
            <div>
                <p class="ams-dash-kicker">{{ ams_date(now()) }}</p>
                <h1>Dashboard</h1>
                <p>Accounts overview and live salesman orders.</p>
            </div>
            <div class="ams-dash-hero-pills">
                <span class="ams-dash-pill">{{ number_format((int) $customersCount) }} customers</span>
                <span class="ams-dash-pill">Cash {{ number_format((float) $cashBalance, 2) }}</span>
                <span class="ams-dash-pill">Bank {{ number_format((float) $totalBankBalance, 2) }}</span>
            </div>
        </div>

        <div id="amsLiveOrdersBanner" class="alert alert-warning ams-live-orders-banner d-flex justify-content-between align-items-center mb-3 text-decoration-none" role="status" @if(($pendingSalesOrdersCount ?? 0) < 1) hidden @endif>
            <span>
                <strong id="amsLiveOrdersBannerCount">{{ $pendingSalesOrdersCount ?? 0 }}</strong>
                salesman order<span id="amsLiveOrdersBannerPlural">{{ ($pendingSalesOrdersCount ?? 0) === 1 ? '' : 's' }}</span>
                waiting for confirmation.
            </span>
            <a href="{{ route('sales-orders.index') }}" class="alert-link">Review →</a>
        </div>

        <div id="amsLiveOrdersPanel" class="card mb-3" @if(($pendingSalesOrders ?? collect())->isEmpty()) hidden @endif>
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>New salesman orders</strong>
                <span class="ams-status-tag is-pending" id="amsLiveOrdersTag">Live</span>
            </div>
            <div class="table-responsive">
                <table class="table table-flush mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Salesman</th>
                            <th>Customer</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="amsLiveOrdersBody">
                        @forelse($pendingSalesOrders as $order)
                            <tr data-order-id="{{ $order->id }}">
                                <td class="fw-semibold"><a href="{{ route('sales-orders.show', $order) }}">{{ $order->order_no }}</a></td>
                                <td>{{ $order->salesman?->name ?: '—' }}</td>
                                <td>{{ $order->customer?->displayName() ?: '—' }}</td>
                                <td class="text-end">{{ ams_num($order->total) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('sales-orders.show', $order) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr id="amsLiveOrdersEmpty">
                                <td colspan="5" class="text-center text-muted">No pending salesman orders.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--customers" href="{{ route('customers.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Customers</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((int) $customersCount) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Receivable <strong>{{ number_format((float) $totalCustomerReceivable, 2) }}</strong></span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--suppliers" href="{{ route('suppliers.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Suppliers</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((int) $suppliersCount) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Payable <strong>{{ number_format((float) $totalSupplierPayable, 2) }}</strong></span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--bank" href="{{ route('bank-accounts.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 .5 0 4v1h16V4L8 .5zM1 6v7h2V6H1zm4 0v7h2V6H5zm4 0v7h2V6H9zm4 0v7h2V6h-2zM0 14v1.5h16V14H0z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Bank Accounts</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((float) $totalBankBalance, 2) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-chips">
                            <span class="ams-kpi-chip">{{ number_format((int) $bankAccountsCount) }} accounts</span>
                            <span class="ams-kpi-chip ams-kpi-chip--in">In {{ number_format((float) $totalBankReceived, 2) }}</span>
                            <span class="ams-kpi-chip ams-kpi-chip--out">Out {{ number_format((float) $totalBankPaid, 2) }}</span>
                        </span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--expenses" href="{{ route('expense-accounts.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .556.146l.5.5A.5.5 0 0 1 15 2v13.5a.5.5 0 0 1-.73.447L13 15.14l-.646.647a.5.5 0 0 1-.708 0L11 15.139l-.646.647a.5.5 0 0 1-.708 0L9 15.139l-.646.647a.5.5 0 0 1-.708 0L7 15.139l-.646.647a.5.5 0 0 1-.708 0L5 15.139l-.646.647a.5.5 0 0 1-.708 0L3 15.139l-.646.647A.5.5 0 0 1 1 15.5V2a.5.5 0 0 1 .053-.224l.5-.5zM3 4.5a.5.5 0 0 0 0 1h10a.5.5 0 0 0 0-1H3zm0 2.5a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H3zm0 2.5a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H3z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Total Expenses</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((float) $totalExpenses, 2) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">{{ number_format((int) $expenseAccountsCount) }} expense account{{ $expenseAccountsCount === 1 ? '' : 's' }}</span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--cash" href="{{ route('cash-vouchers.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2H3z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Total Cash Received</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((float) $totalCashReceived, 2) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Cash balance <strong>{{ number_format((float) $cashBalance, 2) }}</strong></span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a class="ams-kpi ams-kpi--orders" href="{{ route('purchase-orders.index') }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Purchase Orders</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((int) $purchaseOrdersCount) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Total value <strong>{{ number_format((float) $purchaseOrdersTotal, 2) }}</strong></span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <a class="ams-kpi ams-kpi--hero ams-kpi--bank-in" href="{{ route('cash-vouchers.index', ['type' => 'receive']) }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Total Received in Bank</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((float) $totalBankReceived, 2) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Payments received into bank accounts</span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6">
                <a class="ams-kpi ams-kpi--hero ams-kpi--bank-out" href="{{ route('cash-vouchers.index', ['type' => 'payment']) }}">
                    <span class="ams-kpi-orb"></span>
                    <span class="ams-kpi-orb-2"></span>
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>
                            </svg>
                        </span>
                        <span class="ams-kpi-label"><span class="ams-live-dot"></span> Total Paid from Bank</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((float) $totalBankPaid, 2) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">Payments made from bank accounts</span>
                        <span class="ams-kpi-go">View →</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm ams-dash-table">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Recent Purchase Orders</strong>
                        <a class="btn btn-sm btn-outline-light" href="{{ route('purchase-orders.create') }}">Create</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-flush mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>PO No</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPurchaseOrders as $po)
                                    <tr>
                                        <td class="text-gray-900">{{ ams_date($po->po_date) }}</td>
                                        <td class="text-gray-900">
                                            <a href="{{ route('purchase-orders.edit', $po) }}">{{ $po->po_no }}</a>
                                        </td>
                                        <td class="text-gray-900">
                                            <a href="{{ route('suppliers.show', $po->supplier_id) }}">{{ $po->supplier?->name }}</a>
                                        </td>
                                        <td class="text-gray-900 text-end">{{ number_format((float) $po->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-600">No purchase orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

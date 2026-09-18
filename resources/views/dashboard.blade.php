@extends('template.layout')
@section('title', 'Dashboard')

@section('content')
    <div class="pb-4 ams-dashboard">
        <div class="db-welcome">
            <div>
                <h2>Welcome back, {{ $userName }}</h2>
                <p>Overview of invoices, stock, cash, and salesman orders.</p>
            </div>
            <div class="db-welcome-meta">
                <div class="db-chip"><span>Today</span>{{ now()->format('d M Y') }}</div>
                <div class="db-chip"><span>Customers</span>{{ number_format((int) $customersCount) }}</div>
                <div class="db-chip"><span>Cash</span>{{ number_format((float) $cashBalance, 2) }}</div>
                <div class="db-chip"><span>Bank</span>{{ number_format((float) $totalBankBalance, 2) }}</div>
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

        <div id="amsLiveOrdersPanel" class="db-panel mb-3" @if(($pendingSalesOrders ?? collect())->isEmpty()) hidden @endif>
            <div class="db-panel-head">
                <h4>New salesman orders</h4>
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
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('customers.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Customers</p>
                        <div class="db-card-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $customersCount) }}</h3>
                    <p class="db-card-note">Receivable {{ number_format((float) $totalCustomerReceivable, 2) }}</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('suppliers.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Suppliers</p>
                        <div class="db-card-icon orange">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $suppliersCount) }}</h3>
                    <p class="db-card-note">Payable {{ number_format((float) $totalSupplierPayable, 2) }}</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('stock-items.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Stock items</p>
                        <div class="db-card-icon slate">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $stockItemsCount) }}</h3>
                    <p class="db-card-note">Inventory catalog</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('invoices.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Invoices</p>
                        <div class="db-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $invoicesCount) }}</h3>
                    <p class="db-card-note">All sales invoices</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('bank-accounts.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Bank balance</p>
                        <div class="db-card-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 .5 0 4v1h16V4L8 .5zM1 6v7h2V6H1zm4 0v7h2V6H5zm4 0v7h2V6H9zm4 0v7h2V6h-2zM0 14v1.5h16V14H0z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((float) $totalBankBalance, 2) }}</h3>
                    <p class="db-card-note">{{ number_format((int) $bankAccountsCount) }} accounts</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('cash-vouchers.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Cash balance</p>
                        <div class="db-card-icon sand">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((float) $cashBalance, 2) }}</h3>
                    <p class="db-card-note">Received {{ number_format((float) $totalCashReceived, 2) }}</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('expense-accounts.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Expenses</p>
                        <div class="db-card-icon rose">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .556.146l.5.5A.5.5 0 0 1 15 2v13.5a.5.5 0 0 1-.73.447L13 15.14l-.646.647a.5.5 0 0 1-.708 0L11 15.139l-.646.647a.5.5 0 0 1-.708 0L9 15.139l-.646.647a.5.5 0 0 1-.708 0L7 15.139l-.646.647a.5.5 0 0 1-.708 0L5 15.139l-.646.647a.5.5 0 0 1-.708 0L3 15.139l-.646.647A.5.5 0 0 1 1 15.5V2a.5.5 0 0 1 .053-.224l.5-.5z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((float) $totalExpenses, 2) }}</h3>
                    <p class="db-card-note">{{ number_format((int) $expenseAccountsCount) }} expense accounts</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('purchase-orders.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Purchase orders</p>
                        <div class="db-card-icon orange">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
                        </div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $purchaseOrdersCount) }}</h3>
                    <p class="db-card-note">Value {{ number_format((float) $purchaseOrdersTotal, 2) }}</p>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Cash overview</h4>
                        <a href="{{ route('cash-vouchers.index') }}" class="db-link">View all</a>
                    </div>
                    <div class="db-panel-body">
                        <div class="db-cash">
                            <div class="db-cash-item">
                                <span>Received today</span>
                                <strong>{{ number_format((float) $todayCashReceive, 2) }}</strong>
                            </div>
                            <div class="db-cash-item">
                                <span>Paid today</span>
                                <strong>{{ number_format((float) $todayCashPayment, 2) }}</strong>
                            </div>
                            <div class="db-cash-item">
                                <span>Received month</span>
                                <strong>{{ number_format((float) $monthCashReceive, 2) }}</strong>
                            </div>
                            <div class="db-cash-item">
                                <span>Paid month</span>
                                <strong>{{ number_format((float) $monthCashPayment, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Quick actions</h4>
                    </div>
                    <div class="db-panel-body">
                        <div class="db-actions">
                            <a class="db-action" href="{{ route('invoices.create') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                                New invoice
                            </a>
                            <a class="db-action" href="{{ route('customers.create') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/></svg>
                                Add customer
                            </a>
                            <a class="db-action" href="{{ route('purchase-orders.create') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
                                New purchase order
                            </a>
                            <a class="db-action" href="{{ route('cash-vouchers.create') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4z"/></svg>
                                Cash voucher
                            </a>
                            <a class="db-action" href="{{ route('stock-items.index') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/></svg>
                                Stock items
                            </a>
                            <a class="db-action" href="{{ route('reports.index') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0v-1zm6-4a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V7zM7 9a1 1 0 0 1 2 0v3a1 1 0 1 1-2 0V9z"/><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/></svg>
                                Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Snapshot</h4>
                    </div>
                    <div class="db-panel-body">
                        <table class="db-table">
                            <tbody>
                                <tr>
                                    <td>Customers</td>
                                    <td class="text-end"><strong>{{ number_format((int) $customersCount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Suppliers</td>
                                    <td class="text-end"><strong>{{ number_format((int) $suppliersCount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Stock items</td>
                                    <td class="text-end"><strong>{{ number_format((int) $stockItemsCount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Invoices</td>
                                    <td class="text-end"><strong>{{ number_format((int) $invoicesCount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Purchase orders</td>
                                    <td class="text-end"><strong>{{ number_format((int) $purchaseOrdersCount) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Net cash today</td>
                                    <td class="text-end"><strong>{{ number_format((float) $todayCashReceive - (float) $todayCashPayment, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Recent purchase orders</h4>
                        <a href="{{ route('purchase-orders.index') }}" class="db-link">Open list</a>
                    </div>
                    <div class="db-panel-body">
                        @if($recentPurchaseOrders->count())
                            <div class="table-responsive">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>PO No</th>
                                            <th>Supplier</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentPurchaseOrders as $po)
                                            <tr>
                                                <td>{{ ams_date($po->po_date) }}</td>
                                                <td><strong><a href="{{ route('purchase-orders.edit', $po) }}">{{ $po->po_no }}</a></strong></td>
                                                <td>
                                                    @if($po->supplier)
                                                        <a href="{{ route('suppliers.show', $po->supplier_id) }}">{{ $po->supplier->name }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-end">{{ number_format((float) $po->total_amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="db-empty">No purchase orders yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('template.layout')
@section('title', 'Reports')

@section('content')
    @php
        $reportHint = function (string $filter): string {
            return match ($filter) {
                'party-ledger' => 'Choose party and dates',
                'cash-register', 'journal-report', 'dates' => 'Choose date range',
                'salesman-commission', 'dates_salesman' => 'Choose salesman and dates',
                'month' => 'Choose month',
                default => 'Open print sheet',
            };
        };
        $reportIcon = function (string $slug): string {
            return match ($slug) {
                'account-receivables', 'payables' => 'M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zM8.5 9.5a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM5.854 8.146a.5.5 0 1 0-.708.708L6.293 10l-1.147 1.146a.5.5 0 0 0 .708.708l1.5-1.5a.5.5 0 0 0 0-.708l-1.5-1.5z',
                'party-ledger' => 'M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
                'cash-register' => 'M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4z',
                'journal-report' => 'M5 0h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v1H3V2a2 2 0 0 1 2-2z M3 5h10v1H3V5zm0 3h10v1H3V8zm0 3h7v1H3v-1z',
                'customers' => 'M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
                'stock', 'stock-category', 'low-stock' => 'M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z',
                'sales-invoices', 'sales-orders', 'sales-returns' => 'M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z',
                'salesman-report', 'salesman-commission' => 'M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2 1a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h4z',
                'purchase-orders', 'purchase-returns' => 'M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z',
                'expenses' => 'M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .556.146l.5.5A.5.5 0 0 1 15 2v13.5a.5.5 0 0 1-.73.447L13 15.14l-.646.647a.5.5 0 0 1-.708 0L11 15.139l-.646.647a.5.5 0 0 1-.708 0L9 15.139l-.646.647a.5.5 0 0 1-.708 0L7 15.139l-.646.647a.5.5 0 0 1-.708 0L5 15.139l-.646.647a.5.5 0 0 1-.708 0L3 15.139l-.646.647A.5.5 0 0 1 1 15.5V2a.5.5 0 0 1 .053-.224l.5-.5z',
                'monthly-sheet' => 'M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z',
                default => 'M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z',
            };
        };
    @endphp
    <div class="pb-4 reports-page">
        <div class="db-welcome">
            <div>
                <h2>{{ __('Reports') }}</h2>
                <p>Print ledgers, stock, sales, purchases, and period sheets.</p>
            </div>
            <div class="db-welcome-meta">
                <div class="db-chip"><span>Available</span>{{ $reports->count() }} reports</div>
            </div>
        </div>

        @foreach($reports->groupBy('group') as $group => $groupReports)
            <div class="db-panel mb-3">
                <div class="db-panel-head">
                    <h4>{{ __($group) }}</h4>
                    <span class="text-muted small">{{ $groupReports->count() }}</span>
                </div>
                <div class="db-panel-body">
                    <div class="row g-3">
                        @foreach($groupReports as $report)
                            @php
                                $reportModalId = match ($report['filter']) {
                                    'party-ledger' => 'partyLedgerModal',
                                    'cash-register' => 'cashRegisterModal',
                                    'journal-report' => 'journalReportModal',
                                    'salesman-commission' => 'salesmanCommissionModal',
                                    'dates', 'dates_salesman', 'month' => 'reportFilterModal',
                                    default => null,
                                };
                            @endphp
                            <div class="col-12 col-sm-6 col-xl-4">
                                @if($reportModalId)
                                    <button type="button"
                                        class="report-tile report-tile-{{ $report['color'] }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $reportModalId }}"
                                        data-report-url="{{ route('reports.show', $report['slug']) }}"
                                        data-report-title="{{ __($report['title']) }}"
                                        data-report-filter="{{ $report['filter'] }}">
                                        <span class="report-tile-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="{{ $reportIcon($report['slug']) }}"/></svg>
                                        </span>
                                        <span class="report-tile-copy">
                                            <span class="report-tile-title">{{ __($report['title']) }}</span>
                                            <span class="report-tile-hint">{{ $reportHint($report['filter']) }}</span>
                                        </span>
                                        <span class="report-tile-go" aria-hidden="true">→</span>
                                    </button>
                                @else
                                    <a href="{{ route('reports.show', $report['slug']) }}"
                                        target="_blank"
                                        class="report-tile report-tile-{{ $report['color'] }}">
                                        <span class="report-tile-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="{{ $reportIcon($report['slug']) }}"/></svg>
                                        </span>
                                        <span class="report-tile-copy">
                                            <span class="report-tile-title">{{ __($report['title']) }}</span>
                                            <span class="report-tile-hint">{{ $reportHint($report['filter']) }}</span>
                                        </span>
                                        <span class="report-tile-go" aria-hidden="true">→</span>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="partyLedgerModal" tabindex="-1" aria-labelledby="partyLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="partyLedgerForm" method="get" action="{{ route('reports.party-ledger') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="partyLedgerModalLabel">{{ __('Party Ledger') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pl_account_type" class="form-label">Account Type</label>
                            <select name="account_type" id="pl_account_type" class="form-select" required>
                                <option value="">Select type…</option>
                                <option value="customer">Customer</option>
                                <option value="supplier">Supplier</option>
                                <option value="expense">Expense Account</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pl_account_id" class="form-label">Account</label>
                            <select name="account_id" id="pl_account_id" class="form-select" required disabled>
                                <option value="">Select account type first…</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pl_from_date" class="form-label">From Date</label>
                                <x-ams-date-input name="from_date" id="pl_from_date" :value="now()->startOfMonth()" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pl_to_date" class="form-label">To Date</label>
                                <x-ams-date-input name="to_date" id="pl_to_date" :value="now()" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">View Ledger</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashRegisterModal" tabindex="-1" aria-labelledby="cashRegisterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="get" action="{{ route('reports.cash-register') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashRegisterModalLabel">{{ __('Cash Register') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Lists every voucher: customer, supplier, expense, and other — cash receive, cash payment, and bank payments.
                        </p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cr_from_date" class="form-label">From Date</label>
                                <x-ams-date-input name="from_date" id="cr_from_date" :value="now()->startOfMonth()" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cr_to_date" class="form-label">To Date</label>
                                <x-ams-date-input name="to_date" id="cr_to_date" :value="now()" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">View Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="journalReportModal" tabindex="-1" aria-labelledby="journalReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="get" action="{{ route('reports.journal-report') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="journalReportModalLabel">{{ __('Journal Report') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            General journal vouchers with all debit and credit lines for the selected period.
                        </p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="jr_from_date" class="form-label">From Date</label>
                                <x-ams-date-input name="from_date" id="jr_from_date" :value="now()->startOfMonth()" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="jr_to_date" class="form-label">To Date</label>
                                <x-ams-date-input name="to_date" id="jr_to_date" :value="now()" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:#e85d82;border-color:#e85d82;">View Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="salesmanCommissionModal" tabindex="-1" aria-labelledby="salesmanCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="get" action="{{ route('reports.salesman-commission') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="salesmanCommissionModalLabel">{{ __('Salesman Commission') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Confirmed salesman invoices with company retain and commission snapshots.
                        </p>
                        <div class="mb-3">
                            <label for="sc_salesman_id" class="form-label">Salesman</label>
                            <select name="salesman_id" id="sc_salesman_id" class="form-select">
                                <option value="">All salesmen</option>
                                @foreach($salesmen as $salesman)
                                    <option value="{{ $salesman->id }}">{{ $salesman->name }}{{ $salesman->city ? ' · '.$salesman->city : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sc_from_date" class="form-label">From Date</label>
                                <x-ams-date-input name="from_date" id="sc_from_date" :value="now()->startOfMonth()" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sc_to_date" class="form-label">To Date</label>
                                <x-ams-date-input name="to_date" id="sc_to_date" :value="now()" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">View Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-labelledby="reportFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="reportFilterForm" method="get" action="" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reportFilterModalLabel">Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 d-none" id="rf_salesman_wrap">
                            <label for="rf_salesman_id" class="form-label">Salesman</label>
                            <select name="salesman_id" id="rf_salesman_id" class="form-select">
                                <option value="">All salesmen</option>
                                @foreach($salesmen as $salesman)
                                    <option value="{{ $salesman->id }}">{{ $salesman->name }}{{ $salesman->city ? ' · '.$salesman->city : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="rf_month_wrap">
                            <label for="rf_month" class="form-label">Month</label>
                            <input type="month" name="month" id="rf_month" class="form-control" value="{{ now()->format('Y-m') }}">
                        </div>
                        <div class="row" id="rf_dates_wrap">
                            <div class="col-md-6 mb-3">
                                <label for="rf_from_date" class="form-label">From Date</label>
                                <x-ams-date-input name="from_date" id="rf_from_date" :value="now()->startOfMonth()" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rf_to_date" class="form-label">To Date</label>
                                <x-ams-date-input name="to_date" id="rf_to_date" :value="now()" required />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">View Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@section('scripts')
    <script>
        (function () {
            const accountsUrl = @json(route('reports.party-ledger.accounts'));
            const typeSelect = document.getElementById('pl_account_type');
            const accountSelect = document.getElementById('pl_account_id');

            if (typeSelect && accountSelect) {
                typeSelect.addEventListener('change', async function () {
                    const type = typeSelect.value;
                    accountSelect.innerHTML = '<option value="">Loading…</option>';
                    accountSelect.disabled = true;

                    if (!type) {
                        accountSelect.innerHTML = '<option value="">Select account type first…</option>';
                        return;
                    }

                    try {
                        const response = await fetch(accountsUrl + '?account_type=' + encodeURIComponent(type), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const accounts = await response.json();

                        if (!accounts.length) {
                            accountSelect.innerHTML = '<option value="">No accounts found</option>';
                            return;
                        }

                        accountSelect.innerHTML = '<option value="">Select account…</option>';
                        accounts.forEach(function (account) {
                            const option = document.createElement('option');
                            option.value = account.id;
                            option.textContent = account.name;
                            accountSelect.appendChild(option);
                        });
                        accountSelect.disabled = false;
                    } catch (e) {
                        accountSelect.innerHTML = '<option value="">Failed to load accounts</option>';
                    }
                });
            }

            const filterModal = document.getElementById('reportFilterModal');
            const filterForm = document.getElementById('reportFilterForm');
            const filterTitle = document.getElementById('reportFilterModalLabel');
            const salesmanWrap = document.getElementById('rf_salesman_wrap');
            const monthWrap = document.getElementById('rf_month_wrap');
            const datesWrap = document.getElementById('rf_dates_wrap');
            const monthInput = document.getElementById('rf_month');
            const fromInput = document.getElementById('rf_from_date');
            const toInput = document.getElementById('rf_to_date');

            if (filterModal && filterForm) {
                filterModal.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;

                    const filter = trigger.getAttribute('data-report-filter') || 'dates';
                    filterForm.action = trigger.getAttribute('data-report-url') || '';
                    filterTitle.textContent = trigger.getAttribute('data-report-title') || 'Report';

                    const showSalesman = filter === 'dates_salesman';
                    const showMonth = filter === 'month';

                    salesmanWrap.classList.toggle('d-none', !showSalesman);
                    monthWrap.classList.toggle('d-none', !showMonth);
                    datesWrap.classList.toggle('d-none', showMonth);

                    monthInput.required = showMonth;
                    fromInput.required = !showMonth;
                    toInput.required = !showMonth;
                    monthInput.disabled = !showMonth;
                    fromInput.disabled = showMonth;
                    toInput.disabled = showMonth;
                    document.getElementById('rf_salesman_id').disabled = !showSalesman;
                });
            }
        })();
    </script>
@endsection

@extends('template.layout')
@section('title', 'Reports')

@section('content')
    <div class="pb-4">
        <div class="py-3 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Reports</h1>
        </div>

        <div class="card p-4 shadow-sm ams-stat-card ams-reports-card">
            <div class="row g-3">
                @foreach($reports as $report)
                    @php
                        $reportModalId = match ($report['slug']) {
                            'party-ledger' => 'partyLedgerModal',
                            'cash-register' => 'cashRegisterModal',
                            'journal-report' => 'journalReportModal',
                            default => null,
                        };
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4">
                        @if($reportModalId)
                            <button type="button"
                                class="report-tile report-tile-{{ $report['color'] }} w-100 border-0"
                                data-bs-toggle="modal"
                                data-bs-target="#{{ $reportModalId }}">
                                <span class="report-tile-title">{{ $report['title'] }}</span>
                                <span class="report-tile-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M4.5 6a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm3 0a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm3 0a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                        <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm13 10H1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8z"/>
                                    </svg>
                                </span>
                            </button>
                        @else
                            <a href="{{ route('reports.show', $report['slug']) }}"
                                @if($report['slug'] === 'account-receivables') target="_blank" @endif
                                class="report-tile report-tile-{{ $report['color'] }}">
                                <span class="report-tile-title">{{ $report['title'] }}</span>
                                <span class="report-tile-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M4.5 6a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm3 0a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm3 0a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                        <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm13 10H1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8z"/>
                                    </svg>
                                </span>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="partyLedgerModal" tabindex="-1" aria-labelledby="partyLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="partyLedgerForm" method="get" action="{{ route('reports.party-ledger') }}" target="_blank">
                    <div class="modal-header">
                        <h5 class="modal-title" id="partyLedgerModalLabel">Party Ledger</h5>
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
                        <h5 class="modal-title" id="cashRegisterModalLabel">Cash Register</h5>
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
                        <h5 class="modal-title" id="journalReportModalLabel">Journal Report</h5>
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
@endpush

@section('scripts')
    <script>
        (function () {
            const accountsUrl = @json(route('reports.party-ledger.accounts'));
            const typeSelect = document.getElementById('pl_account_type');
            const accountSelect = document.getElementById('pl_account_id');

            if (!typeSelect || !accountSelect) return;

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
        })();
    </script>
@endsection

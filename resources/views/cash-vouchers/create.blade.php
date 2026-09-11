@extends('template.layout')
@section('title', 'Create Cash Voucher')

@section('content')
    @php
        $initialType = old('type', request('type'));
        $hasInitialType = in_array($initialType, ['receive', 'payment'], true);
    @endphp

    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0" id="createVoucherTitle">Create Cash Voucher</h1>
                <p class="mb-0" id="createVoucherSubtitle">Select voucher type to continue.</p>
            </div>
            <div>
                <a href="{{ route('cash-vouchers.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4 {{ $hasInitialType ? '' : 'd-none' }}" id="cashVoucherFormCard">
            @error('type')
                <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror

            <div class="alert py-2 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $initialType === 'payment' ? 'alert-danger' : ($initialType === 'receive' ? 'alert-success' : 'alert-secondary') }}"
                id="selectedVoucherTypeBanner">
                <div>
                    <span class="small text-uppercase fw-semibold">Selected Type</span>
                    <div class="fw-bold" id="selectedVoucherTypeLabel">
                        @if ($initialType === 'receive')
                            Cash Receive
                        @elseif ($initialType === 'payment')
                            Cash Payment
                        @else
                            —
                        @endif
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="changeVoucherTypeBtn">Change Type</button>
            </div>

            <form method="post" action="{{ route('cash-vouchers.store') }}" id="cashVoucherCreateForm">
                @csrf
                @include('cash-vouchers._form', ['showTypeField' => false])
                <button class="btn btn-gray-800" type="submit" id="cashVoucherSaveBtn" {{ $hasInitialType ? '' : 'disabled' }}>Save</button>
            </form>
        </div>
    </div>

    @push('modals')
        <div class="modal fade" id="voucherTypeModal" tabindex="-1" aria-labelledby="voucherTypeModalLabel" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="voucherTypeModalLabel">Select Voucher Type</h5>
                            <p class="mb-0 small text-muted">Choose whether you are receiving or paying cash.</p>
                        </div>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <button type="button" class="btn btn-success w-100 py-4 voucher-type-choice" data-type="receive">
                                    <span class="d-block fs-5 fw-bold">Cash Receive</span>
                                    <span class="small">Money coming in</span>
                                </button>
                            </div>
                            <div class="col-sm-6">
                                <button type="button" class="btn btn-danger w-100 py-4 voucher-type-choice" data-type="payment">
                                    <span class="d-block fs-5 fw-bold">Cash Payment</span>
                                    <span class="small">Money going out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <a href="{{ route('cash-vouchers.index') }}" class="btn btn-link text-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@section('scripts')
    @stack('page_scripts')
    <script>
        (function () {
            const typeLabels = {
                receive: 'Cash Receive',
                payment: 'Cash Payment',
            };
            const hasInitialType = @json($hasInitialType);
            const initialType = @json($hasInitialType ? $initialType : null);

            const modalEl = document.getElementById('voucherTypeModal');
            const typeModal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const formCard = document.getElementById('cashVoucherFormCard');
            const typeInput = document.getElementById('voucher_type');
            const typeLabel = document.getElementById('selectedVoucherTypeLabel');
            const typeBanner = document.getElementById('selectedVoucherTypeBanner');
            const pageTitle = document.getElementById('createVoucherTitle');
            const pageSubtitle = document.getElementById('createVoucherSubtitle');
            const saveBtn = document.getElementById('cashVoucherSaveBtn');

            function applyVoucherType(type) {
                if (!typeLabels[type] || !typeInput) {
                    return;
                }

                typeInput.value = type;
                typeLabel.textContent = typeLabels[type];
                pageTitle.textContent = 'Create ' + typeLabels[type];
                pageSubtitle.textContent = 'Fill in the voucher details below.';
                saveBtn.disabled = false;

                typeBanner.classList.remove('alert-success', 'alert-danger', 'alert-secondary');
                typeBanner.classList.add(type === 'receive' ? 'alert-success' : 'alert-danger');

                formCard.classList.remove('d-none');
            }

            document.querySelectorAll('.voucher-type-choice').forEach(function (button) {
                button.addEventListener('click', function () {
                    applyVoucherType(button.dataset.type);
                    if (typeModal) {
                        typeModal.hide();
                    }
                });
            });

            document.getElementById('changeVoucherTypeBtn')?.addEventListener('click', function () {
                if (typeModal) {
                    typeModal.show();
                }
            });

            if (hasInitialType && initialType) {
                applyVoucherType(initialType);
            } else if (typeModal) {
                typeModal.show();
            }
        })();
    </script>
@endsection

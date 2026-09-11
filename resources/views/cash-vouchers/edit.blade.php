@extends('template.layout')
@section('title', 'Edit Cash Voucher')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Cash Voucher</h1>
                <p class="mb-0">Update cash voucher.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cash-vouchers.print', $cashVoucher) }}" target="_blank"
                    class="btn btn-sm btn-outline-primary">Print Invoice</a>
                <a href="{{ route('cash-vouchers.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('cash-vouchers.update', $cashVoucher) }}">
                @csrf
                @method('put')
                @include('cash-vouchers._form')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @stack('page_scripts')
@endsection


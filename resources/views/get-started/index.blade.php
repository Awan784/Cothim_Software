@extends('template.layout')
@section('title', 'Get started')

@section('content')
    <div class="ams-get-started">
        <p class="ams-welcome">{{ $firstName }}, welcome to {{ $shell['company'] ?? config('ams.product_name') }}!</p>
        <h1 class="ams-get-started-title">What do you want to do first?</h1>

        <div class="row g-4 mt-1">
            <div class="col-md-4">
                <a href="{{ route('invoices.create') }}" class="ams-start-card">
                    <span class="ams-start-time">2 min</span>
                    <div class="ams-start-art ams-start-art--invoice">
                        <span class="ams-qr-mark"></span>
                    </div>
                    <h2>Create your first invoice</h2>
                    <p>Get paid faster with VAT and a ZATCA Phase 1 QR code.</p>
                    @if($hasInvoice)
                        <span class="ams-start-done">Done</span>
                    @endif
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('inbox.create') }}" class="ams-start-card">
                    <span class="ams-start-time">1 min</span>
                    <div class="ams-start-art ams-start-art--expense"></div>
                    <h2>Track your expenses</h2>
                    <p>Upload a PDF or image to Inbox, then post it as an expense or bill.</p>
                    @if($hasInbox)
                        <span class="ams-start-done">Done</span>
                    @endif
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('bank-accounts.index') }}" class="ams-start-card">
                    <span class="ams-start-time">2 min</span>
                    <div class="ams-start-art ams-start-art--bank"></div>
                    <h2>Import a bank statement</h2>
                    <p>Add a bank account, then drop statements into Inbox.</p>
                </a>
            </div>
        </div>

        @if($companyVat === '')
            <div class="ams-setup-hint mt-4">
                Add your KSA VAT number in
                <a href="{{ route('settings.company') }}">Settings</a>
                so issued invoices include a valid ZATCA QR.
            </div>
        @endif
    </div>
@endsection

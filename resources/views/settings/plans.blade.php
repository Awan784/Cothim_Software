@extends('template.layout')
@section('title', 'Plans')

@section('content')
    <div class="pb-4">
        <div class="py-4">
            <h1 class="h4 mb-1">Plans</h1>
            <p class="mb-0 text-muted">Current plan: <strong>{{ ucfirst($plan) }}</strong>@if($plan === 'trial' && $daysLeft !== null) · {{ max(0, $daysLeft) }} days left @endif</p>
        </div>
        <div class="row g-3">
            @foreach([
                'trial' => ['Trial', '14-day free trial. Invoices, Inbox, and VAT QR included.'],
                'plus' => ['Plus', 'For growing teams. Same modules, ready for ZATCA Phase 2.'],
                'pro' => ['Pro', 'Full accounting, bills, inventory, and assistant.'],
            ] as $key => $card)
                <div class="col-md-4">
                    <div class="card p-4 h-100 {{ $plan === $key ? 'border-primary' : '' }}">
                        <h2 class="h5">{{ $card[0] }}</h2>
                        <p class="text-muted">{{ $card[1] }}</p>
                        <form method="post" action="{{ route('settings.plans.select') }}">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $key }}">
                            <button class="btn {{ $plan === $key ? 'btn-primary' : 'btn-outline-primary' }}" type="submit">
                                {{ $plan === $key ? 'Current plan' : 'Select' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

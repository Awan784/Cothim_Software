@extends('marketing.layout')
@section('title', __('Pricing').' — '.config('ams.product_name'))

@section('content')
<section class="wafi-section wafi-pricing">
    <div class="wafi-wrap">
        <h1>{{ __('Simple plans for Saudi teams') }}</h1>
        <p class="wafi-section-lead">{{ __('pricing.lead') }}</p>
        <div class="wafi-plans">
            <article>
                <h3>{{ __('Trial') }}</h3>
                <p class="wafi-price">{{ __('14 days') }}</p>
                <ul>
                    <li>{{ __('ZATCA tax invoices') }}</li>
                    <li>{{ __('Inbox') }}</li>
                    <li>{{ __('WhatsApp') }}</li>
                </ul>
                <a class="wafi-btn wafi-btn-orange" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
            </article>
            <article class="is-featured">
                <h3>{{ __('Plus') }}</h3>
                <p class="wafi-price">{{ __('Growing teams') }}</p>
                <ul>
                    <li>{{ __('feat.invoices') }}</li>
                    <li>{{ __('Customers & suppliers') }}</li>
                    <li>{{ __('Reports') }}</li>
                </ul>
                <a class="wafi-btn wafi-btn-indigo" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
            </article>
            <article>
                <h3>{{ __('Pro') }}</h3>
                <p class="wafi-price">{{ __('Full accounting') }}</p>
                <ul>
                    <li>{{ __('Inventory') }}</li>
                    <li>{{ __('Cash, bank & journals') }}</li>
                    <li>{{ __('feat.whatsapp') }}</li>
                </ul>
                <a class="wafi-btn wafi-btn-ghost" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
            </article>
        </div>
    </div>
</section>
@endsection

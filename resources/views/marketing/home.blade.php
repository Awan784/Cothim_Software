@extends('marketing.layout')
@section('title', config('ams.product_name').' – '.__('Accounting and ZATCA-compliant e-invoicing software for Saudi businesses'))

@section('content')
<section class="wafi-hero">
    <div class="wafi-wrap wafi-hero-inner">
        <div>
            <p class="wafi-kicker">{{ __('Ready for ZATCA Phase 1 · Built for KSA') }}</p>
            <h1>{{ __('Accounting and ZATCA-compliant e-invoicing software for Saudi businesses') }}</h1>
            <p class="wafi-lead">{{ __('hero.lead') }}</p>
            <div class="wafi-hero-actions">
                <a class="wafi-btn wafi-btn-orange wafi-btn-lg" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
                <a class="wafi-btn wafi-btn-ghost wafi-btn-lg" href="#demo">{{ __('Watch demo') }}</a>
            </div>
            <p class="wafi-note">{{ __('14 days free trial — no credit card needed') }}</p>
        </div>
        <div class="wafi-hero-card">
            <img src="{{ asset('images/marketing/wafi-hero-app.png') }}" alt="{{ config('ams.product_name') }} dashboard">
        </div>
    </div>
</section>

<section class="wafi-trust">
    <div class="wafi-wrap wafi-trust-inner">
        <p>{{ __('Trusted by Saudi teams') }}</p>
        <strong>{{ __('4.8 average rating from our customers') }}</strong>
    </div>
</section>

<section class="wafi-split" id="zatca">
    <div class="wafi-wrap wafi-split-inner">
        <img src="{{ asset('images/marketing/wafi-invoice.png') }}" alt="{{ __('ZATCA tax invoices') }}">
        <div>
            <p class="wafi-kicker">Fatoora · ZATCA</p>
            <h2>{{ __('ZATCA tax invoices') }}</h2>
            <p>{{ __('feat.invoices') }}</p>
            <a class="wafi-btn wafi-btn-indigo" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
        </div>
    </div>
</section>

<section class="wafi-section" id="product">
    <div class="wafi-wrap">
        <h2>{{ __('Everything you need to run the books') }}</h2>
        <p class="wafi-section-lead">{{ __('features.lead') }}</p>
        <div class="wafi-grid">
            <article><h3>{{ __('ZATCA tax invoices') }}</h3><p>{{ __('feat.invoices') }}</p></article>
            <article><h3>{{ __('Inbox') }}</h3><p>{{ __('feat.inbox') }}</p></article>
            <article><h3>{{ __('Customers & suppliers') }}</h3><p>{{ __('feat.parties') }}</p></article>
            <article><h3>{{ __('Cash, bank & journals') }}</h3><p>{{ __('feat.cash') }}</p></article>
            <article><h3>{{ __('Inventory') }}</h3><p>{{ __('feat.stock') }}</p></article>
            <article><h3>{{ __('WhatsApp') }}</h3><p>{{ __('feat.whatsapp') }}</p></article>
        </div>
    </div>
</section>

<section class="wafi-demo" id="demo">
    <div class="wafi-wrap">
        <h2>{{ __('See Wafi in action') }}</h2>
        <p class="wafi-section-lead">{{ __('video.lead') }}</p>
        <div class="wafi-player" id="wafiPlayer">
            <img id="wafiPlayerFrame" src="{{ asset('images/marketing/wafi-hero-app.png') }}" alt="{{ __('Watch demo') }}">
            <button type="button" class="wafi-play" id="wafiPlay" aria-label="{{ __('Watch demo') }}">▶</button>
            <div class="wafi-player-bar">
                <span>{{ config('ams.product_name') }} · {{ __('Watch demo') }}</span>
                <a class="wafi-btn wafi-btn-orange" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="wafi-split wafi-split-alt" id="industries">
    <div class="wafi-wrap wafi-split-inner">
        <div>
            <h2>{{ __('Built for Saudi industries') }}</h2>
            <div class="wafi-pills">
                <span>{{ __('ind.retail') }}</span>
                <span>{{ __('ind.services') }}</span>
                <span>{{ __('ind.trading') }}</span>
                <span>{{ __('ind.food') }}</span>
            </div>
            <a class="wafi-btn wafi-btn-ghost" href="{{ route('pricing') }}">{{ __('See pricing') }}</a>
        </div>
        <img src="{{ asset('images/marketing/wafi-team.png') }}" alt="{{ __('Built for Saudi industries') }}">
    </div>
</section>

<section class="wafi-city">
    <img src="{{ asset('images/marketing/wafi-riyadh.png') }}" alt="Riyadh">
    <div class="wafi-city-copy">
        <h2>{{ __('Create your company workspace and try Plus for 14 days.') }}</h2>
        <a class="wafi-btn wafi-btn-orange wafi-btn-lg" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
    </div>
</section>
<script>
(function () {
    var frames = [
        @json(asset('images/marketing/wafi-hero-app.png')),
        @json(asset('images/marketing/wafi-invoice.png')),
        @json(asset('images/marketing/wafi-team.png')),
        @json(asset('images/marketing/wafi-riyadh.png'))
    ];
    var img = document.getElementById('wafiPlayerFrame');
    var btn = document.getElementById('wafiPlay');
    var i = 0, timer = null;
    function tick() { i = (i + 1) % frames.length; img.src = frames[i]; }
    btn.addEventListener('click', function () {
        if (timer) { clearInterval(timer); timer = null; btn.textContent = '▶'; return; }
        timer = setInterval(tick, 2200); btn.textContent = '❚❚'; tick();
    });
})();
</script>
@endsection

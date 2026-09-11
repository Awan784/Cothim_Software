<!DOCTYPE html>
<html lang="{{ wafi_locale() }}" dir="{{ wafi_is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', config('ams.product_name').' – '.__('Accounting and ZATCA-compliant e-invoicing software for Saudi businesses'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('hero.lead') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
    @if(wafi_is_rtl())
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/wafi-site.css') }}?v=5">
</head>
<body class="wafi-site {{ wafi_is_rtl() ? 'is-rtl' : '' }}">
    <div class="wafi-topbar">
        <div class="wafi-wrap wafi-topbar-inner">
            <span>{{ __('Ready for ZATCA Phase 1 · Built for KSA') }}</span>
            <a href="{{ route('home') }}#zatca">{{ __('Learn more') }}</a>
        </div>
    </div>
    <header class="wafi-nav">
        <div class="wafi-wrap wafi-nav-inner">
            <a class="wafi-logo" href="{{ route('home') }}">
                <span class="wafi-logo-mark">W</span>
                {{ config('ams.product_name') }}
            </a>
            <nav class="wafi-nav-links">
                <a href="{{ route('home') }}#product">{{ __('Products') }}</a>
                <a href="{{ route('home') }}#industries">{{ __('Industries') }}</a>
                <a href="{{ route('pricing') }}">{{ __('Pricing') }}</a>
                <a href="{{ route('locale.switch', wafi_is_rtl() ? 'en' : 'ar') }}">{{ wafi_is_rtl() ? 'English' : 'عربي' }}</a>
                @auth
                    @if(auth()->user()->isPlatformAdmin())
                        <a href="{{ route('platform.home') }}">{{ __('Platform admin') }}</a>
                    @endif
                    <a href="{{ route('get-started') }}">{{ __('Open app') }}</a>
                @else
                    <a href="{{ route('login') }}">{{ __('Login') }}</a>
                    <a class="wafi-btn wafi-btn-orange" href="{{ route('register') }}">{{ __('Start now for free') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    @if(session('success'))
        <div class="wafi-flash">{{ session('success') }}</div>
    @endif

    @yield('content')

    <footer class="wafi-footer">
        <div class="wafi-wrap wafi-footer-grid">
            <div>
                <a class="wafi-logo" href="{{ route('home') }}">
                    <span class="wafi-logo-mark">W</span>
                    {{ config('ams.product_name') }}
                </a>
                <p>{{ __('Accounting and ZATCA-compliant e-invoicing software for Saudi businesses') }}</p>
            </div>
            <div>
                <h4>{{ __('Product') }}</h4>
                <a href="{{ route('home') }}#product">{{ __('Features') }}</a>
                <a href="{{ route('pricing') }}">{{ __('Pricing') }}</a>
                <a href="{{ route('register') }}">{{ __('Free trial') }}</a>
            </div>
            <div>
                <h4>{{ __('Account') }}</h4>
                <a href="{{ route('login') }}">{{ __('Login') }}</a>
                <a href="{{ route('register') }}">{{ __('Start now for free') }}</a>
                <a href="{{ wafi_whatsapp_url(__('Chat on WhatsApp')) }}" target="_blank" rel="noopener">{{ __('WhatsApp') }}</a>
            </div>
        </div>
        <div class="wafi-wrap wafi-copy">
            Copyright © {{ date('Y') }} {{ config('ams.product_name') }}. {{ config('ams.support_phone') }}
        </div>
    </footer>

    <a class="wafi-wa" href="{{ wafi_whatsapp_url(__('Hello, I want to start a Wafi trial for my company in KSA.')) }}" target="_blank" rel="noopener" aria-label="{{ __('Chat on WhatsApp') }}">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true"><path d="M20 3.5A10 10 0 0 0 3.2 17.4L2 22l4.7-1.2A10 10 0 0 0 20 3.5zm-8 16.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-2.8.7.7-2.7-.2-.3A8.2 8.2 0 1 1 12 19.7zm4.5-6.1c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1l-.8 1c-.1.1-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.3-.4c.1-.1.1-.3 0-.4l-.8-1.9c-.2-.5-.4-.4-.5-.4h-.4c-.2 0-.4.1-.6.3s-.7.7-.7 1.8.8 2.1.9 2.2c.1.2 1.5 2.3 3.6 3.2 2.1.9 2.1.6 2.5.6.4 0 1.3-.5 1.5-1s.2-.9.1-1-.2-.2-.4-.3z"/></svg>
        <span>{{ __('Chat on WhatsApp') }}</span>
    </a>
</body>
</html>

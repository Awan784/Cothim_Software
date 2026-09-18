@extends('auth.layout')
@section('title', __('Login').' — '.config('ams.product_name'))

@php
    $product = (string) config('ams.product_name');
    $parts = preg_split('/\s+/', $product, 2) ?: [$product];
    $brandTop = strtoupper($parts[0] ?? $product);
    $brandBottom = strtoupper($parts[1] ?? 'Traders');
@endphp

@section('content')
<div class="login-shell">
    <aside class="brand-plane" aria-label="{{ $product }}">
        <div class="brand-top">
            <div class="brand-mark">
                @if(!empty($brandLogo))
                    <img src="{{ $brandLogo }}" alt="{{ $product }}">
                @else
                    <span class="brand-mark-fallback">{{ mb_strtoupper(mb_substr($product, 0, 1)) }}</span>
                @endif
                <span>Est. Trading</span>
            </div>
        </div>

        <div class="brand-main">
            <h1>{{ $brandTop }} <em>{{ $brandBottom }}</em></h1>
            <p>Secure access to invoices, stock, sales orders, and accounts — one place for your daily business.</p>
        </div>

        <div class="brand-foot">
            <div><strong>Trading</strong> · Homeopathic</div>
            <div><strong>Sales</strong> · Inventory</div>
        </div>
    </aside>

    <main class="form-plane">
        <div class="form-panel">
            <div class="form-kicker">Welcome back</div>
            <h2>Sign in</h2>
            <p>{{ __('Use your company email, or salesman username.') }}</p>

            @if (session('success'))
                <div class="wafi-alert wafi-alert-ok">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="wafi-alert wafi-alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="wafi-alert wafi-alert-warn">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ url('/login') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="login">{{ __('Email or username') }}</label>
                    <div class="field-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 6h16v12H4z"></path>
                            <path d="m4 7 8 6 8-6"></path>
                        </svg>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="you@company.com or username" autocomplete="username">
                    </div>
                </div>
                <div class="field">
                    <label for="password">{{ __('Password') }}</label>
                    <div class="field-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                        </svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                    </div>
                </div>
                <div class="form-meta">
                    <label class="remember">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Remember me
                    </label>
                </div>
                <button type="submit" class="submit-btn">{{ __('Sign in') }}</button>
            </form>
            <p class="form-note">© {{ date('Y') }} {{ $product }} · Confidential access</p>
        </div>
    </main>
</div>
@endsection

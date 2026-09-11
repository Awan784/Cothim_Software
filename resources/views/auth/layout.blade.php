<!DOCTYPE html>
<html lang="{{ wafi_locale() }}" dir="{{ wafi_is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', __('Login').' — '.config('ams.product_name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
    @if(wafi_is_rtl())
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/wafi-site.css') }}?v=6">
</head>
<body class="wafi-site wafi-auth-page {{ wafi_is_rtl() ? 'is-rtl' : '' }}">
    <header class="wafi-nav">
        <div class="wafi-wrap wafi-nav-inner">
            <a class="wafi-logo" href="{{ route('login') }}">
                <span class="wafi-logo-mark">{{ mb_strtoupper(mb_substr(config('ams.product_name'), 0, 1)) }}</span>
                {{ config('ams.product_name') }}
            </a>
        </div>
    </header>

    @yield('content')
</body>
</html>

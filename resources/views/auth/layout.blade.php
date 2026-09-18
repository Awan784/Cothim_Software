<!DOCTYPE html>
<html lang="{{ wafi_locale() }}" dir="{{ wafi_is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', __('Login').' — '.config('ams.product_name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c1b2e">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @if(wafi_is_rtl())
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/wafi-site.css') }}?v=9">
</head>
<body class="wafi-site wafi-auth-page {{ wafi_is_rtl() ? 'is-rtl' : '' }}">
    @yield('content')
</body>
</html>

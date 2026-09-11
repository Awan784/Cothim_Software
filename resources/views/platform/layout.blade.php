<!DOCTYPE html>
<html lang="{{ wafi_locale() }}" dir="{{ wafi_is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title') — {{ __('Platform admin') }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link type="text/css" href="{{ asset('css/volt.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/ams-theme.css') }}?v=4" rel="stylesheet">
    @if(wafi_is_rtl())
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>body { font-family: Cairo, sans-serif; }</style>
    @endif
</head>
<body class="ams-light">
    <nav class="navbar navbar-expand bg-white border-bottom">
        <div class="container-fluid px-4 py-2 d-flex justify-content-between">
            <a class="ams-brand" href="{{ route('platform.home') }}">
                <span class="ams-brand-mark">W</span>
                <span>{{ config('ams.product_name') }} · {{ __('Platform admin') }}</span>
            </a>
            <div class="d-flex gap-2 align-items-center">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard') }}">{{ __('Open app') }}</a>
                <a class="btn btn-sm btn-secondary" href="{{ route('logout') }}">{{ __('Sign out') }}</a>
            </div>
        </div>
    </nav>
    <div class="container py-4" style="max-width:1180px">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <ul class="nav nav-pills mb-4">
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('platform.home') ? 'active' : '' }}" href="{{ route('platform.home') }}">{{ __('Platform admin') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('platform.organizations*') ? 'active' : '' }}" href="{{ route('platform.organizations') }}">{{ __('Companies') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('platform.users') ? 'active' : '' }}" href="{{ route('platform.users') }}">{{ __('All users') }}</a></li>
        </ul>
        @yield('content')
    </div>
</body>
</html>

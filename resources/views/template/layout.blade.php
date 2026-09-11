<!DOCTYPE html>
<html lang="{{ wafi_locale() }}" dir="{{ wafi_is_rtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>@yield('title') — {{ $shell['company'] ?? config('ams.product_name') }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('assets/img/favicon/safari-pinned-tab.svg') }}" color="#ffffff">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#5b5ce2">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link type="text/css" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/notyf/notyf.min.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('vendor/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/volt.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/ams-theme.css') }}?v=4" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @if(wafi_is_rtl())
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
        <style>body.ams-light.is-rtl { font-family: Cairo, sans-serif; }</style>
    @endif
    @stack('styles')
</head>

<body class="ams-light {{ wafi_is_rtl() ? 'is-rtl' : '' }}">
    <nav class="px-4 navbar navbar-light ams-mobile-nav col-12 d-lg-none">
        <a class="navbar-brand me-lg-5" href="{{ route('dashboard') }}">
            <img class="navbar-brand-dark" src="{{ asset('assets/img/brand/light.svg') }}" alt="Logo">
            <img class="navbar-brand-light" src="{{ asset('assets/img/brand/dark.svg') }}" alt="Logo">
        </a>
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-lg-none collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    @include('template.includes.navbar')
    <main class="content">
        <nav class="pb-0 navbar navbar-top navbar-expand ams-topbar ps-0 pe-2">
            <div class="px-0 container-fluid">
                <div class="d-flex justify-content-between w-100" id="navbarSupportedContent">
                    <div class="d-flex align-items-center">
                        <button id="sidebar-toggle"
                            class="sidebar-toggle me-3 btn btn-icon-only d-none d-lg-inline-block align-items-center justify-content-center">
                            <svg class="toggle-icon" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                    <ul class="navbar-nav align-items-center">
                        @if(auth()->user()?->isPlatformAdmin())
                            <li class="nav-item me-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('platform.home') }}">{{ __('Platform admin') }}</a>
                            </li>
                        @endif
                        <li class="nav-item d-none d-md-block me-2">
                            <span class="small text-muted">{{ auth()->user()?->name }}</span>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <a href="{{ route('logout') }}"
                                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
                                <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                                {{ __('Sign out') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="mt-3 row justify-content-center ams-page-content">
            @include('template.includes.alerts')
            <div class="col-12">
                @yield('content')
            </div>
        </div>

    </main>

    @include('template.includes.assistant-widget')

    @stack('modals')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js') }}"></script>
    <script src="{{ asset('vendor/simple-datatables/dist/umd/simple-datatables.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/volt.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/ams-ui.js') }}"></script>
    <script src="{{ asset('js/ams-assistant.js') }}?v=2"></script>
    <script>
        (function () {
            if (!window.simpleDatatables || !window.simpleDatatables.DataTable) return;

            document.querySelectorAll('table[data-datatable="true"]').forEach(function (table) {
                if (table.dataset.datatableInit === '1') return;
                table.dataset.datatableInit = '1';

                new simpleDatatables.DataTable(table, {
                    searchable: true,
                    fixedHeight: true,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                });
            });
        })();
    </script>
    @stack('page_scripts')
    @stack('scripts')
    @yield('scripts')

</body>

</html>

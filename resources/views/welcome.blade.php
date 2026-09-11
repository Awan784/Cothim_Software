<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sign In — {{ config('ams.company_name') }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
    <link type="text/css" href="{{ asset('css/volt.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('css/ams-theme.css') }}?v=4" rel="stylesheet">
</head>

<body class="ams-light ams-login">
    <main>
        <section class="mt-5 vh-lg-100 mt-lg-0 bg-soft d-flex align-items-center">
            <div class="container">
                <div class="row justify-content-center form-bg-image"
                    data-background-lg="{{ asset('assets/img/illustrations/signin.svg') }}">
                    <div class="col-12 d-flex align-items-center justify-content-center">
                        <div class="p-4 bg-white border-0 rounded shadow border-light p-lg-5 w-100 fmxw-500 ams-login-card">
                            <div class="mb-4 text-center text-md-center mt-md-0">
                                <img src="{{ asset('assets/img/brand/dark.svg') }}" height="48" alt="Logo" class="mb-3">
                                <h1 class="mb-1 h3">{{ config('ams.company_name') }}</h1>
                                <p class="text-gray-600 mb-0">Sign in to continue</p>
                            </div>

                            @if (session('success'))
                                <div class="alert alert-success ams-alert mb-3" role="alert">{{ session('success') }}</div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger ams-alert mb-3" role="alert">{{ session('error') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-warning ams-alert mb-3" role="alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ url('/') }}" method="post" class="mt-4" id="loginForm">
                                @csrf
                                <div class="mb-4 form-group">
                                    <label for="email">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg class="text-gray-600 icon icon-xs" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                            </svg>
                                        </span>
                                        <input type="email" name="email" value="{{ old('email') }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="you@company.com" id="email" autofocus required>
                                    </div>
                                    @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-4 form-group">
                                    <label for="password">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg class="text-gray-600 icon icon-xs" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Password" id="password" required>
                                    </div>
                                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-gray-800 position-relative">
                                        <span class="ams-btn-text">Sign in</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/ams-ui.js') }}"></script>
</body>

</html>

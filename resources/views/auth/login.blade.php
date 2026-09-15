@extends('auth.layout')
@section('title', __('Login').' — '.config('ams.product_name'))

@section('content')
<section class="wafi-auth">
    <div class="wafi-auth-card">
        <h1>{{ __('Sign in to :name', ['name' => config('ams.product_name')]) }}</h1>
        <p class="muted">{{ __('Use your company email, or salesman username.') }}</p>

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

        <form method="post" action="{{ url('/login') }}">
            @csrf
            <div class="wafi-field">
                <label for="login">{{ __('Email or username') }}</label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="you@company.com or username" autocomplete="username">
            </div>
            <div class="wafi-field">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required>
            </div>
            <button class="wafi-btn wafi-btn-indigo" type="submit">{{ __('Sign in') }}</button>
        </form>
    </div>
</section>
@endsection

@extends('marketing.layout')
@section('title', __('Start now for free').' — '.config('ams.product_name'))

@section('content')
<section class="wafi-auth">
    <div class="wafi-auth-card">
        <h1>{{ __('Start now for free') }}</h1>
        <p class="muted">{{ __('register.lead') }}</p>

        @if ($errors->any())
            <div class="wafi-alert wafi-alert-warn">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('register') }}">
            @csrf
            <div class="wafi-field">
                <label for="company_name">{{ __('Company name') }}</label>
                <input id="company_name" name="company_name" value="{{ old('company_name') }}" required>
            </div>
            <div class="wafi-field">
                <label for="name">{{ __('Your name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="wafi-field">
                <label for="email">{{ __('Work email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="wafi-field">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required minlength="6">
            </div>
            <div class="wafi-field">
                <label for="password_confirmation">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="6">
            </div>
            <button class="wafi-btn wafi-btn-orange" type="submit">{{ __('Start 14-day free trial') }}</button>
        </form>
        <p class="muted" style="margin-top:1rem">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login') }}</a></p>
    </div>
</section>
@endsection

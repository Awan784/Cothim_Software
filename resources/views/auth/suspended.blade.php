@extends('auth.layout')
@section('title', __('This company workspace is suspended.'))

@section('content')
<section class="wafi-auth">
    <div class="wafi-auth-card">
        <h1>{{ $organization->name }}</h1>
        <p class="muted">{{ __('This company workspace is suspended.') }}</p>
        <p class="muted" style="margin-top:1rem"><a href="{{ route('logout') }}">{{ __('Login') }}</a></p>
    </div>
</section>
@endsection

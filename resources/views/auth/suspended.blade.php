@extends('marketing.layout')
@section('title', __('This company workspace is suspended.'))

@section('content')
<section class="wafi-auth">
    <div class="wafi-auth-card">
        <h1>{{ $organization->name }}</h1>
        <p class="muted">{{ __('This company workspace is suspended.') }}</p>
        <a class="wafi-btn wafi-btn-orange" href="{{ wafi_whatsapp_url() }}" target="_blank" rel="noopener">{{ __('Chat on WhatsApp') }}</a>
        <p class="muted" style="margin-top:1rem"><a href="{{ route('logout') }}">{{ __('Back to website') }}</a></p>
    </div>
</section>
@endsection

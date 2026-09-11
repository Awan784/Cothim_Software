@extends('platform.layout')
@section('title', $organization->name)

@section('content')
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $organization->name }}</h1>
            <p class="text-muted mb-0">{{ $organization->slug }} · invoices {{ $invoices }} · customers {{ $customers }}</p>
        </div>
        <a class="btn btn-sm btn-secondary" href="{{ route('platform.organizations') }}">{{ __('Companies') }}</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card p-4">
                <form method="post" action="{{ route('platform.organizations.update', $organization) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Plan') }}</label>
                        <select name="plan" class="form-select">
                            @foreach(['trial','plus','pro'] as $plan)
                                <option value="{{ $plan }}" @selected($organization->plan === $plan)>{{ ucfirst($plan) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Trial ends') }}</label>
                        <input type="date" name="trial_ends_at" class="form-control" value="{{ optional($organization->trial_ends_at)->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected($organization->status === 'active')>{{ __('Active') }}</option>
                            <option value="suspended" @selected($organization->status === 'suspended')>{{ __('Suspended') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input name="whatsapp" class="form-control" value="{{ $organization->whatsapp }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="3">{{ $organization->admin_notes }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                </form>
            </div>
            <form class="mt-3 d-flex gap-2" method="post" action="{{ route('platform.organizations.extend', $organization) }}">
                @csrf
                @foreach([7,14,30] as $days)
                    <button class="btn btn-outline-primary" name="days" value="{{ $days }}" type="submit">{{ __('Extend trial') }} +{{ $days }}</button>
                @endforeach
            </form>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><strong>{{ __('Users') }}</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>{{ __('Your name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Status') }}</th></tr></thead>
                        <tbody>
                            @foreach($organization->users as $user)
                                <tr>
                                    <td>{{ $user->name }} @if($user->is_platform_admin)<span class="badge bg-primary">{{ __('Platform admin') }}</span>@endif</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->is_active ? __('Active') : __('Suspended') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

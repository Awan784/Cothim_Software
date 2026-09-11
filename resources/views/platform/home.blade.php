@extends('platform.layout')
@section('title', __('Platform admin'))

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card p-3"><div class="text-muted small">{{ __('Companies') }}</div><div class="h3 mb-0">{{ $stats['companies'] }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><div class="text-muted small">{{ __('Users') }}</div><div class="h3 mb-0">{{ $stats['users'] }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><div class="text-muted small">{{ __('Trials') }}</div><div class="h3 mb-0">{{ $stats['trials'] }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><div class="text-muted small">{{ __('Suspended') }}</div><div class="h3 mb-0">{{ $stats['suspended'] }}</div></div></div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <strong>{{ __('Companies') }}</strong>
            <a href="{{ route('platform.organizations') }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
        </div>
        <div class="table-responsive">
            <table class="table table-flush mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>{{ __('Company name') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Days left') }}</th>
                        <th>{{ __('Users') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($organizations as $org)
                        <tr>
                            <td>{{ $org->name }}</td>
                            <td>{{ ucfirst($org->plan) }}</td>
                            <td>{{ $org->isOnTrial() ? ($org->trialDaysLeft() ?? '—') : '—' }}</td>
                            <td>{{ $org->users_count }}</td>
                            <td>{{ $org->status === 'active' ? __('Active') : __('Suspended') }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('platform.organizations.show', $org) }}">{{ __('Open') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

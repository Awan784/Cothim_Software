@extends('platform.layout')
@section('title', __('Companies'))

@section('content')
    <h1 class="h4 mb-3">{{ __('Companies') }}</h1>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-flush mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Company name') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Trial ends') }}</th>
                        <th>{{ __('Days left') }}</th>
                        <th>{{ __('Users') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $org)
                        <tr>
                            <td>{{ $org->id }}</td>
                            <td>{{ $org->name }}</td>
                            <td>{{ ucfirst($org->plan) }}</td>
                            <td>{{ $org->trial_ends_at ? $org->trial_ends_at->format('Y-m-d') : '—' }}</td>
                            <td>{{ $org->trialDaysLeft() ?? '—' }}</td>
                            <td>{{ $org->users_count }}</td>
                            <td>{{ $org->status === 'active' ? __('Active') : __('Suspended') }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('platform.organizations.show', $org) }}">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

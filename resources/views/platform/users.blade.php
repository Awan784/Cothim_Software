@extends('platform.layout')
@section('title', __('All users'))

@section('content')
    <h1 class="h4 mb-3">{{ __('All users') }}</h1>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-flush mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Your name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Company name') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Days left') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->organization?->name ?: '—' }}</td>
                            <td>{{ $user->organization?->plan ? ucfirst($user->organization->plan) : '—' }}</td>
                            <td>{{ $user->organization?->trialDaysLeft() ?? '—' }}</td>
                            <td>{{ $user->is_active ? __('Active') : __('Suspended') }}</td>
                            <td class="text-end">
                                <form method="post" action="{{ route('platform.users.toggle', $user) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-primary' }}" type="submit">
                                        {{ $user->is_active ? __('Disable') : __('Enable') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

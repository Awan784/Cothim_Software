@extends('template.layout')
@section('title', 'Edit User')

@section('content')
    <div class="my-4 row">
        <form action="{{ route('users.update', $user->id) }}" method="post" class="p-3 pb-4 mb-4 border-0 shadow card">
            @csrf
            @method('PUT')
            <div class="p-0 py-3 mb-4 card-header mx-lg-4 py-lg-4 mb-md-0">
                <h3 class="mb-0 h5">{{ ($isSelf ?? false) ? 'My Account' : 'Edit User' }}</h3>
                @if ($isSelf ?? false)
                    <p class="mb-0 small text-muted">Update your name, email, or password below.</p>
                @endif
            </div>
            <div class="p-0 card-body p-md-4 pb-md-0">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-6">
                        <div class="mb-4 form-group">
                            <label>User Name *</label>
                            <input type="text" placeholder="Name" value="{{ old('name', $user->name) }}" name="name"
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="mb-4 form-group">
                            <label>Email address *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', $user->email) }}" placeholder="example@company.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="mb-4 form-group">
                            <label>Password</label>
                            <input type="text" class="form-control @error('password') is-invalid @enderror"
                                name="password" placeholder="Leave empty to keep current password" autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="mb-4 form-group">
                            <label>Confirm Password</label>
                            <input type="text" name="password_confirmation" class="form-control"
                                placeholder="Leave empty to keep current password" autocomplete="new-password">
                        </div>
                    </div>
                    @if (! ($isSelf ?? false))
                    <div class="col-12 col-lg-6">
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1"
                                @checked(old('is_active', $user->is_active))>
                            <label class="form-check-label" for="is_active">Active (can sign in)</label>
                        </div>
                    </div>
                    @endif
                </div>

                @if (! ($isSelf ?? false))
                @include('users._permissions', ['user' => $user])
                @endif

                <div class="col-12 mt-3">
                    <button class="btn btn-gray-800" type="submit">Save User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection

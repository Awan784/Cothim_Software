@extends('template.layout')
@section('title', 'Edit Cash Account')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Cash Account</h1>
                <p class="mb-0">Update cash account details.</p>
            </div>
            <div>
                <a href="{{ route('cash-accounts.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('cash-accounts.update', $cashAccount) }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" value="{{ old('name', $cashAccount->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Opening Balance</label>
                    <input name="opening_balance" value="{{ old('opening_balance', $cashAccount->opening_balance) }}" class="form-control @error('opening_balance') is-invalid @enderror">
                    @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $cashAccount->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection


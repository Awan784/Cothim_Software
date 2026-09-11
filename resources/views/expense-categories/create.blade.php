@extends('template.layout')
@section('title', 'Create Expense Category')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Expense Category</h1>
                <p class="mb-0">Add a new expense category.</p>
            </div>
            <div>
                <a href="{{ route('expense-categories.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('expense-categories.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Nominal A/C (optional)</label>
                    <select name="nominal_account_id" class="form-select @error('nominal_account_id') is-invalid @enderror">
                        <option value="">Select nominal account</option>
                        @foreach($nominalAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('nominal_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->type }} - {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('nominal_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection


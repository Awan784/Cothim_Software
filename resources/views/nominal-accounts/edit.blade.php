@extends('template.layout')
@section('title', 'Edit Nominal Account')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Nominal A/C</h1>
                <p class="mb-0">Update nominal account details.</p>
            </div>
            <div>
                <a href="{{ route('nominal-accounts.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('nominal-accounts.update', $nominalAccount) }}">
                @csrf
                @method('put')

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code</label>
                        <input name="code" value="{{ old('code', $nominalAccount->code) }}" class="form-control @error('code') is-invalid @enderror">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" value="{{ old('name', $nominalAccount->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select type</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}" {{ old('type', $nominalAccount->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parent (optional)</label>
                        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">No parent</option>
                            @foreach($nominalAccounts as $p)
                                <option value="{{ $p->id }}" {{ (string) old('parent_id', $nominalAccount->parent_id) === (string) $p->id ? 'selected' : '' }}>
                                    {{ $p->type }} - {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $nominalAccount->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection


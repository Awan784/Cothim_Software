@extends('template.layout')
@section('title', 'Create Vendor')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Vendor</h1>
                <p class="mb-0">Opening balance is the amount already payable to this vendor.</p>
            </div>
            <div>
                <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('vendors.store') }}">
                @csrf
                @include('vendors._fields', ['vendor' => new \App\Models\Vendor(['is_active' => true, 'opening_balance' => 0])])
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection

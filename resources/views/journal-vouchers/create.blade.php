@extends('template.layout')
@section('title', 'General Journal')

@section('content')
    <div class="pb-4">
        <div class="py-3 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">General Journal</h1>
            <a href="{{ route('journal-vouchers.index') }}" class="btn btn-sm btn-danger">Close</a>
        </div>

        @if ($errors->has('lines'))
            <div class="alert alert-danger">{{ $errors->first('lines') }}</div>
        @endif

        <div class="card p-4 shadow-sm">
            <form method="post" action="{{ route('journal-vouchers.store') }}">
                @csrf
                @include('journal-vouchers._form')
            </form>
        </div>
    </div>
@endsection

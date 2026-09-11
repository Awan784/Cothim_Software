@extends('template.layout')
@section('title', 'Upload to Inbox')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Upload document</h1>
                <p class="mb-0 text-muted">PDF or image. You can post it as an expense or supplier bill next.</p>
            </div>
            <a href="{{ route('inbox.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <div class="card p-4">
            <form method="post" action="{{ route('inbox.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="receipt">Receipt / expense</option>
                            <option value="bill">Supplier bill</option>
                            <option value="bank_statement">Bank statement</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Title</label>
                        <input name="title" value="{{ old('title') }}" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount</label>
                        <input name="extracted_amount" value="{{ old('extracted_amount') }}" class="form-control" type="number" step="0.01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date</label>
                        <x-ams-date-input name="extracted_date" :value="now()" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Party name</label>
                        <input name="extracted_party" value="{{ old('extracted_party') }}" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">File</label>
                    <input type="file" name="file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Add to Inbox</button>
            </form>
        </div>
    </div>
@endsection

@extends('template.layout')
@section('title', $title)

@section('content')
    <div class="pb-4">
        <div class="py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $title }}</h1>
                @if (!empty($summary))
                    <p class="mb-0 text-gray-700"><strong>{{ $summary }}</strong></p>
                @endif
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-secondary">Back to Reports</a>
        </div>

        @if (!empty($message))
            <div class="alert alert-info">{{ $message }}</div>
        @endif

        @if (!empty($grouped))
            @foreach($grouped as $group)
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-light">
                        <strong>{{ $group['title'] }}</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-flush mb-0" data-datatable="true">
                            <thead class="thead-light">
                                <tr>
                                    @foreach($group['columns'] as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group['rows'] as $row)
                                    <tr>
                                        @foreach($group['columns'] as $key => $label)
                                            <td class="text-gray-900">{{ $row[$key] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($group['columns']) }}" class="text-center text-gray-600">No data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (!empty($group['footer']))
                                <tfoot>
                                    <tr class="table-light">
                                        @foreach($group['footer'] as $cell)
                                            <td class="fw-bold">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endforeach
        @elseif (!empty($columns))
            <div class="card shadow-sm">
                <div class="table-responsive py-3">
                    <table class="table table-flush" data-datatable="true">
                        <thead class="thead-light">
                            <tr>
                                @foreach($columns as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    @foreach($columns as $key => $label)
                                        <td class="text-gray-900">{{ $row[$key] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) }}" class="text-center text-gray-600">No data for this report.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (!empty($footer))
                            <tfoot>
                                <tr class="table-light">
                                    @foreach($footer as $cell)
                                        <td class="fw-bold">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection

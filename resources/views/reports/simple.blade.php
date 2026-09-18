@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                @foreach($columns as $key => $label)
                    <th class="{{ in_array($key, $numeric ?? [], true) ? 'num' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $key => $label)
                        <td class="{{ in_array($key, $numeric ?? [], true) ? 'num' : '' }}">
                            @if(in_array($key, $numeric ?? [], true))
                                {{ ams_num($row[$key] ?? 0) }}
                            @else
                                {{ $row[$key] ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty"><td colspan="{{ count($columns) }}">{{ $empty ?? 'No records found.' }}</td></tr>
            @endforelse
        </tbody>
        @if(!empty($footer) && $rows->isNotEmpty())
            <tfoot>
                <tr>
                    @foreach($columns as $key => $label)
                        <td class="{{ in_array($key, $numeric ?? [], true) ? 'num' : '' }}">
                            @if(in_array($key, $numeric ?? [], true))
                                {{ ams_num($footer[$key] ?? 0) }}
                            @else
                                {{ $footer[$key] ?? '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

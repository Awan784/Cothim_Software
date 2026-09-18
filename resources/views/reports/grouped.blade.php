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
            @forelse($groups as $group)
                <tr class="group-head">
                    <td colspan="{{ count($columns) }}">{{ $group['title'] }}</td>
                </tr>
                @forelse($group['rows'] as $row)
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
                    <tr class="empty"><td colspan="{{ count($columns) }}">No items in this group.</td></tr>
                @endforelse
                @if(!empty($group['footer']))
                    <tr class="subtotal">
                        @foreach($columns as $key => $label)
                            <td class="{{ in_array($key, $numeric ?? [], true) ? 'num' : '' }}">
                                @if(in_array($key, $numeric ?? [], true))
                                    {{ ams_num($group['footer'][$key] ?? 0) }}
                                @else
                                    {{ $group['footer'][$key] ?? '' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            @empty
                <tr class="empty"><td colspan="{{ count($columns) }}">{{ $empty ?? 'No records found.' }}</td></tr>
            @endforelse
        </tbody>
        @if(!empty($footer) && $groups->isNotEmpty())
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

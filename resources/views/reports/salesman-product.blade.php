@extends('reports.print')

@section('body')
    <div class="section-title">Top products · who sold the most</div>
    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th class="num">Qty</th>
                <th class="num">Amount</th>
                <th class="num">Salesmen</th>
                <th>Top salesman</th>
                <th class="num">Top qty</th>
                <th class="num">Top amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topProducts as $i => $row)
                <tr class="row-sale">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['product'] }}</td>
                    <td>{{ $row['sku'] ?: '—' }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="num">{{ ams_num($row['qty']) }}</td>
                    <td class="num">{{ ams_num($row['amount']) }}</td>
                    <td class="num">{{ $row['salesmen'] }}</td>
                    <td>{{ $row['top_salesman'] }}</td>
                    <td class="num">{{ ams_num($row['top_qty']) }}</td>
                    <td class="num">{{ ams_num($row['top_amount']) }}</td>
                </tr>
            @empty
                <tr class="empty"><td colspan="10">No invoiced product sales in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Detail by {{ $groupLabel ?? 'category' }}</div>
    <table class="items">
        <thead>
            <tr>
                @foreach($detailColumns as $key => $label)
                    <th class="{{ in_array($key, $detailNumeric ?? [], true) ? 'num' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
                <tr class="group-head">
                    <td colspan="{{ count($detailColumns) }}">{{ $group['title'] }}</td>
                </tr>
                @foreach($group['rows'] as $row)
                    <tr>
                        @foreach($detailColumns as $key => $label)
                            <td class="{{ in_array($key, $detailNumeric ?? [], true) ? 'num' : '' }}">
                                @if(in_array($key, $detailNumeric ?? [], true))
                                    {{ ams_num($row[$key] ?? 0) }}
                                @else
                                    {{ $row[$key] !== '' ? ($row[$key] ?? '—') : '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="subtotal">
                    @foreach($detailColumns as $key => $label)
                        <td class="{{ in_array($key, $detailNumeric ?? [], true) ? 'num' : '' }}">
                            @if($loop->first)
                                {{ $group['footer']['salesman'] ?? 'Subtotal' }}
                            @elseif(in_array($key, $detailNumeric ?? [], true))
                                {{ ams_num($group['footer'][$key] ?? 0) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty"><td colspan="{{ count($detailColumns) }}">No product sales for the selected filters.</td></tr>
            @endforelse
        </tbody>
        @if(!empty($footer) && $groups->isNotEmpty())
            <tfoot>
                <tr>
                    @foreach($detailColumns as $key => $label)
                        <td class="{{ in_array($key, $detailNumeric ?? [], true) ? 'num' : '' }}">
                            @if($loop->first)
                                {{ $footer['label'] ?? 'Grand total' }}
                            @elseif(in_array($key, $detailNumeric ?? [], true))
                                {{ ams_num($footer[$key] ?? 0) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

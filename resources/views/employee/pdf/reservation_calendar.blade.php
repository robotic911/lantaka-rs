<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
/* ── Reset ──────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: A4 landscape; margin: 8mm 12mm 8mm 12mm; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    color: #1f2937;
    background: #ffffff;
}

/* ── Page break between months ───────────────────────────── */
.month-page-break { page-break-after: always; }

/* ── Title ───────────────────────────────────────────────── */
.pg-title {
    font-size: 13pt;
    font-weight: bold;
    color: #1e3a5f;
    text-align: center;
    padding-bottom: 2mm;
    border-bottom: 2px solid #2c3e8f;
    margin-bottom: 3mm;
}

/* ── Legend ──────────────────────────────────────────────── */
.legend-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
}
.legend-cell {
    text-align: center;
    font-size: 6.5pt;
    font-weight: bold;
    padding: 1mm 0;
    vertical-align: middle;
}
.swatch {
    display: inline-block;
    width: 8pt;
    height: 8pt;
    border-radius: 2pt;
    vertical-align: middle;
    margin-right: 2pt;
    border-width: 1px;
    border-style: solid;
}

/* ── Calendar master table ───────────────────────────────── */
.cal-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border: 1.5px solid #d1d5db;
}

/* ── DOW header ──────────────────────────────────────────── */
.dow-th {
    width: 14.2857%;
    text-align: center;
    font-size: 6pt;
    font-weight: bold;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 2mm 0;
    background: #f1f5f9;
    border: 1px solid #d1d5db;
}

/* ── Date row cells ──────────────────────────────────────── */
.date-cell {
    text-align: center;
    vertical-align: top;
    padding: 1.5mm 1mm 1mm;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    height: 11mm;
}
.date-cell.wknd       { background: #eff6ff; }
.date-cell.out        { background: #f9fafb; }
.date-cell.tod        { background: #dbeafe; }

.dname {
    display: block;
    font-size: 5pt;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.5mm;
}
.dnum {
    display: block;
    font-size: 13pt;
    font-weight: bold;
    line-height: 1.1;
    color: #1f2937;
}
.dnum.dim { color: #d1d5db; font-weight: normal; font-size: 11pt; }
.dnum.hi  { color: #1d4ed8; }

/* ── Lane rows ───────────────────────────────────────────── */
.lane-row { height: 8mm; }

/* Empty cells — subtle column guide */
.lane-empty {
    border-left:  1px solid #f3f4f6;
    border-right: 1px solid #f3f4f6;
    background: transparent;
}

/* Cell that holds a bar */
.lane-bar-td {
    padding: 0.6mm 1pt;
    vertical-align: middle;
}

/* Bar div — styled block inside the td */
.bar-div {
    height: 6mm;
    line-height: 6mm;
    padding: 0 4pt;
    font-size: 6.5pt;
    font-weight: bold;
    overflow: hidden;
    white-space: nowrap;
    border-radius: 3pt;
    border-width: 1px;
    border-style: solid;
}
.bar-clip-l {
    border-top-left-radius:    0;
    border-bottom-left-radius: 0;
    border-left-style: dashed;
}
.bar-clip-r {
    border-top-right-radius:    0;
    border-bottom-right-radius: 0;
    border-right-style: dashed;
}

/* Empty week spacer (weeks with no reservations) */
.empty-week-cell { height: 8mm; }

/* Week separator strip */
.week-sep td {
    height: 1.5mm;
    background: #f0f2f5;
    border-top: 1.5px solid #d1d5db;
}

/* ── Footer ──────────────────────────────────────────────── */
.footer {
    font-size: 5.5pt;
    color: #9ca3af;
    text-align: right;
    margin-top: 2mm;
}
</style>
</head>
<body>

@foreach($months as $monthIdx => $month)
<div class="month-page">

    {{-- ── Title ─────────────────────────────────────────── --}}
    <div class="pg-title">Reservation Calendar &mdash; {{ $month['month_label'] }}</div>

    {{-- ── Legend ────────────────────────────────────────── --}}
    <table class="legend-table">
    <tr>
        @foreach($month['legend'] as $item)
        <td class="legend-cell">
            <span class="swatch" style="background:{{ $item['bg'] }};border-color:{{ $item['border'] }};"></span>
            {{ $item['label'] }}
        </td>
        @endforeach
    </tr>
    </table>

    {{-- ── Calendar grid ──────────────────────────────────── --}}
    <table class="cal-table">
        <thead>
            <tr>
                <th class="dow-th">Sun</th>
                <th class="dow-th">Mon</th>
                <th class="dow-th">Tue</th>
                <th class="dow-th">Wed</th>
                <th class="dow-th">Thu</th>
                <th class="dow-th">Fri</th>
                <th class="dow-th">Sat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($month['weeks'] as $week)

            {{-- Date header row --}}
            <tr>
                @foreach($week['days'] as $day)
                <td class="date-cell
                    {{ $day['is_weekend'] ? 'wknd' : '' }}
                    {{ !$day['in_month']  ? 'out'  : '' }}
                    {{ $day['is_today']   ? 'tod'  : '' }}">
                    <span class="dname">{{ $day['name'] }}</span>
                    <span class="dnum
                        {{ !$day['in_month'] ? 'dim' : '' }}
                        {{ $day['is_today']  ? 'hi'  : '' }}">{{ $day['num'] }}</span>
                </td>
                @endforeach
            </tr>

            {{-- Lane rows (one <tr> per lane) --}}
            @if(!empty($week['lanes']))
                @foreach($week['lanes'] as $bars)
                @php
                    /* Sort bars in this lane left-to-right */
                    usort($bars, fn($a, $b) => $a['col_start'] - $b['col_start']);

                    /* Build a flat cell list: empty | bar(colspan) */
                    $cells = [];
                    $pos   = 0;
                    foreach ($bars as $bar) {
                        /* Empty filler cells before this bar */
                        while ($pos < $bar['col_start']) {
                            $cells[] = ['type' => 'empty'];
                            $pos++;
                        }
                        /* The bar cell (may span multiple columns) */
                        $cells[] = [
                            'type' => 'bar',
                            'span' => $bar['col_span'],
                            'bar'  => $bar,
                        ];
                        $pos += $bar['col_span'];
                    }
                    /* Fill any remaining columns on the right */
                    while ($pos < 7) {
                        $cells[] = ['type' => 'empty'];
                        $pos++;
                    }
                @endphp
                <tr class="lane-row">
                    @foreach($cells as $cell)
                        @if($cell['type'] === 'empty')
                        <td class="lane-empty"></td>
                        @else
                        @php
                            $bar  = $cell['bar'];
                            $sc   = $month['statusColors'][$bar['status']]
                                    ?? $month['statusColors']['confirmed'];
                            /* Truncate label to roughly fit: ~22 chars per column */
                            $maxC = max(14, $bar['col_span'] * 22);
                            $lbl  = mb_strlen($bar['label']) > $maxC
                                    ? mb_strimwidth($bar['label'], 0, $maxC, '…')
                                    : $bar['label'];
                            $cls  = ($bar['clips_l'] ? 'bar-clip-l ' : '')
                                  . ($bar['clips_r'] ? 'bar-clip-r'  : '');
                        @endphp
                        <td colspan="{{ $cell['span'] }}" class="lane-bar-td">
                            <div class="bar-div {{ $cls }}"
                                 style="background:{{ $sc['bg'] }};border-color:{{ $sc['border'] }};color:{{ $sc['fg'] }};">
                                {{ $lbl }}
                            </div>
                        </td>
                        @endif
                    @endforeach
                </tr>
                @endforeach

            @else
            {{-- No reservations this week — small breathing row --}}
            <tr>
                <td class="lane-empty empty-week-cell" colspan="7"></td>
            </tr>
            @endif

            {{-- Week separator (between weeks, not after the last) --}}
            @if(!$loop->last)
            <tr class="week-sep"><td colspan="7"></td></tr>
            @endif

            @endforeach {{-- /weeks --}}
        </tbody>
    </table>

    @if($loop->last)
    <div class="footer">Generated {{ $generated_at }}</div>
    @endif

</div>{{-- /month-page --}}

@if(!$loop->last)
<div class="month-page-break"></div>
@endif

@endforeach {{-- /months --}}

</body>
</html>

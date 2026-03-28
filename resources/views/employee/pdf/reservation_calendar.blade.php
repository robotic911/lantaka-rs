<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
/* ─── Reset ─────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: A4 landscape; margin: 7mm 11mm 7mm 11mm; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8pt;
    color: #1f2937;
    background: #ffffff;
}

/* ─── Title ─────────────────────────────────────────────── */
.page-title {
    font-size: 14pt;
    font-weight: bold;
    color: #1e3a5f;
    text-align: center;
    padding-bottom: 2mm;
    border-bottom: 2.5px solid #2c3e8f;
    margin-bottom: 2mm;
}

/* ─── Legend (table for DOMPDF compat) ──────────────────── */
.legend-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
}
.legend-cell {
    text-align: center;
    font-size: 7pt;
    font-weight: bold;
    padding: 1mm 0;
    vertical-align: middle;
}
.swatch {
    display: inline-block;
    width: 9pt;
    height: 9pt;
    border-radius: 2pt;
    vertical-align: middle;
    margin-right: 2pt;
    border-width: 1px;
    border-style: solid;
}

/* ─── Week block ─────────────────────────────────────────── */
.week {
    margin-bottom: 2.5mm;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    overflow: hidden;
}

.week-label {
    font-size: 6pt;
    color: #6b7280;
    font-weight: bold;
    background: #f1f5f9;
    padding: 0.8mm 2.5mm;
    border-bottom: 1px solid #e5e7eb;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

/* ─── Date header row (table) ────────────────────────────── */
.date-table {
    width: 100%;
    border-collapse: collapse;
}
.date-td {
    width: 14.2857%;
    text-align: center;
    padding: 1.8mm 0;
    vertical-align: middle;
    border-right: 1px solid #f0f0f0;
    background: #ffffff;
}
.date-td:last-child { border-right: none; }
.date-td.wknd       { background: #eff6ff; }
.date-td.out        { background: #f9fafb; }
.date-td.tod        { background: #dbeafe; }

.dname {
    display: block;
    font-size: 5.5pt;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.dnum {
    display: block;
    font-size: 13pt;
    font-weight: bold;
    line-height: 1.1;
    color: #1f2937;
}
.dnum.dim { color: #d1d5db; font-weight: normal; }
.dnum.hi  { color: #1d4ed8; }

/* ─── Lanes ──────────────────────────────────────────────── */
.lanes-wrap {
    background: #f9fafb;
    padding: 1.2mm 0;
    border-top: 1px solid #e5e7eb;
}
.no-rsv {
    background: #f9fafb;
    height: 5.5mm;
    border-top: 1px solid #e5e7eb;
}
.lane-row {
    position: relative;
    height: 13pt;
    margin: 1pt 0;
}

/* ─── Reservation bar ────────────────────────────────────── */
.bar {
    position: absolute;
    height: 11pt;
    top: 1pt;
    border-radius: 3pt;
    font-size: 6pt;
    font-weight: bold;
    overflow: hidden;
    white-space: nowrap;
    text-align: center;
    line-height: 11pt;
    padding: 0 3pt;
    border-width: 1px;
    border-style: solid;
}
/* Clipping arrows — use left/right borders to hint continuation */
.clip-l {
    border-top-left-radius:    0;
    border-bottom-left-radius: 0;
    border-left-style: dashed;
}
.clip-r {
    border-top-right-radius:    0;
    border-bottom-right-radius: 0;
    border-right-style: dashed;
}

/* ─── Footer ─────────────────────────────────────────────── */
.footer {
    font-size: 6pt;
    color: #9ca3af;
    text-align: right;
    margin-top: 2mm;
}
</style>
</head>
<body>

{{-- ── Title ─────────────────────────────────────────────── --}}
<div class="page-title">Reservation Calendar &mdash; {{ $month_label }}</div>

{{-- ── Legend ───────────────────────────────────────────── --}}
<table class="legend-table">
<tr>
    @foreach($legend as $item)
    <td class="legend-cell">
        <span class="swatch"
              style="background:{{ $item['bg'] }};border-color:{{ $item['border'] }};"></span>
        {{ $item['label'] }}
    </td>
    @endforeach
</tr>
</table>

{{-- ── Weeks ────────────────────────────────────────────── --}}
@foreach($weeks as $week)
<div class="week">

    {{-- Week label --}}
    <div class="week-label">{{ $week['label'] }}</div>

    {{-- Date header cells --}}
    <table class="date-table">
    <tr>
        @foreach($week['days'] as $day)
        <td class="date-td
            {{ $day['is_weekend'] ? 'wknd' : '' }}
            {{ !$day['in_month']  ? 'out'  : '' }}
            {{ $day['is_today']   ? 'tod'  : '' }}">
            <span class="dname">{{ $day['name'] }}</span>
            <span class="dnum {{ !$day['in_month'] ? 'dim' : '' }} {{ $day['is_today'] ? 'hi' : '' }}">{{ $day['num'] }}</span>
        </td>
        @endforeach
    </tr>
    </table>

    {{-- Reservation lanes --}}
    @if(!empty($week['lanes']))
    <div class="lanes-wrap">
        @foreach($week['lanes'] as $bars)
        <div class="lane-row">
            @foreach($bars as $bar)
            @php
                $pct   = 100 / 7;
                $left  = round($bar['col_start'] * $pct, 4);
                $width = round($bar['col_span']  * $pct, 4);
                $c     = $statusColors[$bar['status']] ?? $statusColors['confirmed'];
                $cls   = trim(($bar['clips_l'] ? 'clip-l ' : '') . ($bar['clips_r'] ? 'clip-r' : ''));
            @endphp
            <div class="bar {{ $cls }}"
                 style="left:{{ $left }}%;width:{{ $width }}%;background:{{ $c['bg'] }};color:{{ $c['fg'] }};border-color:{{ $c['border'] }};">
                {{ mb_strimwidth($bar['label'], 0, 32, '…') }}
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @else
    <div class="no-rsv"></div>
    @endif

</div>{{-- /week --}}
@endforeach

<div class="footer">Generated {{ $generated_at }}</div>

</body>
</html>

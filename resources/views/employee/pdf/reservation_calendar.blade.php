@php
    $logoPath   = public_path('images/adzu_logo.png');
    $logoB64    = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    $logoSrc    = $logoB64 ? 'data:image/png;base64,' . $logoB64 : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>

/* ── Reset ───────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }

/* 297mm wide (A4 landscape), height auto-sized; 0.5 in = 12.7mm all sides */
@page { size: 297mm {{ $pageHeight }}mm; margin: 12.7mm; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 7pt;
    color: #1f2937;
    background: #ffffff;
    padding: 20px;
    overflow: hidden;
}

/* ── Page break ──────────────────────────────────────────── */
.month-page-break { page-break-after: always; }

/* ══════════════════════════════════════════════════════════
   HEADER  (mirrors the top bar of the live calendar)
   ══════════════════════════════════════════════════════════ */
.hdr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
    padding-bottom: 2mm;
    border-bottom: 1.5px solid #e5e7eb;
}
/* Month + year — left */
.hdr-month {
    font-size: 16pt;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
    width: 1%;           /* shrink-wrap */
    padding-right: 6mm;
}
.hdr-month .yr {
    font-weight: 400;
    color: #374151;
}
/* "RESERVATION CALENDAR" — centered */
.hdr-center {
    font-size: 8pt;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #6b7280;
    text-align: center;
}

/* ══════════════════════════════════════════════════════════
   LEGEND ROW  (coloured dots + hidden notice)
   ══════════════════════════════════════════════════════════ */
.lgd-table {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
}
.lgd-items {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 6.5pt;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}
.lgd-dot {
    
    display: inline-block;
    width: 7pt;
    height: 7pt;
    border-radius: 4pt;
    vertical-align: middle;
    margin-right: 2pt;
}
.lgd-sep { margin: 0 4pt; }
.lgd-notice {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 6pt;
    color: #9ca3af;
    font-style: italic;
    text-align: right;
}

/* ══════════════════════════════════════════════════════════
   CALENDAR TABLE
   ══════════════════════════════════════════════════════════ */
.cal-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border: 1px solid #e5e7eb;
}

/* DOW header — exactly like the live calendar column labels */
.dow-th {
    width: 14.2857%;
    text-align: center;
    font-size: 6pt;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 2mm 0;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    border-right: 1px solid #f3f4f6;
}

/* ── Date row cells ──────────────────────────────────────── */
.date-cell {
    text-align: left;
    vertical-align: top;
    padding: 1.5mm 1.5mm 1mm;
    border-right: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
}

.date-cell.wknd { background: #f9fafb; }
.date-cell.out  { background: #fafafa; }
.date-cell.tod  { background: #ffffff; }  /* today bg stays white; badge handles it */

/* Regular date number — large, top-left, matching the live calendar */
.dnum {
    display: block;
    font-size: 12pt;
    font-weight: 400;
    line-height: 1;
    color: #374151;
    text-align: left;
}
.dnum.dim { color: #d1d5db; font-weight: 400; font-size: 11pt; }

/* Today — blue filled circle (matches the live calendar badge) */
.dnum-today {
    display: inline-block;
    width: 18pt;
    height: 18pt;
    border-radius: 50%;
    font-size: 10pt;
    font-weight: 700;
    text-align: center;
    line-height: 18pt;
}

/* ── Lane rows ───────────────────────────────────────────── */
.lane-row { height: 5mm !important; }

.lane-empty {
    height: 6mm !important;
    vertical-align: middle;
    /* border-right: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6; */
    background: transparent;
}

/* Bar cell — tiny vertical gap, NO horizontal padding → bar fills full column width */
.lane-bar-td {
    height: 6mm !important;   /* close to bar height */
    padding: 0;               /* remove vertical gap */
    vertical-align: middle;
}

/* ── Reservation bar — mirrors the live calendar chip style ─ */
.bar-div {
    margin: 1px;
    text-transform: uppercase;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 5.5mm;
    line-height: 5.5mm;
    padding: 0 4pt;
    font-size: 8pt;
    font-weight: 500;
    overflow: hidden;
    white-space: nowrap;
    width: auto;
    text-align: left;
    border-width: 1px;
    border-style: solid;
    border-radius: 3pt;
}
/* Continuation — remove radius on the clipped edge */
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

/* Empty week spacer */
.empty-week-cell { height: 12mm !important; }

/* Week separator — thin gray line between weeks */
.week-sep td {
    height: 1px;
    background: #e5e7eb;
}

/* ══════════════════════════════════════════════════════════
   BRAND HEADER  (top-right of each page header)
   ══════════════════════════════════════════════════════════ */
.hdr-brand {
    text-align: right;
    width: 1%;
    white-space: nowrap;
    vertical-align: middle;
}
.hdr-brand-inner {
    display: inline-block;
    border-radius: 5pt;
    padding: 4pt 8pt 4pt 5pt;
}
.hdr-brand-table {
    border-collapse: collapse;
}
.hdr-brand-logo-td {
    vertical-align: middle;
    padding-right: 5pt;
}
.hdr-brand-logo {
    width: 26pt;
    height: 26pt;
}
.hdr-brand-text-td {
    vertical-align: middle;
    text-align: left;
}
.hdr-brand-univ {
    font-size: 5.5pt;
    font-style: italic;
    color:rgb(0, 0, 0);
    letter-spacing: 0.2px;
    line-height: 1.3;
    margin: 0;
}
.hdr-brand-title {
    font-size: 7.5pt;
    font-weight: 700;
    color:rgb(0, 0, 0);
    letter-spacing: 0.1px;
    line-height: 1.3;
    margin: 0;
}
.hdr-brand-tag {
    font-size: 4.5pt;
    color:rgb(0, 0, 0);
    letter-spacing: 0.2px;
    margin: 0;
}

/* ══════════════════════════════════════════════════════════
   FOOTER — fixed at page bottom (DomPDF repeats per page)
   ══════════════════════════════════════════════════════════ */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    font-size: 5.5pt;
    color: #9ca3af;
    text-align: center;
    padding-top: 1.5mm;
    border-top: 0.5px solid #e5e7eb;
    background: #ffffff;
}
</style>
</head>
<body>

{{-- Fixed footer — DomPDF renders this at the bottom of every page --}}
<div class="footer">
    AdZU Lantaka Reservation System &nbsp;|&nbsp;
    Prepared by: {{ $preparedBy }} &nbsp;|&nbsp;
    Period: {{ $periodLabel }} &nbsp;|&nbsp;
    Generated: {{ $generated_at }}
</div>

@foreach($months as $monthIdx => $month)
<div class="month-page">

    {{-- ══ Header row: month/year left · title center ═══════ --}}
    @php [$mn, $yr] = explode(' ', $month['month_label'], 2); @endphp
    <table class="hdr-table">
        <tr>
        <td class="hdr-brand">
                <div class="hdr-brand-inner">
                    <table class="hdr-brand-table">
                        <tr>
                            @if($logoSrc)
                            <td class="hdr-brand-logo-td">
                                <img src="{{ $logoSrc }}" class="hdr-brand-logo" alt="AdZU">
                            </td>
                            @endif
                            <td class="hdr-brand-text-td">
                                <p class="hdr-brand-univ">Ateneo de Zamboanga University</p>
                                <p class="hdr-brand-title">Lantaka Reservation Portal</p>
                                <p class="hdr-brand-tag">&lt; Lantaka Online Room &amp; Venue Reservation System/ &gt;</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="hdr-center">Reservation Calendar</td>
            <td class="hdr-month">
                {{ $mn }}&nbsp;<span class="yr">{{ $yr }}</span>
            </td>
        </tr>
    </table>

    {{-- ══ Legend: coloured dots + hidden-statuses notice ═══ --}}
    <table class="lgd-table">
        <tr>
            <td class="lgd-items">
                @foreach($month['legend'] as $item)
                    @if(!$loop->first)<span class="lgd-sep">&nbsp;</span>@endif
                    <span class="lgd-dot" style="background:{{ $item['solid'] }};"></span>{{ $item['label'] }}
                @endforeach
            </td>
            <td class="lgd-notice">Cancelled &amp; Rejected reservations are hidden</td>
        </tr>
    </table>

    {{-- ══ Calendar grid ══════════════════════════════════════ --}}
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

            {{-- Date header row — large number top-left, today gets a blue badge --}}
            <tr>
                @foreach($week['days'] as $day)
                <td class="date-cell
                    {{ $day['is_weekend'] ? 'wknd' : '' }}
                    {{ !$day['in_month']  ? 'out'  : '' }}
                    {{ $day['is_today']   ? 'tod'  : '' }}">
                    @if($day['is_today'])
                        <span class="dnum">{{ $day['num'] }}</span>
                    @else
                        <span class="dnum {{ !$day['in_month'] ? 'dim' : '' }}">{{ $day['num'] }}</span>
                    @endif
                </td>
                @endforeach
            </tr>

            {{-- Lane rows --}}
            @if(!empty($week['lanes']))
                @foreach($week['lanes'] as $bars)
                @php
                    usort($bars, fn($a, $b) => $a['col_start'] - $b['col_start']);

                    $cells = [];
                    $pos   = 0;
                    foreach ($bars as $bar) {
                        if ($pos >= 7) break; // never exceed 7 columns

                        // Fill empty cells up to bar start (capped at 7)
                        $fillTo = min($bar['col_start'], 7);
                        while ($pos < $fillTo) {
                            $cells[] = ['type' => 'empty'];
                            $pos++;
                        }

                        if ($pos >= 7) break;

                        // Clamp bar span so total never exceeds 7 columns
                        $span = max(1, min($bar['col_span'], 7 - $pos));
                        $cells[] = ['type' => 'bar', 'span' => $span, 'bar' => $bar];
                        $pos += $span;
                    }
                    // Trailing empty cells
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

                            /* ~24 chars per column span — more generous for longer labels */
                            $maxC = max(40, $bar['col_span'] * 24);
                            $lbl  = mb_strlen($bar['label']) > $maxC
                                    ? mb_strimwidth($bar['label'], 0, $maxC, '...')
                                    : $bar['label'];
                            $cls  = ($bar['clips_l'] ? 'bar-clip-l ' : '')
                                  . ($bar['clips_r'] ? 'bar-clip-r'  : '');
                        @endphp
                        <td colspan="{{ $cell['span'] }}" class="lane-bar-td">
                            <div class="bar-div {{ $cls }}"
                                 style="background: {{ $sc['bg'] }};
                                        display:flex;
                                        align-items: center;
                                        font-size: 6px;
                                        border-color: {{ $sc['border'] }};
                                        border-left-color: {{ $sc['solid'] }};
                                        color: {{ $sc['fg'] }};">
                                {{ $lbl }}
                            </div>
                        </td>
                        @endif
                    @endforeach
                </tr>
                @endforeach

            @else
            <tr>
                <td class="lane-empty empty-week-cell" colspan="7"></td>
            </tr>
            @endif

            @if(!$loop->last)
            <tr class="week-sep"><td colspan="7"></td></tr>
            @endif

            @endforeach {{-- /weeks --}}
        </tbody>
    </table>

</div>{{-- /month-page --}}

@if(!$loop->last)
<div class="month-page-break"></div>
@endif

@endforeach {{-- /months --}}

</body>
</html>

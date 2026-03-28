<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reservation Calendar</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .title {
            text-align: center;
            font-weight: 800;
            font-size: 22px;
            color: #22439a;
            padding: 12px 0;
            background: #edf3ff;
            border: 1px solid #cbd5e1;
            margin-bottom: 6px;
        }

        .subtitle {
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            color: #334155;
            margin-bottom: 6px;
        }

        .month-title {
            text-align: center;
            font-weight: 800;
            font-size: 16px;
            color: #0f172a;
            padding: 10px 0;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            margin-bottom: 10px;
        }

        .legend {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        .legend td {
            text-align: center;
            font-weight: 700;
            font-size: 9px;
            padding: 8px 4px;
            border: 1px solid #cbd5e1;
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .calendar th {
            background: #e2e8f0;
            color: #334155;
            border: 1px solid #94a3b8;
            padding: 8px 4px;
            font-size: 10px;
            font-weight: 700;
        }

        .date-row td {
            height: 92px;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            padding: 4px 4px 0 4px;
            background: #ffffff;
        }

        .date-row td.other-month {
            background: #f8fafc;
            color: #94a3b8;
        }

        .date-row td.weekend {
            background: #f8fbff;
        }

        .date-row td.other-month.weekend {
            background: #f1f5f9;
        }

        .day-number {
            font-size: 11px;
            font-weight: 700;
            text-align: left;
        }

        .lane-row td {
            height: 20px;
            padding: 0;
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .lane-row td.other-month {
            background: #f8fafc;
        }

        .lane-row td.weekend {
            background: #f8fbff;
        }

        .lane-row td.other-month.weekend {
            background: #f1f5f9;
        }

        .lane-row.last-lane td {
            border-bottom: 1px solid #cbd5e1;
        }

        .spacer-row td {
            height: 6px;
            border: none;
            background: transparent;
        }

        .bar {
            display: block;
            width: 100%;
            height: 20px;
            line-height: 20px;
            font-size: 8px;
            font-weight: 700;
            text-align: left;
            padding: 0 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-top: 1px solid rgba(0,0,0,0.15);
            border-bottom: 1px solid rgba(0,0,0,0.15);
            box-sizing: border-box;
        }

        .pending {
            background: #f4dd7f;
            color: #9a5a00;
            border-left: 1px solid #d8b84b;
            border-right: 1px solid #d8b84b;
        }

        .confirmed {
            background: #a9c2e4;
            color: #1e429f;
            border-left: 1px solid #7ea6d8;
            border-right: 1px solid #7ea6d8;
        }

        .checked-in {
            background: #a8ddb7;
            color: #166534;
            border-left: 1px solid #7ccf95;
            border-right: 1px solid #7ccf95;
        }

        .checked-out {
            background: #d9dde5;
            color: #374151;
            border-left: 1px solid #b9c0cc;
            border-right: 1px solid #b9c0cc;
        }

        .completed {
            background: #d7cff4;
            color: #5b21b6;
            border-left: 1px solid #baa8ee;
            border-right: 1px solid #baa8ee;
        }

        .cancelled {
            background: #efc1be;
            color: #b42318;
            border-left: 1px solid #e2a29e;
            border-right: 1px solid #e2a29e;
        }

        .rejected {
            background: #efc1be;
            color: #b42318;
            border-left: 1px solid #e2a29e;
            border-right: 1px solid #e2a29e;
        }

        .continued-left {
            border-left-width: 3px !important;
        }

        .continued-right {
            border-right-width: 3px !important;
        }
    </style>
</head>
<body>
@php
    function compactGuestNamePdf($name) {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if (mb_strlen($name) <= 18) return $name;

        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            $first = $parts[0];
            $lastInitial = mb_strtoupper(mb_substr(end($parts), 0, 1));
            return "{$first} {$lastInitial}.";
        }

        return mb_substr($name, 0, 15) . '...';
    }

    function shortenPdf($text, $limit = 42) {
        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit - 3) . '...'
            : $text;
    }

    function barTextPdf($segment) {
        $guest = compactGuestNamePdf($segment['guest'] ?? 'Guest');
        $resource = $segment['label'] ?? '';
        return shortenPdf("{$guest} - {$resource}", 38);
    }
@endphp

@foreach($months as $month)
    <div class="page">
        <div class="title">Reservation Calendar</div>
        <div class="subtitle">
            Range: {{ $rangeStart->format('F Y') }} - {{ $rangeEnd->format('F Y') }}
        </div>
        <div class="month-title">{{ $month['monthDate']->format('F Y') }}</div>

        <table class="legend">
            <tr>
                <td class="pending">Pending</td>
                <td class="confirmed">Confirmed</td>
                <td class="checked-in">Checked-In</td>
                <td class="checked-out">Checked-Out</td>
                <td class="completed">Completed</td>
                <td class="cancelled">Cancelled</td>
                <td style="background:#ffffff;"></td>
            </tr>
        </table>

        <table class="calendar">
            <thead>
                <tr>
                    <th>Sunday</th>
                    <th>Monday</th>
                    <th>Tuesday</th>
                    <th>Wednesday</th>
                    <th>Thursday</th>
                    <th>Friday</th>
                    <th>Saturday</th>
                </tr>
            </thead>
            <tbody>
            @foreach($month['weeks'] as $week)
                {{-- Large breathable date boxes --}}
                <tr class="date-row">
                    @foreach($week['days'] as $day)
                        @php
                            $classes = [];
                            if ($day->month !== $month['monthDate']->month) $classes[] = 'other-month';
                            if ($day->isWeekend()) $classes[] = 'weekend';
                        @endphp
                        <td class="{{ implode(' ', $classes) }}">
                            <div class="day-number">{{ $day->day }}</div>
                        </td>
                    @endforeach
                </tr>

                {{-- Reservation instance lanes under the big boxes --}}
                @for($lane = 0; $lane < $week['laneCount']; $lane++)
                    <tr class="lane-row {{ $lane === ($week['laneCount'] - 1) ? 'last-lane' : '' }}">
                        @for($dow = 0; $dow < 7; $dow++)
                            @php
                                $day = $week['days'][$dow];
                                $classes = [];
                                if ($day->month !== $month['monthDate']->month) $classes[] = 'other-month';
                                if ($day->isWeekend()) $classes[] = 'weekend';

                                $segment = null;
                                foreach ($week['segments'] as $seg) {
                                    if (
                                        $seg['lane'] === $lane &&
                                        $seg['segment_start']->dayOfWeek === $dow
                                    ) {
                                        $segment = $seg;
                                        break;
                                    }
                                }
                            @endphp

                            @if($segment)
                                @php
                                    $span = $segment['segment_end']->dayOfWeek - $segment['segment_start']->dayOfWeek + 1;

                                    $barClasses = [$segment['status']];
                                    if ($segment['continues_from_previous_week'] ?? false) $barClasses[] = 'continued-left';
                                    if ($segment['continues_to_next_week'] ?? false) $barClasses[] = 'continued-right';
                                @endphp

                                <td colspan="{{ $span }}" class="{{ implode(' ', $classes) }}">
                                    <span class="bar {{ implode(' ', $barClasses) }}">
                                        {{ barTextPdf($segment) }}
                                    </span>
                                </td>
                                @php $dow += ($span - 1); @endphp
                            @else
                                <td class="{{ implode(' ', $classes) }}"></td>
                            @endif
                        @endfor
                    </tr>
                @endfor

                <tr class="spacer-row">
                    <td colspan="7"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
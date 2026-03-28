<?php

namespace App\Http\Controllers;

use App\Models\RoomReservation;
use App\Models\VenueReservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReservationCalendarExport extends Controller
{
    public function exportCalendarPdf(Request $request)
    {
        $monthStart = (int) $request->query('start', now()->month);
        $monthEnd   = (int) $request->query('end', now()->month);
        $year       = (int) $request->query('year', now()->year);

        $startMonth = max(1, min(12, $monthStart));
        $endMonth   = max(1, min(12, $monthEnd));
        $year       = max(2020, min(2100, $year));

        if ($startMonth > $endMonth) {
            [$startMonth, $endMonth] = [$endMonth, $startMonth];
        }

        $rangeStart = Carbon::create($year, $startMonth, 1)->startOfMonth()->startOfDay();
        $rangeEnd   = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();

        $months = [];
        for ($month = $startMonth; $month <= $endMonth; $month++) {
            $monthDate = Carbon::create($year, $month, 1)->startOfMonth();
            $months[] = [
                'monthDate' => $monthDate,
                'weeks' => $this->buildMonthWeeks($monthDate),
            ];
        }

        $reservations = $this->loadReservations($rangeStart, $rangeEnd);

        foreach ($months as &$month) {
            foreach ($month['weeks'] as &$week) {
                $week['segments'] = $this->buildWeekSegments(
                    $reservations,
                    $week['start']->copy()->startOfDay(),
                    $week['end']->copy()->endOfDay()
                );
                $week['laneCount'] = max(1, count($week['segments']) ? max(array_column($week['segments'], 'lane')) + 1 : 1);
            }
        }

        $pdf = Pdf::loadView('pdf.reservation-calendar', [
            'rangeStart' => $rangeStart,
            'rangeEnd'   => $rangeEnd,
            'months'     => $months,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            'reservation_calendar_' .
            $rangeStart->format('F') . '_' .
            $rangeEnd->format('F_Y') . '.pdf'
        );
    }

    private function loadReservations(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $items = [];

        $roomReservations = RoomReservation::with(['room', 'user'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->where('Room_Reservation_Check_In_Time', '<=', $rangeEnd)
                  ->where('Room_Reservation_Check_Out_Time', '>=', $rangeStart);
            })
            ->get();

        foreach ($roomReservations as $reservation) {
            $items[] = [
                'id'        => 'room_' . ($reservation->Room_Reservation_ID ?? $reservation->id ?? uniqid()),
                'status'    => strtolower($reservation->Room_Reservation_Status ?? 'pending'),
                'check_in'  => Carbon::parse($reservation->Room_Reservation_Check_In_Time)->startOfDay(),
                'check_out' => Carbon::parse($reservation->Room_Reservation_Check_Out_Time)->startOfDay(),
                'guest'     => optional($reservation->user)->Account_Name ?? optional($reservation->user)->name ?? 'Guest',
                'label'     => 'Room ' . (optional($reservation->room)->Room_Number ?? 'N/A'),
                'type'      => 'room',
                'purpose'   => $reservation->Room_Reservation_Purpose ?? '',
            ];
        }

        $venueReservations = VenueReservation::with(['venue', 'user'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->where('Venue_Reservation_Check_In_Time', '<=', $rangeEnd)
                  ->where('Venue_Reservation_Check_Out_Time', '>=', $rangeStart);
            })
            ->get();

        foreach ($venueReservations as $reservation) {
            $items[] = [
                'id'        => 'venue_' . ($reservation->Venue_Reservation_ID ?? $reservation->id ?? uniqid()),
                'status'    => strtolower($reservation->Venue_Reservation_Status ?? 'pending'),
                'check_in'  => Carbon::parse($reservation->Venue_Reservation_Check_In_Time)->startOfDay(),
                'check_out' => Carbon::parse($reservation->Venue_Reservation_Check_Out_Time)->startOfDay(),
                'guest'     => optional($reservation->user)->Account_Name ?? optional($reservation->user)->name ?? 'Guest',
                'label'     => optional($reservation->venue)->Venue_Name ?? 'Venue',
                'type'      => 'venue',
                'purpose'   => $reservation->Venue_Reservation_Purpose ?? '',
            ];
        }

        usort($items, function ($a, $b) {
            $cmp = $a['check_in']->timestamp <=> $b['check_in']->timestamp;
            if ($cmp !== 0) {
                return $cmp;
            }
            return $a['check_out']->timestamp <=> $b['check_out']->timestamp;
        });

        return $items;
    }

    private function buildMonthWeeks(Carbon $monthDate): array
    {
        $gridStart = $monthDate->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd   = $monthDate->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $days = [];
            $weekStart = $cursor->copy();

            for ($i = 0; $i < 7; $i++) {
                $days[] = $cursor->copy();
                $cursor->addDay();
            }

            $weeks[] = [
                'start' => $weekStart,
                'end'   => $days[6]->copy(),
                'days'  => $days,
            ];
        }

        return $weeks;
    }

    private function buildWeekSegments(array $reservations, Carbon $weekStart, Carbon $weekEnd): array
{
    $segments = [];

    foreach ($reservations as $reservation) {
        if ($reservation['check_out']->lt($weekStart) || $reservation['check_in']->gt($weekEnd)) {
            continue;
        }

        $segStart = $reservation['check_in']->copy()->lt($weekStart)
            ? $weekStart->copy()
            : $reservation['check_in']->copy();

        $segEnd = $reservation['check_out']->copy()->gt($weekEnd)
            ? $weekEnd->copy()
            : $reservation['check_out']->copy();

        $segments[] = [
            'id' => $reservation['id'],
            'status' => $reservation['status'],
            'guest' => $reservation['guest'],
            'label' => $reservation['label'],
            'type' => $reservation['type'],
            'purpose' => $reservation['purpose'],
            'segment_start' => $segStart,
            'segment_end' => $segEnd,
            'continues_from_previous_week' => $reservation['check_in']->lt($weekStart),
            'continues_to_next_week' => $reservation['check_out']->gt($weekEnd),
        ];
    }

    usort($segments, function ($a, $b) {
        $cmp = $a['segment_start']->timestamp <=> $b['segment_start']->timestamp;
        if ($cmp !== 0) {
            return $cmp;
        }
        return $a['segment_end']->timestamp <=> $b['segment_end']->timestamp;
    });

    $laneEnds = [];

    foreach ($segments as $index => $segment) {
        $lane = null;

        foreach ($laneEnds as $laneIndex => $laneEndTs) {
            if ($segment['segment_start']->timestamp > $laneEndTs) {
                $lane = $laneIndex;
                break;
            }
        }

        if ($lane === null) {
            $lane = count($laneEnds);
        }

        $laneEnds[$lane] = $segment['segment_end']->timestamp;
        $segments[$index]['lane'] = $lane;
    }

    return $segments;
}
}
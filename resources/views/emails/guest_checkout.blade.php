<!DOCTYPE html>
<html>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px;">
        <div style="max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;">
            <h2 style="color: #2c3e50;">Thank you for staying with us!</h2>
            <p>Dear {{ $reservation->user->name }},</p>
            <p>This email confirms that you have successfully checked out from Adzu Lantaka Campus.</p>

            @php
                if ($type === 'room') {
                    $accommodationLabel = 'Room ' . ($reservation->room->room_number ?? 'N/A');
                    $accommodationTotal = $reservation->Room_Reservation_Total_Price ?? 0;
                } else {
                    $accommodationLabel = $reservation->venue->name ?? 'Venue';
                    $accommodationTotal = $reservation->Venue_Reservation_Total_Price ?? 0;
                }
                $grandTotal = $accommodationTotal + $foodTotal;
            @endphp

            <div style="background: #f4f4f4; padding: 15px; border-radius: 5px;">
                <strong>Stay Details:</strong><br>
                Accommodation: {{ $accommodationLabel }}<br>
                Check-out Date: {{ now()->format('F d, Y') }}<br><br>

                Accommodation Total: ₱ {{ number_format($accommodationTotal, 2) }}<br>
                @if ($type === 'venue' && $foodTotal > 0)
                    Food Total: ₱ {{ number_format($foodTotal, 2) }}<br>
                @endif
                <strong>Grand Total: ₱ {{ number_format($grandTotal, 2) }}</strong>
            </div>

            <p>We hope you had a pleasant experience. We would love to see you again soon!</p>

            <p>Best Regards,<br><strong>Lantaka Campus Team</strong></p>
        </div>
    </body>
</html>

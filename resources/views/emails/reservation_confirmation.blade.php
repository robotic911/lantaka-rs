<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; line-height: 1.6; }
        .container { width: 80%; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
        .header { font-weight: bold; font-size: 18px; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .status { font-weight: bold; color: #d4af37; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">🏨 Lantaka Online Reservation – Your Reservation Status Update</div>
        <p>Dear {{ $reservation->user?->Account_Name ?? 'Guest' }},</p>
        <p>Below are the details of your reservation:</p>

        <div class="details">
            <strong>Reservation Information</strong><br>
            Room/Venue: {{ $reservation->room ? ($reservation->room?->Room_Number ?? 'N/A') : ($reservation->venue?->Venue_Name ?? 'N/A') }}<br>
            Date of Reservation: {{ now()->format('F j, Y') }}<br>
            Check-in Date: {{ \Carbon\Carbon::parse($reservation->Room_Reservation_Check_In_Time ?? $reservation->Venue_Reservation_Check_In_Time)->format('F j, Y') }}<br>
            Check-out Date: {{ \Carbon\Carbon::parse($reservation->Room_Reservation_Check_Out_Time ?? $reservation->Venue_Reservation_Check_Out_Time)->format('F j, Y') }}<br>
            Number of Guests: {{ $reservation->Room_Reservation_Pax ?? $reservation->Venue_Reservation_Pax }}<br>
            
            @if($reservation->foods->isNotEmpty())
                Food Selection: {{ $reservation->foods->pluck('Food_Name')->implode(', ') }}
            @endif
        </div>

        <p><strong>Reservation Status:</strong></p>
        <p class="status">🟡 Pending</p>
        <p>(Your reservation is awaiting confirmation from our staff. You will receive another email once it is approved.)</p>

        <p>Warm regards,<br>Adzu Lantaka Campus<br>Administrative Office</p>
    </div>
</body>
</html>
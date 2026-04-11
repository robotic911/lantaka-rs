<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cancellation Request Approved</title>
<style>
body{margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;color:#333}
.wrap{max-width:580px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.hdr{background:#1a2e4a;padding:32px 40px;text-align:center}
.hdr h1{margin:0;color:#fff;font-size:20px;font-weight:600;letter-spacing:.5px}
.hdr p{margin:6px 0 0;color:#a8bdd4;font-size:13px}
.banner{background:#fff7ed;border-bottom:3px solid #f97316;padding:14px 40px;text-align:center;font-size:14px;font-weight:700;color:#9a3412;letter-spacing:.3px}
.banner-icon{font-size:22px;vertical-align:middle;margin-right:6px}
.body{padding:32px 40px}
.body p{margin:0 0 14px;font-size:15px;line-height:1.6;color:#444}
.detail-box{background:#f8f9fa;border:1px solid #e0e0e0;border-radius:6px;padding:18px 22px;margin:20px 0}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #efefef;font-size:14px}
.detail-row:last-child{border-bottom:none}
.detail-label{color:#666;font-weight:600}
.detail-value{color:#1a2e4a;font-weight:700}
.status-chip{display:inline-block;background:#fff7ed;border:1px solid #f97316;color:#9a3412;padding:3px 12px;border-radius:20px;font-size:13px;font-weight:700}
.note-box{background:#fff7ed;border-left:4px solid #f97316;padding:14px 18px;border-radius:0 6px 6px 0;margin:20px 0;font-size:13px;color:#7c2d12}
.note-box strong{display:block;margin-bottom:4px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#9a3412}
.info{background:#f0f9ff;border-left:4px solid #38bdf8;padding:14px 18px;border-radius:0 6px 6px 0;margin:20px 0;font-size:13px;color:#0c4a6e}
.footer{background:#f4f6f9;padding:18px 40px;text-align:center;border-top:1px solid #e8ecf0}
.footer p{margin:0;font-size:12px;color:#999;line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <h1>Lantaka Reservation System</h1>
    <p>Ateneo de Zamboanga University</p>
  </div>

  <div class="banner">
    <span class="banner-icon">✔</span> Cancellation Request Approved
  </div>

  <div class="body">
    <p>Hello, <strong>{{ $reservation->user?->Account_Name ?? 'Guest' }}</strong>.</p>
    <p>Your cancellation request has been <strong>reviewed and approved</strong> by our staff. The reservation details are shown below.</p>

    @php
      if ($type === 'room') {
        $accLabel = 'Room ' . ($reservation->room?->Room_Number ?? 'N/A');
        $checkIn  = $reservation->Room_Reservation_Check_In_Time;
        $checkOut = $reservation->Room_Reservation_Check_Out_Time;
      } else {
        $accLabel = $reservation->venue?->Venue_Name ?? 'Venue';
        $checkIn  = $reservation->Venue_Reservation_Check_In_Time;
        $checkOut = $reservation->Venue_Reservation_Check_Out_Time;
      }
    @endphp

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">{{ ucfirst($type) }}:</span>
        <span class="detail-value">{{ $accLabel }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Check-in:</span>
        <span class="detail-value">{{ \Carbon\Carbon::parse($checkIn)->format('F d, Y') }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Check-out:</span>
        <span class="detail-value">{{ \Carbon\Carbon::parse($checkOut)->format('F d, Y') }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Status:</span>
        <span class="detail-value"><span class="status-chip">Cancelled</span></span>
      </div>
      @if($reservation->cancellation_reason)
      <div class="detail-row">
        <span class="detail-label">Your reason:</span>
        <span class="detail-value" style="color:#555;font-weight:500;text-align:right;max-width:60%">{{ $reservation->cancellation_reason }}</span>
      </div>
      @endif
    </div>

    @if($adminNote)
    <div class="note-box">
      <strong>Note from Lantaka staff:</strong>
      {{ $adminNote }}
    </div>
    @endif

    <div class="info">
      If you wish to make a new reservation or have questions, feel free to reach out to us at <strong>lantaka@adzu.edu.ph</strong>.
    </div>

    <p>Thank you for reaching out to us. We hope to welcome you again soon.<br><strong>Lantaka Reservation System Team</strong></p>
  </div>

  <div class="footer">
    <p>This is an automated message. Please do not reply directly to this email.</p>
    <p>&copy; {{ date('Y') }} Lantaka Reservation System.</p>
  </div>
</div>
</body>
</html>

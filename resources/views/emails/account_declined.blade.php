<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Registration Update</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">

          {{-- Header --}}
          <tr>
            <td style="background:#1e3a5f;border-radius:10px 10px 0 0;padding:32px 40px;text-align:center;">
              <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8bafd4;font-weight:600;">Lantaka Reservation System</p>
              <h1 style="margin:10px 0 0;font-size:22px;font-weight:700;color:#ffffff;">Registration Update</h1>
            </td>
          </tr>

          {{-- Body --}}
          <tr>
            <td style="background:#ffffff;padding:36px 40px;">

              <p style="margin:0 0 6px;font-size:15px;color:#374151;font-weight:600;">Hello, {{ $user->name }}</p>
              <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
                Thank you for registering with the Lantaka Room and Venue Reservation System. After reviewing your registration request, we regret to inform you that your account could not be approved at this time.
              </p>

              {{-- Reason block --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:18px 22px;">
                    <p style="margin:0 0 6px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#ef4444;font-weight:700;">Status: Not Approved</p>
                    <p style="margin:0;font-size:13px;color:#7f1d1d;line-height:1.6;">
                      This may be due to incomplete, unreadable, or unverifiable identification information submitted during registration. Please ensure that all details match your official identification documents.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.6;">
                You are welcome to re-apply with the correct information. If you believe this decision was made in error or need further clarification, please reach out to the system administrator.
              </p>

              <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                We appreciate your understanding and patience.
              </p>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 10px 10px;padding:20px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#9ca3af;">
                This is an automated message from <strong style="color:#6b7280;">{{ $campusName }}</strong>.<br>
                Please do not reply to this email.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Approved</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr>
      <td style= "align-items: center">
        <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">

          {{-- Header --}}
          <tr>
            <td style="background:#1e3a5f;border-radius:10px 10px 0 0;padding:32px 40px;text-align:center;">
              <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#8bafd4;font-weight:600;">Lantaka Reservation System</p>
              <h1 style="margin:10px 0 0;font-size:22px;font-weight:700;color:#ffffff;">Account Approved</h1>
            </td>
          </tr>

          {{-- Body --}}
          <tr>
            <td style="background:#ffffff;padding:36px 40px;">

              <p style="margin:0 0 6px;font-size:15px;color:#374151;font-weight:600;">Hello, {{ $user->Account_Name }}</p>
              <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
                Your registration request has been reviewed and approved. You may now access the Lantaka Room and Venue Reservation System using the credentials below.
              </p>

              {{-- Credentials block --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;font-weight:700;">Your Login Credentials</p>
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;min-width:90px;">Username: </td>
                        <td style="font-size:13px;color:#1f2937;font-weight:600;padding:4px 0;">{{ $user->username }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Password: </td>
                        <td style="padding:4px 0;">
                          <span style="display:inline-block;background:#1e3a5f;color:#ffffff;font-family:'Courier New',monospace;font-size:15px;font-weight:700;letter-spacing:2px;padding:6px 14px;border-radius:5px;">{{ $plainPassword }}</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- Warning --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:14px 18px;">
                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.55;">
                      <strong>⚠ Important:</strong> This is a system-generated password. Please change it immediately after your first login to keep your account secure.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                If you did not register for an account or believe this message was sent in error, please disregard this email or contact our support team.
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

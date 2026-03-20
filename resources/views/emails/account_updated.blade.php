<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Details Updated</title>
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
              <h1 style="margin:10px 0 0;font-size:22px;font-weight:700;color:#ffffff;">Account Updated</h1>
            </td>
          </tr>

          {{-- Body --}}
          <tr>
            <td style="background:#ffffff;padding:36px 40px;">

              <p style="margin:0 0 6px;font-size:15px;color:#374151;font-weight:600;">Hello, {{ $user->Account_Name }}</p>
              <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.6;">
                This is to inform you that your account details have been updated by a system administrator.
              </p>

              {{-- What changed --}}
              @if(!empty($changedFields))
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#3b82f6;font-weight:700;">Fields Updated</p>
                    @foreach($changedFields as $field)
                      <p style="margin:0 0 4px;font-size:13px;color:#1e3a5f;">✔ {{ $field }}</p>
                    @endforeach
                  </td>
                </tr>
              </table>
              @endif

              {{-- Current account info --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 12px;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#9ca3af;font-weight:700;">Current Account Information</p>
                    <table cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;min-width:110px;">Name</td>
                        <td style="font-size:13px;color:#1f2937;font-weight:600;padding:4px 0;">{{ $user->Account_Name }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Username</td>
                        <td style="font-size:13px;color:#1f2937;font-weight:600;padding:4px 0;">{{ $user->Account_Username }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Email</td>
                        <td style="font-size:13px;color:#1f2937;font-weight:600;padding:4px 0;">{{ $user->Account_Email }}</td>
                      </tr>
                      <tr>
                        <td style="font-size:13px;color:#6b7280;padding:4px 0;">Phone</td>
                        <td style="font-size:13px;color:#1f2937;font-weight:600;padding:4px 0;">{{ $user->Account_Phone ?? '—' }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- Security notice --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:14px 18px;">
                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.55;">
                      <strong>⚠ Security Notice:</strong> If you did not authorize these changes or suspect unauthorized access to your account, please contact the system administrator immediately.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">
                No further action is required if this update was expected.
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

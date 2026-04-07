<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Your New Password</title>
<style>
body{margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;color:#333}
.wrap{max-width:580px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
.hdr{background:#1a2e4a;padding:32px 40px;text-align:center}
.hdr h1{margin:0;color:#fff;font-size:20px;font-weight:600;letter-spacing:.5px}
.hdr p{margin:6px 0 0;color:#a8bdd4;font-size:13px}
.banner{background:#f0fdf4;border-bottom:3px solid #22c55e;padding:14px 40px;text-align:center;font-size:14px;font-weight:700;color:#14532d;letter-spacing:.3px}
.banner-icon{font-size:22px;vertical-align:middle;margin-right:6px}
.body{padding:32px 40px}
.body p{margin:0 0 14px;font-size:15px;line-height:1.6;color:#444}
.password-box{background:#f8f9fa;border:2px dashed #22c55e;border-radius:8px;padding:20px;text-align:center;margin:24px 0}
.password-box .label{font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px}
.password-box .password{font-size:24px;font-weight:700;color:#1a2e4a;font-family:'Courier New',monospace;letter-spacing:2px}
.warning{background:#fffbeb;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:0 6px 6px 0;margin:20px 0;font-size:13px;color:#78350f}
.warning strong{display:block;margin-bottom:4px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#92400e}
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
    <span class="banner-icon">🔑</span> Password Reset
  </div>

  <div class="body">
    <p>Hello, <strong>{{ $user->Account_Name ?? 'Guest' }}</strong>.</p>
    <p>We received a request to reset the password for your account. A new password has been generated for you:</p>

    <div class="password-box">
      <div class="label">Your New Password</div>
      <div class="password">{{ $plainPassword }}</div>
    </div>

    <div class="warning">
      <strong>Important</strong>
      Please log in using this password and change it immediately from your account settings. Do not share this password with anyone.
    </div>

    <p>Your username remains the same: <strong>{{ $user->Account_Username }}</strong></p>

    <div class="info">
      If you did not request a password reset, please contact us immediately at <strong>lantaka@adzu.edu.ph</strong> so we can secure your account.
    </div>

    <p>Thank you.<br><strong>Lantaka Reservation System Team</strong></p>
  </div>

  <div class="footer">
    <p>This is an automated message. Please do not reply directly to this email.</p>
    <p>&copy; {{ date('Y') }} Lantaka Reservation System.</p>
  </div>
</div>
</body>
</html>

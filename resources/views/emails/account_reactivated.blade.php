<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Reactivated</title>
  <style>
    body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color: #333; }
    .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .header { background-color: #1a2e4a; padding: 32px 40px; text-align: center; }
    .header h1 { margin: 0; color: #ffffff; font-size: 20px; font-weight: 600; letter-spacing: 0.5px; }
    .header p { margin: 6px 0 0; color: #a8bdd4; font-size: 13px; }
    .body { padding: 36px 40px; }
    .body p { margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #444; }
    .status-badge { display: inline-block; background-color: #e6f4ea; color: #2e7d32; border: 1px solid #a5d6a7; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 24px; }
    .credentials { background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px 24px; margin: 20px 0; }
    .credentials p { margin: 0 0 10px; font-size: 13px; color: #666; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .credentials .value { font-family: 'Courier New', Courier, monospace; font-size: 16px; color: #1a2e4a; font-weight: 700; letter-spacing: 1px; }
    .warning { background-color: #fff8e1; border-left: 4px solid #f9a825; padding: 14px 18px; border-radius: 0 6px 6px 0; margin: 24px 0; font-size: 14px; color: #5d4037; }
    .warning strong { display: block; margin-bottom: 4px; }
    .footer { background-color: #f4f6f9; padding: 20px 40px; text-align: center; border-top: 1px solid #e8ecf0; }
    .footer p { margin: 0; font-size: 12px; color: #999; line-height: 1.5; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>Lantaka Reservation System</h1>
      <p>Account Management</p>
    </div>
    <div class="body">
      <p>Hello, <strong>{{ $user->Account_Name }}</strong>.</p>
      <span class="status-badge">✓ Account Reactivated</span>
      <p>
        Your account has been reactivated by an administrator. You can now log in
        to the Lantaka Reservation System using the credentials below.
      </p>

      <div class="credentials">
        <p>Username</p>
        <span class="value">{{ $user->Account_Username }}</span>
      </div>
      <div class="credentials">
        <p>Temporary Password</p>
        <span class="value">{{ $plainPassword }}</span>
      </div>

      <div class="warning">
        <strong>Security Notice</strong>
        Please change your password immediately after your first login to keep your account secure.
      </div>

      <p>If you did not expect this reactivation or believe this is an error, please contact your system administrator.</p>
      <p>Thank you,<br><strong>Lantaka Reservation System Team</strong></p>
    </div>
    <div class="footer">
      <p>This is an automated message. Please do not reply directly to this email.</p>
      <p>&copy; {{ date('Y') }} Lantaka Reservation System. All rights reserved.</p>
    </div>
  </div>
</body>
</html>

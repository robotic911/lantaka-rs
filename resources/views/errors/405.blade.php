<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>405 — Method Not Allowed | Lantaka Reservation System</title>
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Alexandria', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: url('/images/index_background.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      position: relative;
    }

    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(26, 31, 77, 0.65);
      z-index: 0;
    }

    .error-card {
      position: relative;
      z-index: 1;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.25);
      padding: 56px 48px 48px;
      text-align: center;
      max-width: 500px;
      width: 90%;
      animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .error-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #fef3c7;
      margin: 0 auto 24px;
    }

    .error-icon svg { color: #b45309; }

    .error-code {
      font-size: 5rem;
      font-weight: 900;
      color: #2c3e7f;
      line-height: 1;
      letter-spacing: -2px;
    }

    .error-title {
      font-size: 1.35rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-top: 10px;
    }

    .error-message {
      font-size: 0.9rem;
      color: #6b7280;
      margin-top: 12px;
      line-height: 1.6;
    }

    .divider {
      border: none;
      border-top: 1px solid #f0f0f0;
      margin: 28px 0;
    }

    .btn-group {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 11px 26px;
      background: #ffc107;
      color: #1a1a1a;
      border: none;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s, transform 0.15s;
    }

    .btn-primary:hover {
      background: #ffb300;
      transform: translateY(-1px);
    }

    .btn-secondary {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 11px 22px;
      background: #f3f4f6;
      color: #374151;
      border: none;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }

    .btn-secondary:hover { background: #e5e7eb; }

    .brand-footer {
      margin-top: 32px;
      font-size: 0.75rem;
      color: #9ca3af;
    }

    .brand-footer strong { color: #2c3e7f; }
  </style>
</head>
<body>

  <div class="overlay"></div>

  {{-- Resolve the correct "safe" destination based on the logged-in role --}}
  @php
    $dashboardUrl = url('/'); // default: guests go to home

    if (auth()->check()) {
      $role = auth()->user()->Account_Role;
      if (in_array($role, ['admin', 'staff', 'Admin', 'Staff'])) {
        $dashboardUrl = route('employee.dashboard');
      } elseif ($role === 'client') {
        $dashboardUrl = route('client.my_reservations');
      }
    }
  @endphp

  <div class="error-card">

    <div class="error-icon">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/>
        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
      </svg>
    </div>

    <div class="error-code">405</div>
    <div class="error-title">Method Not Allowed</div>
    <p class="error-message">
      That action isn't permitted here. You may have submitted<br>
      a form incorrectly or followed an invalid link.
    </p>

    <hr class="divider">

    <div class="btn-group">
      <a href="{{ $dashboardUrl }}" class="btn-secondary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Go Back
      </a>
      <a href="{{ $dashboardUrl }}" class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        </svg>
        @auth
          @if(in_array(auth()->user()->Account_Role, ['admin', 'staff', 'Admin', 'Staff']))
            Go to Dashboard
          @else
            Go to My Reservations
          @endif
        @else
          Go to Home
        @endauth
      </a>
    </div>

    <div class="brand-footer">
      <strong>Lantaka</strong> Room &amp; Venue Reservation System &mdash; Ateneo de Zamboanga University
    </div>

  </div>

</body>
</html>

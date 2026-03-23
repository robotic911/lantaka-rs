<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>503 — Service Unavailable | Lantaka Reservation System</title>
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
      background: #f3f4f6;
      margin: 0 auto 24px;
    }

    .error-icon svg { color: #6b7280; }

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

    /* Maintenance badge */
    .maintenance-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      color: #1e40af;
      border-radius: 999px;
      padding: 5px 14px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: .3px;
      margin-top: 16px;
    }

    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #3b82f6;
      animation: pulse 1.4s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%       { opacity: 0.5; transform: scale(0.75); }
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

    .retry-hint {
      font-size: 0.78rem;
      color: #9ca3af;
      margin-top: 14px;
    }

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

  <div class="error-card">

    <div class="error-icon">
      <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>

    <div class="error-code">503</div>
    <div class="error-title">Service Unavailable</div>
    <p class="error-message">
      The Lantaka Reservation System is currently undergoing<br>
      scheduled maintenance. We'll be back shortly.
    </p>

    <div class="maintenance-badge">
      <span class="pulse-dot"></span>
      Maintenance in Progress
    </div>

    @if(isset($exception) && $exception->getMessage())
    <p style="font-size:0.8rem; color:#9ca3af; margin-top:14px;">
      {{ $exception->getMessage() }}
    </p>
    @endif

    <hr class="divider">

    <div class="btn-group">
      <a href="{{ url('/') }}" class="btn-primary" onclick="this.textContent='Retrying…'">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="23 4 23 10 17 10"/>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Try Again
      </a>
    </div>

    <p class="retry-hint">Please try refreshing the page in a few minutes.</p>

    <div class="brand-footer">
      <strong>Lantaka</strong> Room &amp; Venue Reservation System &mdash; Ateneo de Zamboanga University
    </div>

  </div>

</body>
</html>

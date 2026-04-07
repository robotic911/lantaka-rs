<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Lantaka Reservation System</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        .back-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 14px;
            color: #1a2e4a;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover { text-decoration: underline; }
        .info-text {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="background-overlay"></div>

    <main class="login-container">
        <div class="login-card">
            <!-- Logo Section -->
            <div class="logo-section">
                <img src="{{ asset('images/adzu_logo.png') }}" class="logo">
            </div>

            <!-- University Text -->
            <p class="university-name">Ateneo de Zamboanga University</p>

            <!-- Main Heading -->
            <h1 class="main-heading">Forgot Password</h1>

            <p class="info-text">Enter the email address associated with your client account and we'll send you a new password.</p>

            <!-- Flash Messages -->
            @if(session('success'))
                <div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="color: red; margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Forgot Password Form -->
            <form class="login-form" method="POST" action="{{ route('forgot.password.send') }}">
                @csrf
                <div class="input-group">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input
                        type="email"
                        name="Account_Email"
                        class="form-input"
                        placeholder="Email Address"
                        value="{{ old('Account_Email') }}"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="login-btn">SEND NEW PASSWORD</button>
            </form>

            <p class="signup-text">
                <a href="{{ route('login') }}" class="signup-link">← Back to Login</a>
            </p>
        </div>
    </main>
</body>
</html>

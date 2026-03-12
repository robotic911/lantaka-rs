<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lantaka Room and Venue Reservation Portal</title>
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    </head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">

            <div class="logo-section">
                <img src="{{ asset('images/adzu_logo.png') }}" class="logo">
                <div class="header-text">
                    <p class="subtitle-text">Ateneo de Zamboanga University</p>
                    <h1 class="header-title">Lantaka Room and Venue Reservation Portal</h1>
                    <h1 class="tagline"> &lt;Lantaka Online Room & Venue Reservation System/&gt; </h1>
                </div>
            </div>
            
            <nav class="nav">
                <a href="{{ route('client.room_venue') }}"class="nav-link">Accommodation</a>
                <a href="{{ route('login') }}" class="nav-link">Login</a></nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <p class="welcome-text">WELCOME TO</p>
            <h2 class="hero-title">LANTAKA CAMPUS</h2>
            <p class="hero-subtitle">The Ateneo de Zamboanga University Spirituality, Formation, and Training Center<br>Since 2019</p>
            <form method="GET" action="{{ route('client.room_venue') }}">
            <button type="submit" class="explore-btn">EXPLORE</button>
            </form>
            <p class="login-prompt" style="margin-top: 20px; font-size: 0.95rem; font-weight: 300;">
                Want to make a reservation? Please <a href="{{ route('login') }}" style="color: #FFD700; text-decoration: none; font-weight: 500;">Login</a> or <a href="{{ route('signup') }}" style="color: #FFD700; text-decoration: none; font-weight: 500;">Sign up</a> to get started.
            </p>
            
        </div>
    </section> 
</body>
</html>

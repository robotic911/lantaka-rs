<link rel="stylesheet" href="{{asset('css/header.css')}}">
@vite(['resources/js/top_nav.js'])

    <div class="header-container">
        <a href="{{ url('/') }}">
            <div class="logo-section">
                <img src="{{ asset('images/adzu_logo.png') }}" class="logo">
                <div class="header-text">
                    <p class="subtitle-text">Ateneo de Zamboanga University</p>
                    <h1 class="header-title">Lantaka Room and Venue Reservation Portal</h1>
                    <h1 class="tagline"> &lt;Lantaka Online Room & Venue Reservation System/&gt; </h1>
                </div>
            </div>
        </a>

        <nav class="nav">
            <a href="{{ route('client.room_venue') }}" 
               class="nav-link nav-item {{ request()->routeIs('client.room_venue') ? 'active' : '' }}">
               Accommodation
            </a>

            @guest
                <a href="{{ route('login') }}" class="nav-link">Login</a>
            @endguest

            @auth
                <a href="{{ route('client.my_bookings') }}" 
                   class="nav-link nav-item {{ request()->routeIs('client.my_bookings') ? 'active' : '' }}">
                   My Booking
                </a>
                
                <a href="{{ route('client.my_reservations') }}" 
                   class="nav-link nav-item {{ request()->routeIs('client.my_reservations') ? 'active' : '' }}">
                   My Reservations
                </a>

                <button class="icon-btn">
                    <img src="{{ asset(path: 'images/logo/topnav/notification-bell.svg') }}" alt="reservations">
                </button>

                <div class="user-profile" id="open-modal">
                    <div class="user-avatar">
                        <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                    </div>
                    <div class="user-info">
                        <p class="user-name">{{ Auth::user()->username ?? 'Client' }}</p>
                    </div>
                </div>

                <div class="user-profile-modal">
                    <a href="{{ route('client.account') }}" class="modal-link">
                        <button class="btn-view-account">View your account</button>
                    </a>
                    <form class="modal-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout">Logout</button>
                    </form>
                </div>
            @endauth

        </nav>
    </div>

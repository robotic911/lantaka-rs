<link rel="stylesheet" href="{{ asset('css/employee_side_nav.css') }}">

<div class="logo">
            <div class="logo-icon">
              <img src="{{ asset('images/adzu_logo.png') }}" class="logo-image">
            </div>
            <div class="logo-text">
                <div class="logo-subtitle">Ateneo de Zamboanga University</div>
                <div class="logo-title">Lantaka Room and Venue Reservation System
                </div>
            </div>
        </div>

        <nav class="nav-menu">
        <a href="{{route('employee.dashboard')}}" class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
            <span class="icon dashboard-icon"></span>
            <span>Dashboard</span>
        </a>

        <a href="{{route('employee.reservations')}}" class="nav-item {{ request()->routeIs('employee.reservations') || request()->routeIs('employee.reservations.specific') ? 'active' : '' }}">
            <span class="icon reservation-icon"></span>
            <span>Reservation</span>
        </a>

        <a href="{{route('employee.guest')}}" class="nav-item {{ request()->routeIs('employee.guest') || request()->routeIs('employee.guests.specific')? 'active' : '' }}">
            <span class="icon guest-icon"></span>
            <span>Guest</span>
        </a>

        <a href="{{route('employee.room_venue')}}" class="nav-item {{ request()->routeIs('employee.room_venue') ? 'active' : '' }}">
            <span class="icon rooms-icon"></span>
            <span>Rooms / Venue</span>
        </a>

        @if(Auth::user()->Account_Role === 'admin')
        <a href="{{route('employee.accounts')}}" class="nav-item {{ request()->routeIs('employee.accounts') ? 'active' : '' }}">
            <span class="icon accounts-icon"></span>
            <span>Accounts</span>
        </a>



        <a href="{{route('employee.eventlogs')}}" class="nav-item {{ request()->routeIs('employee.eventlogs') ? 'active' : '' }}">
            <span class="icon logs-icon"></span>
            <span>Event Logs</span>
        </a>
        @endif
        </nav>

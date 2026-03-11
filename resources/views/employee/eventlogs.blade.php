@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/employee_eventlogs.css') }}">

@section('content')
<main class="main-content">
  <div class="logs-container">
    <h1 class="page-title">Action Logs</h1>

    <!-- Search and Filter Section -->
    <div class="logs-controls">
      <div class="search-bar">
        <input type="text" placeholder="Search" class="search-input">
        <span class="search-icon">🔍</span>
      </div>
      <div class="filter-controls">
        <div class="filters-actions">
            <select class="status-filter">
              <option>Created</option>
              <option>Approved</option>
              <option>Declined</option>
              <option>Cancelled</option>
              <option>Checked-in</option>
              <option>Checked-out</option>
            </select>
        </div>
      </div>
    </div>

    <!-- Action Logs Entries -->
    <div class="logs-list">
      <!-- Log Entry 1 -->
      <div class="log-entry">
        <div class="log-header">
          <h3 class="log-action">Suzie Kow created a reservation</h3>
          <span class="log-badge created">Created</span>
        </div>
        <div class="log-details">
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Client</p>
              <p class="log-value">Tom Cruz</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-in</p>
              <p class="log-value">Feb 15, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-out</p>
              <p class="log-value">Feb 16, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
            <img src="{{ asset(path: 'images/logo/sidenav/rooms-venue-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Reservation Type</p>
              <p class="log-value">Room and Venue</p>
            </div>
          </div>
        </div>
        <p class="log-timestamp">9/26/2025 09:23:03</p>
      </div>

      <!-- Log Entry 2 -->
      <div class="log-entry">
        <div class="log-header">
          <h3 class="log-action">Suzie Kow declined a reservation</h3>
          <span class="log-badge declined">Declined</span>
        </div>
        <div class="log-details">
          <div class="log-detail-item">
            <span class="log-icon">
            <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Client</p>
              <p class="log-value">Aurey Kow</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-in</p>
              <p class="log-value">Feb 14, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
             <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-out</p>
              <p class="log-value">Feb 16, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
            <img src="{{ asset(path: 'images/logo/sidenav/rooms-venue-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Reservation Type</p>
              <p class="log-value">Room</p>
            </div>
          </div>
        </div>
        <p class="log-timestamp">9/26/2025 09:23:03</p>
      </div>

      <!-- Log Entry 3 -->
      <div class="log-entry">
        <div class="log-header">
          <h3 class="log-action">Suzie Kow approved a reservation</h3>
          <span class="log-badge approved">Approved</span>
        </div>
        <div class="log-details">
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Client</p>
              <p class="log-value">Harold Aryu</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-in</p>
              <p class="log-value">Feb 11, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
              <img src="{{ asset(path: 'images/logo/sidenav/reservation-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Check-out</p>
              <p class="log-value">Feb 12, 2024</p>
            </div>
          </div>
          <div class="log-detail-item">
            <span class="log-icon">
            <img src="{{ asset(path: 'images/logo/sidenav/rooms-venue-icon.svg') }}" alt="reservations">
            </span>
            <div>
              <p class="log-label">Reservation Type</p>
              <p class="log-value">Venue</p>
            </div>
          </div>
        </div>
        <p class="log-timestamp">9/26/2025 09:23:03</p>
      </div>
    </div>
  </div>
  </main>
@endsection
@extends('layouts.client')
<title>My Account - Lantaka Portal</title>
<link rel="stylesheet" href="{{ asset('css/client_account.css') }}">

@section('content')

@php
  $user     = Auth::user();
  $initials = collect(explode(' ', $user->Account_Name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
  $statusClass = strtolower($user->Account_Status ?? 'pending');
@endphp

{{-- Flash messages --}}
@if(session('success'))
  <div class="flash-success">✓ {{ session('success') }}</div>
@endif
@if(session('error') || $errors->any())
  <div class="flash-error">
    @if(session('error'))
      {{ session('error') }}
    @else
      @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
    @endif
  </div>
@endif

<div class="account-wrapper">

  {{-- ── Left: Profile Card ── --}}
  <div class="profile-card">
    <div class="profile-card-banner"></div>
    <div class="profile-card-body">

      <div class="profile-avatar-wrap">
        @if($user->valid_id_path)
          <img src="{{ media_url($user->valid_id_path) }}" alt="avatar">
        @else
          <span class="profile-avatar-initials">{{ $initials }}</span>
        @endif
      </div>

      <p class="profile-name">{{ $user->Account_Name }}</p>
      <span class="profile-status-badge {{ $statusClass }}">{{ ucfirst($statusClass) }}</span>

      <hr class="profile-divider">

      <div class="profile-info-list">

        <div class="profile-info-row">
          <div class="profile-info-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div class="profile-info-text">
            <span class="profile-info-label">Email</span>
            <span class="profile-info-value">{{ $user->Account_Email }}</span>
          </div>
        </div>

        <div class="profile-info-row">
          <div class="profile-info-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.45 2 2 0 0 1 3.59 1.27h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.29 6.29l.89-.89a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </div>
          <div class="profile-info-text">
            <span class="profile-info-label">Phone</span>
            <span class="profile-info-value">{{ $user->Account_Phone ?? '—' }}</span>
          </div>
        </div>

        <div class="profile-info-row">
          <div class="profile-info-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
          </div>
          <div class="profile-info-text">
            <span class="profile-info-label">User Type</span>
            <span class="profile-info-value">{{ $user->Account_Type ?? '—' }}</span>
          </div>
        </div>

        @if($user->affiliation)
        <div class="profile-info-row">
          <div class="profile-info-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <div class="profile-info-text">
            <span class="profile-info-label">Affiliation</span>
            <span class="profile-info-value">{{ $user->affiliation }}</span>
          </div>
        </div>
        @endif

      </div>

      {{-- Valid ID --}}
      <div class="valid-id-section">
        <p class="valid-id-label">Valid ID</p>
        <img src="{{ $user->valid_id_path ? media_url($user->valid_id_path) : asset('images/placeholder_id.svg') }}"
             alt="Valid ID" class="valid-id-img">
      </div>

    </div>
  </div>

  {{-- ── Right Column ── --}}
  <div class="account-right">

    {{-- Stats --}}
    <div class="stats-row">
      <div class="stat-card">
        <span class="stat-card-label">Total</span>
        <span class="stat-card-value">{{ $totalCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-card-label">Pending</span>
        <span class="stat-card-value c-pending">{{ $pendingCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-card-label">Confirmed</span>
        <span class="stat-card-value c-confirmed">{{ $confirmedCount }}</span>
      </div>
      <div class="stat-card">
        <span class="stat-card-label">Completed</span>
        <span class="stat-card-value c-completed">{{ $completedCount }}</span>
      </div>
    </div>

    {{-- Edit Profile --}}
    <div class="account-panel">
      <div class="account-panel-header">
        <span class="account-panel-title">Edit Profile</span>
      </div>
      <div class="account-panel-body">
        <form action="{{ route('client.account.update') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="edit-form-grid">

            <div class="edit-form-group">
              <label class="edit-form-label" for="name">Full Name</label>
              <input class="edit-form-input" type="text" id="name" name="name"
                     value="{{ old('name', $user->Account_Name) }}" required>
            </div>

            <div class="edit-form-group">
              <label class="edit-form-label" for="username">Username</label>
              <input class="edit-form-input" type="text" id="username" name="username"
                     value="{{ old('username', $user->Account_Username) }}" required>
            </div>

            <div class="edit-form-group">
              <label class="edit-form-label" for="email">Email</label>
              <input class="edit-form-input" type="email" id="email" name="email"
                     value="{{ old('email', $user->Account_Email) }}" required>
            </div>

            <div class="edit-form-group">
              <label class="edit-form-label" for="phone">Phone Number</label>
              <input class="edit-form-input" type="text" id="phone" name="phone"
                     value="{{ old('phone', $user->Account_Phone) }}" placeholder="e.g. 09171234567">
            </div>

            <div class="edit-form-group">
              <label class="edit-form-label" for="password">New Password</label>
              <div class="password-field-wrap">
                <input class="edit-form-input" type="password" id="password" name="password"
                       placeholder="Leave blank to keep current">
                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                  <svg id="eye-password" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
              <span class="edit-form-hint">Leave blank to keep your current password.</span>
            </div>

            <div class="edit-form-group">
              <label class="edit-form-label" for="password_confirmation">Confirm New Password</label>
              <div class="password-field-wrap">
                <input class="edit-form-input" type="password" id="password_confirmation"
                       name="password_confirmation" placeholder="Repeat new password">
                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="edit-form-actions">
              <button type="submit" class="btn-save">Save Changes</button>
            </div>

          </div>
        </form>
      </div>
    </div>

    {{-- Recent Reservations --}}
    <div class="account-panel">
      <div class="account-panel-header">
        <span class="account-panel-title">Recent Reservations</span>
        <a href="{{ route('client.my_reservations') }}"
           style="font-size:0.82rem; color:#2c3e7f; font-weight:600; text-decoration:none;">
          View all →
        </a>
      </div>
      <div class="account-panel-body" style="padding: 0;">
        @if($recentReservations->isEmpty())
          <div class="empty-state">No reservations yet.</div>
        @else
          <table class="res-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Accommodation</th>
                <th>Type</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentReservations as $res)
              <tr>
                <td style="color:#9ca3af; font-size:0.78rem;">
                  #{{ str_pad($res['id'], 5, '0', STR_PAD_LEFT) }}
                </td>
                <td style="font-weight:600;">{{ $res['name'] }}</td>
                <td style="text-transform:capitalize;">{{ $res['type'] }}</td>
                <td>{{ \Carbon\Carbon::parse($res['check_in'])->format('M d, Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($res['check_out'])->format('M d, Y') }}</td>
                <td>
                  <span class="res-badge {{ strtolower(str_replace(' ', '-', $res['status'])) }}">
                    {{ ucfirst($res['status']) }}
                  </span>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>

  </div>{{-- end .account-right --}}
</div>{{-- end .account-wrapper --}}

<script>
  function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.querySelector('svg').style.opacity = isText ? '1' : '0.4';
  }
  
</script>

@endsection

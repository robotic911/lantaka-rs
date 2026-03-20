@extends('layouts.employee')

<title>Action Logs - Lantaka Room and Venue Reservation System</title>
<link rel="stylesheet" href="{{ asset('css/employee_accounts.css') }}">

@section('content')
<div class="page-content">

  <h1 class="page-title">Action Logs</h1>

  {{-- ── Search ── --}}
  <form method="GET" action="{{ route('employee.eventlogs') }}" id="logsForm">
    <div class="search-container">
      <input
        type="text"
        name="search"
        class="search-input"
        placeholder="Search by action, message, or performed by…"
        value="{{ request('search') }}"
      >
      <button type="submit" class="search-icon" style="background:none;border:none;cursor:pointer;">🔍</button>
    </div>

    {{-- ── Action filter tabs ── --}}
    <div class="tabs">
      <a href="{{ route('employee.eventlogs') }}"
         class="tab-btn {{ !request('action') ? 'active' : '' }}">All</a>

      @foreach($actions as $act)
        <a href="{{ route('employee.eventlogs', array_merge(request()->except('action','page'), ['action' => $act])) }}"
           class="tab-btn {{ request('action') === $act ? 'active' : '' }}">
          {{ ucwords(str_replace('_', ' ', $act)) }}
        </a>
      @endforeach

      @if(request('search') || request('action'))
        <a href="{{ route('employee.eventlogs') }}" class="tab-btn" style="background:#f3f4f6;color:#6b7280;">✕ Clear</a>
      @endif
    </div>
  </form>

  {{-- ── Table ── --}}
  <div class="table-container">
    <table class="accounts-table">
      <thead>
        <tr>
          <th>Action</th>
          <th>Description</th>
          <th>Performed By</th>
          <th>Role</th>
          <th>Date &amp; Time</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          @php
            $slug  = \Illuminate\Support\Str::slug($log->Event_Logs_Action, '-');
            $label = ucwords(str_replace('_', ' ', $log->Event_Logs_Action));
            $actor = $log->user?->Account_Name ?? 'System';
            $role  = $log->user?->Account_Role ? ucfirst($log->user->Account_Role) : '—';
          @endphp
          <tr>
            {{-- Action badge --}}
            <td>
              <span class="status-badge el-badge--{{ $slug }}">{{ $label }}</span>
            </td>

            {{-- Description --}}
            <td style="max-width:380px;">
              <span style="font-size:13px;color:#374151;line-height:1.4;">{{ $log->Event_Logs_Message }}</span>
            </td>

            {{-- Performed by --}}
            <td>
              <div class="cell-with-icon">
                <span class="cell-icon">
                  <img src="{{ asset('images/logo/topnav/user-avatar.svg') }}" alt="user" style="width:22px;height:22px;opacity:.6;">
                </span>
                <span>{{ $actor }}</span>
              </div>
            </td>

            {{-- Role --}}
            <td>{{ $role }}</td>

            {{-- Timestamp --}}
            <td style="white-space:nowrap;">
              <span style="font-size:13px;">{{ $log->created_at ? $log->created_at->format('M j, Y') : '—' }}</span><br>
              <span style="font-size:11px;color:#9ca3af;">
                {{ $log->created_at ? $log->created_at->format('g:i A') : '' }}
                · {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;padding:32px;color:#9ca3af;font-size:14px;">
              No action logs found{{ request('search') || request('action') ? ' matching your filters' : '' }}.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:16px;">
      {{ $logs->links('vendor.pagination.simple') }}
    </div>
  </div>

</div>

{{-- Action badge colours (additive — inherits accounts.css base) --}}
<style>
.status-badge[class*="el-badge--"] {
  width: auto;
  min-width: 120px;
  white-space: nowrap;
  font-size: 11px;
  letter-spacing: .3px;
}

/* Green — approved / confirmed / reactivated / completed */
.el-badge--account-approved,
.el-badge--account-reactivated,
.el-badge--reservation-confirmed,
.el-badge--reservation-completed   { background: rgba(76,175,80,.18); color: #2e7d32; }

/* Red — declined / rejected / cancelled / deactivated */
.el-badge--account-declined,
.el-badge--account-deactivated,
.el-badge--reservation-rejected,
.el-badge--reservation-cancelled   { background: rgba(239,83,80,.15); color: #c62828; }

/* Blue — checked-in */
.el-badge--reservation-checked-in  { background: rgba(33,150,243,.15); color: #1565c0; }

/* Amber — checked-out */
.el-badge--reservation-checked-out { background: rgba(255,193,7,.2);  color: #e65100; }

/* Purple — updated */
.el-badge--account-updated         { background: rgba(149,117,205,.2); color: #4527a0; }
</style>

@endsection

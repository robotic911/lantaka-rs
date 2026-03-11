@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/employee_eventlogs.css') }}">

@section('content')
<main class="main-content">
  <div class="logs-container">
    <h1 class="page-title">Action Logs</h1>

    <div class="logs-controls">
      <form method="GET" action="{{ route('employee.eventlogs') }}" class="logs-controls" style="display: flex; gap: 16px; width: 100%;">
        <div class="search-bar">
          <input 
            type="text" 
            name="search" 
            placeholder="Search" 
            class="search-input"
            value="{{ request('search') }}"
          >
          <span class="search-icon">🔍</span>
        </div>

        <div class="filter-controls">
          <div class="filters-actions">
            <select name="action" class="status-filter" onchange="this.form.submit()">
              <option value="">All</option>
              <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
              <option value="approved" {{ request('action') == 'approved' ? 'selected' : '' }}>Approved</option>
              <option value="declined" {{ request('action') == 'declined' ? 'selected' : '' }}>Declined</option>
              <option value="cancelled" {{ request('action') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              <option value="checked-in" {{ request('action') == 'checked-in' ? 'selected' : '' }}>Checked-in</option>
              <option value="checked-out" {{ request('action') == 'checked-out' ? 'selected' : '' }}>Checked-out</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <div class="logs-list">
      @forelse($logs as $log)
        <div class="log-entry">
          <div class="log-header">
            <h3 class="log-action">{{ $log->message }}</h3>
            <span class="log-badge {{ \Illuminate\Support\Str::slug($log->action) }}">
              {{ ucfirst($log->action) }}
            </span>
          </div>

          <p class="log-timestamp">
            {{ $log->created_at ? $log->created_at->format('n/j/Y h:i:s A') : 'No timestamp' }}
          </p>
        </div>
      @empty
        <div class="log-entry">
          <div class="log-header">
            <h3 class="log-action">No event logs found.</h3>
          </div>
        </div>
      @endforelse
    </div>

    <div style="margin-top: 20px;">
      {{ $logs->links() }}
    </div>
  </div>
</main>
@endsection
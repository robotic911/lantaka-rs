@extends('layouts.employee')

  <title>Accounts - Lantaka Room and Venue Reservation System</title>
  <link rel="stylesheet" href="{{asset('css/employee_accounts.css')}}">
  @vite('resources/js/employee/approve_account.js')
  @vite('resources/js/employee/view_account.js')

  @section('content')
      <div class="page-content">
        <h1 class="page-title">Accounts</h1>

        <div class="search-container">
          <form action="{{ route('employee.accounts') }}" method="GET">
            <input type="text" name="search" class="search-input" placeholder="Search by name or email" value="{{ request('search') }}">
            <button type="submit" class="search-icon" style="background:none; border:none;">🔍</button>
          </form>
        </div>

        <div class="tabs">
          <a href="{{ route('employee.accounts') }}" class="tab-btn {{ !request('status') && !request('role') ? 'active' : '' }}">All</a>
          
          <a href="{{ route('employee.accounts', ['role' => 'employee']) }}" class="tab-btn {{ request('role') == 'employee' ? 'active' : '' }}">Employee Accounts</a>
          
          <a href="{{ route('employee.accounts', ['status' => 'approved']) }}" class="tab-btn {{ request('status') == 'approved' ? 'active' : '' }}">Approved Client Account</a>
          
          <a href="{{ route('employee.accounts', ['status' => 'declined']) }}" class="tab-btn {{ request('status') == 'declined' ? 'active' : '' }}">Declined Client Account</a>
          
          <a href="{{ route('employee.accounts', ['status' => 'pending']) }}" class="tab-btn {{ request('status') == 'pending' ? 'active' : '' }}">Pending Client Account</a>
        </div>

        <div class="table-container">
          <table class="accounts-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone no.</th>
                <th style="display: flex; width: 150px; justify-content: center;">
                  Status/Last Online
                </th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
                <tr>
                  <td>
                    <div class="cell-with-icon">
                      <span class="cell-icon">👤</span>
                      <span>{{ $user->name }}</span>
                    </div>
                  </td>
                  <td>{{ ucfirst($user->role) }}</td>
                  <td>{{ $user->email }}</td>
                  <td>{{ $user->phone ?? 'N/A' }}</td>
                  <td>
                    @if($user->status == 'pending')
                        <span class="status-badge pending">Pending</span>
                    @elseif($user->status == 'approved')
                        <span class="status-badge online">Approved</span>
                    @elseif($user->status == 'declined')
                        <span class="status-badge deactivated">Declined</span>
                    @else
                        <span class="status-badge online">Online</span>
                    @endif
                  </td>
                  <td>

                    @if($user->status == 'pending')
                        <button class="action-btn-approve" title="Review Account">👁️</button>
                    @else
                        <button class="action-btn-view">✎</button>
                    @endif

                  </td>
                </tr>
              @empty<th style="display: flex; width: 150px; justify-content: center;">
                  Status
                </th>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No accounts found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <x-approve_account_modal/>
      <x-view_account_modal/>

    @endsection
@extends('layouts.employee')

    <link rel="stylesheet" href="{{asset('css/employee_dashboard.css')}}">
    @vite('resources/js/employee/dashboard_calendar.js')
@section('content')
    <!-- Main Content -->
        <!-- Header -->

        <!-- Content Area -->
        <div class="content">
            <h1 class="page-title">Dashboard</h1>
            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Reservations</h3>
                        <span class="stat-icon">📅</span>
                    </div>
                    <div class="stat-value">{{ $totalReservations }}</div>
                    <div class="stat-change">+20.1% from last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Occupancy Rate</h3>
                        <span class="stat-icon">📊</span>
                    </div>
                    <div class="stat-value">78.5%</div>
                    <div class="stat-change">+2.5% from last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Revenue</h3>
                        <span class="stat-icon">💰</span>
                    </div>
                    <div class="stat-value">₱52,345</div>
                    <div class="stat-change">+15.3% from last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Active Guest</h3>
                        <span class="stat-icon">👥</span>
                    </div>
                    <div class="stat-value">312</div>
                    <div class="stat-change">+7.2% from last month</div>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="calendar-section">
                <div class="calendar-header">
                    <div class="calendar-left"></div>
                    <div class="calendar-nav-month">
                        <button class="prev-month btn">❮</button>
                            <h2 id="calendar-month-header"></h2>
                        <button class="next-month btn">❯</button>
                    </div>
                    <div class="calendar-nav-week hidden">
                        <button class="prev-week btn">❮</button>
                            <h2 id="calendar-week-header"></h2>
                        <button class="next-week btn">❯</button>
                    </div>

                    <div class="view-toggle">
                        <button class="toggle-btn" id="refresh">⟲</button>
                        <button class="toggle-btn active" id="btnMonthly">Monthly</button>
                        <button class="toggle-btn" id="btnWeekly">Weekly</button>
                    </div>
                </div>
                <div class="calendar">
                    <div class="calendar-grid-month">
                        <!-- Day headers -->
                         <div class="day-header-container">
                            <div class="day-header">Sun</div>
                            <div class="day-header">Mon</div>
                            <div class="day-header">Tue</div>
                            <div class="day-header">Wed</div>
                            <div class="day-header">Thu</div>
                            <div class="day-header">Fri</div>
                            <div class="day-header">Sat</div>
                         </div>
                        

                        <!-- Calendar dates -->
                            <div class="days-container-month">

                                <!-- Days Display -->

                            </div>
                    </div>
                    <div class="calendar-grid-week hide">
                        <!-- Day headers -->
                         <div class="day-header-container-week">
                            <div class="day-header-week">Sun</div>
                            <div class="day-header-week">Mon</div>
                            <div class="day-header-week">Tue</div>
                            <div class="day-header-week">Wed</div>
                            <div class="day-header-week">Thu</div>
                            <div class="day-header-week">Fri</div>
                            <div class="day-header-week">Sat</div>
                         </div>
                        

                        <!-- Calendar dates -->
                         <div class="days-container-week">
                             <!-- Days Display -->

                        </div>
                    </div>
                </div>
                <br>
                <div> Blue = Pending // Green = Confirmed // Orange = Completed // Red = Completed // Sky Blue = Checked-In //</div>
            </div>
           
            <!-- Export Button -->
            <button class="export-btn">Export</button>
        </div>
        <script>
            window.reservations = @json($reservations);
            window.reservationPage = "{{ route('employee.reservations') }}"
        </script>
@endsection
    
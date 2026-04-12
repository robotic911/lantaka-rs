@extends('layouts.employee')

    <link rel="stylesheet" href="{{asset('css/employee_dashboard.css')}}">
    @vite('resources/js/employee/dashboard_calendar.js')

@section('content')

    {{-- Chart.js + jsPDF CDN --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Export Month Picker Modal -->
    <div id="exportModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:36px 32px 28px; width:400px; box-shadow:0 24px 64px rgba(0,0,0,.3);">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                <div style="width:40px; height:40px; background:#1a3a7a; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px;">📊</div>
                <div>
                    <h3 style="margin:0; color:#1a3a7a; font-size:17px; font-weight:700;">Generate Analytics Report</h3>
                    <p style="margin:0; color:#888; font-size:12px;">Export monthly data as a visual PDF</p>
                </div>
            </div>
            <hr style="border:none; border-top:1px solid #eee; margin:18px 0;">
            <label style="font-size:12px; font-weight:600; color:#555; display:block; margin-bottom:10px; text-transform:uppercase; letter-spacing:.5px;">Select Period</label>
            <div style="display:flex; gap:12px; margin-bottom:24px;">
                <div style="flex:1.4;">
                    <select id="exportMonth" style="width:100%; padding:10px 12px; border:1.5px solid #e0e0e8; border-radius:8px; font-size:14px; color:#333; background:#fff; cursor:pointer; outline:none;">
                        <option value="1">January</option><option value="2">February</option>
                        <option value="3">March</option><option value="4">April</option>
                        <option value="5">May</option><option value="6">June</option>
                        <option value="7">July</option><option value="8">August</option>
                        <option value="9">September</option><option value="10">October</option>
                        <option value="11">November</option><option value="12">December</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <select id="exportYear" style="width:100%; padding:10px 12px; border:1.5px solid #e0e0e8; border-radius:8px; font-size:14px; color:#333; background:#fff; cursor:pointer; outline:none;"></select>
                </div>
            </div>
            <div style="background:#f0f4ff; border-radius:8px; padding:10px 14px; margin-bottom:22px; font-size:12px; color:#555; line-height:1.6;">
                📄 The PDF will include <strong>daily trend charts</strong>, <strong>status breakdown</strong>, <strong>rooms vs venues comparison</strong>, and a <strong>top accommodations table</strong>.
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button id="cancelExportBtn" style="padding:10px 22px; border:1.5px solid #ddd; background:#fff; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; color:#666;">Cancel</button>
                <button id="confirmExportBtn" style="padding:10px 22px; background:#1a3a7a; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px;">
                    <span>⬇</span> Generate PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content" id="dashboardContent">
        <h1 class="page-title">Dashboard</h1>

        <!-- Stat Cards -->
        <div class="stats-grid">

            {{-- Total Reservations --}}
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Total Reservations</h3>
                    <img src="{{ asset('images/logo/dashboard/dashboard-reservations.svg') }}" alt="reservations">
                </div>
                <div class="stat-value" id="totalReservationsValue">{{ $totalReservations ?? 0 }}</div>
                <div class="stat-change" id="changeTotalReservations">
                    @php $c = $changes['totalReservations'] ?? 0; @endphp
                    @if($c > 0)<span class="chg-positive">↑ {{ $c }}%</span>
                    @elseif($c < 0)<span class="chg-negative">↓ {{ abs($c) }}%</span>
                    @else<span class="chg-neutral">—</span>@endif
                    <span class="chg-label">vs {{ $changes['lastMonthLabel'] ?? 'last month' }}</span>
                </div>
            </div>

            {{-- Occupancy Rate --}}
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Occupancy Rate</h3>
                    <img src="{{ asset('images/logo/dashboard/dashboard-occupancy.svg') }}" alt="occupancy">
                </div>
                <div class="stat-value" id="occupancyRateValue">{{ number_format($occupancyRate ?? 0, 1) }}%</div>
                <div class="stat-change" id="changeOccupancyRate">
                    @php $c = $changes['occupancyRate'] ?? 0; @endphp
                    @if($c > 0)<span class="chg-positive">↑ {{ $c }}%</span>
                    @elseif($c < 0)<span class="chg-negative">↓ {{ abs($c) }}%</span>
                    @else<span class="chg-neutral">—</span>@endif
                    <span class="chg-label">vs prev 30 days</span>
                </div>
            </div>

            {{-- Revenue --}}
            @if(auth()->user()->Account_Role == "admin")
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Revenue</h3>
                    <img src="{{ asset('images/logo/dashboard/dashboard-revenue.svg') }}" alt="revenue">
                </div>
                <div class="stat-value" id="totalRevenueValue">₱{{ number_format($totalRevenue ?? 0) }}</div>
                <div class="stat-change" id="changeRevenue">
                    @php
                    $c = $changes['revenue'] ?? 0; @endphp
                    @if($c > 0)<span class="chg-positive">↑ {{ $c }}%</span>
                    @elseif($c < 0)<span class="chg-negative">↓ {{ abs($c) }}%</span>
                    @else<span class="chg-neutral">—</span>@endif
                    <span class="chg-label">vs {{ $changes['lastMonthLabel'] ?? 'last month' }}</span>
                </div>
            </div>
            @endif
            {{-- Active Guests --}}
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Active Guests</h3>
                    <img src="{{ asset('images/logo/dashboard/dashboard-guests.svg') }}" alt="guests">
                </div>
                <div class="stat-value" id="activeGuestsValue">{{ $activeGuests ?? 0 }}</div>
                <div class="stat-change" id="changeActiveGuests">
                    @php $c = $changes['activeGuests'] ?? 0; @endphp
                    @if($c > 0)<span class="chg-positive">↑ {{ $c }}%</span>
                    @elseif($c < 0)<span class="chg-negative">↓ {{ abs($c) }}%</span>
                    @else 
                    <span class="chg-neutral">—</span>
                    @endif
                    <span class="chg-label">vs {{ $changes['lastMonthLabel'] ?? 'last month' }}</span>
                </div>
            </div>

            {{-- Checked-outs Today --}}
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Checked-outs Today</h3>
                    <img src="{{ asset('images/logo/dashboard/dashboard-occupancy.svg') }}" alt="checkouts">
                </div>
                <div class="stat-value" id="checkOutsTodayValue">{{ $checkOutsTodayCount ?? 0 }}</div>
                <div class="stat-change" id="changeCheckOuts">
                    @php $c = $changes['checkOutsToday'] ?? 0; @endphp
                    @if($c > 0)<span class="chg-positive">↑ {{ $c }}%</span>
                    @elseif($c < 0)<span class="chg-negative">↓ {{ abs($c) }}%</span>
                    @else<span class="chg-neutral">—</span>@endif
                    <span class="chg-label">vs {{ $changes['lastMonthLabel'] ?? 'last month' }}</span>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════════
             Apple Calendar — Reservation Calendar
             ══════════════════════════════════════════ -->
        <div class="ac-section">

            {{-- ── Toolbar ── --}}
            <div class="ac-toolbar">

                {{-- Left: navigation --}}
                <div class="ac-nav-group">
                    <button class="ac-nav-btn" id="calPrev" title="Previous">&#8249;</button>
                    <h2 class="ac-header-label" id="calHeaderLabel">Loading…</h2>
                    <button class="ac-nav-btn" id="calNext" title="Next">&#8250;</button>
                    <button class="ac-today-btn" id="calToday">Today</button>
                </div>

                {{-- Centre: section title --}}
                <span class="ac-section-title">Reservation Calendar</span>

                {{-- Right: controls --}}
                <div class="ac-ctrl-group">

                    {{-- Status filter --}}
                    <select class="ac-status-sel" id="calStatusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked-in">Checked-In</option>
                        <option value="checked-out">Checked-Out</option>
                        <option value="completed">Completed</option>
                        <option disabled>──────────</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    {{-- Display mode --}}
                    <div class="ac-segmented" aria-label="Display mode">
                        <button class="ac-mode-btn" data-mode="stacked">Stacked</button>
                        <button class="ac-mode-btn active" data-mode="detailed">Detailed</button>
                    </div>

                    {{-- View --}}
                    <div class="ac-segmented" aria-label="Calendar view">
                        <button class="ac-view-btn active" data-view="month">Month</button>
                        <button class="ac-view-btn" data-view="week">Week</button>
                    </div>

                    {{-- Export PDF (keeps ganttExcelBtn id for existing JS handler) --}}
                    <button class="ac-export-btn" id="ganttExcelBtn" title="Export Calendar PDF">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Export CSV/PDF
                    </button>
                </div>
            </div>

            {{-- ── Legend ── --}}
            <div class="ac-legend-bar">
                <span class="ac-legend-item"><span class="ac-legend-dot" style="background:#f59e0b;"></span>Pending</span>
                <span class="ac-legend-item"><span class="ac-legend-dot" style="background:#3b82f6;"></span>Confirmed</span>
                <span class="ac-legend-item"><span class="ac-legend-dot" style="background:#22c55e;"></span>Checked-In</span>
                <span class="ac-legend-item"><span class="ac-legend-dot" style="background:#9ca3af;"></span>Checked-Out</span>
                <span class="ac-legend-item"><span class="ac-legend-dot" style="background:#8b5cf6;"></span>Completed</span>
                <span class="ac-legend-note">Cancelled &amp; Rejected reservations are hidden</span>
            </div>

            {{-- ── Calendar Grid (rendered by JS) ── --}}
            <div id="calGrid" class="ac-grid-wrap"></div>

            {{-- ── Export Modal ── --}}
            <div id="calExportModal" class="cal-export-overlay" style="display:none;">
                <div class="cal-export-dialog">
                    <div class="cal-export-header">
                        <span class="cal-export-title">Export Reservation Calendar</span>
                        <button class="cal-export-close" id="calExportClose">&times;</button>
                    </div>
                    <p class="cal-export-desc">
                        Choose a month and year to export reservations as a PDF or a flat CSV spreadsheet.
                    </p>
                    <div class="cal-export-fields">
                        <div class="cal-export-field">
                            <label for="calExportStartMonth">Month</label>
                            <select id="calExportStartMonth">
                                <option value="1">January</option><option value="2">February</option>
                                <option value="3">March</option><option value="4">April</option>
                                <option value="5">May</option><option value="6">June</option>
                                <option value="7">July</option><option value="8">August</option>
                                <option value="9">September</option><option value="10">October</option>
                                <option value="11">November</option><option value="12">December</option>
                            </select>
                        </div>
                        <div class="cal-export-field">
                            <label for="calExportYear">Year</label>
                            <select id="calExportYear"></select>
                        </div>
                    </div>

                    {{-- PDF Granularity (PDF only) --}}
                    <div class="cal-export-fields" style="margin-top:8px;">
                        <div class="cal-export-field" style="flex:1;">
                            <label>PDF View <span style="font-size:10px;color:#888;">(PDF only)</span></label>
                            <div style="display:flex;gap:8px;margin-top:4px;">
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500;font-size:13px;cursor:pointer;">
                                    <input type="radio" name="calPdfGranularity" id="calPdfGranMonth" value="month" checked style="accent-color:#1a3a7a;">
                                    Monthly
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:500;font-size:13px;cursor:pointer;">
                                    <input type="radio" name="calPdfGranularity" id="calPdfGranWeek" value="week" style="accent-color:#1a3a7a;">
                                    Weekly
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Week picker (hidden until Weekly is selected) --}}
                    <div class="cal-export-fields" id="calWeekPickerRow" style="margin-top:8px;display:none;">
                        <div class="cal-export-field" style="flex:1;">
                            <label for="calExportWeek">Week of Month</label>
                            <select id="calExportWeek">
                                <option value="1">Week 1 (days 1–7)</option>
                                <option value="2">Week 2 (days 8–14)</option>
                                <option value="3">Week 3 (days 15–21)</option>
                                <option value="4">Week 4 (days 22–28)</option>
                                <option value="5">Week 5 (days 29–end)</option>
                            </select>
                        </div>
                    </div>

                    <div class="cal-export-fields" style="margin-top:8px;">
                        <div class="cal-export-field" style="flex:1;">
                            <label for="calExportType">Reservation Type <span style="font-size:10px;color:#888;">(CSV only)</span></label>
                            <select id="calExportType">
                                <option value="all">All</option>
                                <option value="room">Rooms Only</option>
                                <option value="venue">Venues Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="cal-export-preview">
                        <span class="cal-export-preview-lbl">File:</span>
                        <span id="calExportFilename" class="cal-export-preview-name"></span>
                    </div>
                    <div class="cal-export-actions">
                        <button class="cal-export-cancel" id="calExportCancelBtn">Cancel</button>
                        <button class="cal-export-download cal-export-csv" id="calExportCsvBtn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download CSV
                        </button>
                        <button class="cal-export-download cal-export-pdf" id="calExportPdfBtn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- /ac-section --}}

        <!-- Export PDF Button -->
        <button class="export-btn" id="exportPdfBtn">Export Report</button>
    </div>

    {{-- Gantt Tooltip --}}
    <div class="gantt-tooltip" id="ganttTooltip"></div>

    <script>
        window.reservations         = @json($reservations);
        window.allRooms             = @json($allRooms);
        window.allVenues            = @json($allVenues);
        window.statChanges          = @json($changes);
        window.calendarExportRoute    = "{{ route('calendar.export') }}";
        window.calendarExportPDFRoute = "{{ route('calendar.export.pdf') }}";
        window.calendarExportCSVRoute = "{{ route('calendar.export.csv') }}";
        window.reservationPage      = "{{ route('employee.reservations') }}";
        window.guestPage            = "{{ route('employee.guest') }}";
        window.calendarDataRoute    = "{{ route('calendar.fetchUpdatedData') }}";
        window.analyticsReportRoute = "{{ route('employee.analytics.report.data') }}";

        /* ── Modal Setup ── */
        const modal      = document.getElementById('exportModal');
        const exportBtn  = document.getElementById('exportPdfBtn');
        const cancelBtn  = document.getElementById('cancelExportBtn');
        const confirmBtn = document.getElementById('confirmExportBtn');
        const mSel       = document.getElementById('exportMonth');
        const ySel       = document.getElementById('exportYear');

        // Populate year dropdown (current year - 3 years back)
        const nowDate = new Date();
        for (let y = nowDate.getFullYear(); y >= nowDate.getFullYear() - 3; y--) {
            const o = document.createElement('option');
            o.value = y; o.textContent = y;
            ySel.appendChild(o);
        }
        mSel.value = nowDate.getMonth() + 1;

        exportBtn.addEventListener('click', () => { modal.style.display = 'flex'; });
        cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; });
        modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

        /* ── Export Modal ─────────────────────────────────── */
        const MONTH_NAMES_FULL = ['January','February','March','April','May','June',
                                  'July','August','September','October','November','December'];
        const calModal         = document.getElementById('calExportModal');
        const calCloseBtn      = document.getElementById('calExportClose');
        const calCancelBtn     = document.getElementById('calExportCancelBtn');
        const calDownBtn       = document.getElementById('calExportDownloadBtn');
        const calMonthSelStart = document.getElementById('calExportStartMonth');
        const calYearSel       = document.getElementById('calExportYear');
        const calFilename      = document.getElementById('calExportFilename');
        const calWeekPickerRow = document.getElementById('calWeekPickerRow');
        const calWeekSel       = document.getElementById('calExportWeek');

        // Populate year dropdown (current + 1 down to current - 2)
        for (let y = nowDate.getFullYear() + 1; y >= nowDate.getFullYear() - 2; y--) {
            const o = document.createElement('option');
            o.value = y; o.textContent = y;
            if (y === nowDate.getFullYear()) o.selected = true;
            calYearSel.appendChild(o);
        }
        calMonthSelStart.value = nowDate.getMonth() + 1;

        function calPdfGranularity() {
            return document.querySelector('input[name="calPdfGranularity"]:checked')?.value || 'month';
        }

        function updateCalFilename() {
            const m    = parseInt(calMonthSelStart.value);
            const y    = calYearSel.value;
            const gran = calPdfGranularity();
            const week = parseInt(calWeekSel?.value || 1);
            const base = `${MONTH_NAMES_FULL[m-1]}_${y}`;
            const suffix = (gran === 'week') ? `_Week${week}` : '';
            if (calFilename) calFilename.textContent = `reservation_calendar_${base}${suffix}`;
        }

        // Toggle week picker visibility
        document.querySelectorAll('input[name="calPdfGranularity"]').forEach(r => {
            r.addEventListener('change', () => {
                const isWeek = calPdfGranularity() === 'week';
                calWeekPickerRow.style.display = isWeek ? '' : 'none';
                updateCalFilename();
            });
        });

        calMonthSelStart.addEventListener('change', updateCalFilename);
        calYearSel.addEventListener('change',       updateCalFilename);
        calWeekSel?.addEventListener('change',      updateCalFilename);
        updateCalFilename();

        document.getElementById('ganttExcelBtn')
            ?.addEventListener('click', () => { calModal.style.display = 'flex'; });
        calCloseBtn ?.addEventListener('click', () => { calModal.style.display = 'none'; });
        calCancelBtn?.addEventListener('click', () => { calModal.style.display = 'none'; });
        calModal    ?.addEventListener('click', e => { if (e.target === calModal) calModal.style.display = 'none'; });

        function calExportParams(includeType = false) {
            const base = `month=${calMonthSelStart.value}&year=${calYearSel.value}`;
            if (!includeType) return base;
            const type = document.getElementById('calExportType')?.value || 'all';
            return `${base}&reservation_type=${type}`;
        }

        function calExportPdfParams() {
            let params = `month=${calMonthSelStart.value}&year=${calYearSel.value}`;
            const gran = calPdfGranularity();
            if (gran === 'week') {
                params += `&granularity=week&week=${calWeekSel?.value || 1}`;
            } else {
                params += `&granularity=month`;
            }
            return params;
        }

        calDownBtn?.addEventListener('click', () => {
            calModal.style.display = 'none';
            window.location.href = `${window.calendarExportRoute}?${calExportParams()}`;
        });

        document.getElementById('calExportPdfBtn')
            ?.addEventListener('click', () => {
                calModal.style.display = 'none';
                window.location.href = `${window.calendarExportPDFRoute}?${calExportPdfParams()}`;
            });

        document.getElementById('calExportCsvBtn')
            ?.addEventListener('click', () => {
                calModal.style.display = 'none';
                window.location.href = `${window.calendarExportCSVRoute}?${calExportParams(true)}`;
            });

        confirmBtn.addEventListener('click', async () => {
            modal.style.display = 'none';
            exportBtn.innerHTML = '⏳ Generating…';
            exportBtn.disabled  = true;
            try {
                const res = await fetch(
                    `${window.analyticsReportRoute}?month=${mSel.value}&year=${ySel.value}`,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                if (!res.ok) throw new Error('Server error ' + res.status);
                const data = await res.json();
                const pdf  = await buildAnalyticsPDF(data);
                pdf.save(`Lantaka_Analytics_${data.monthLabel.replace(' ', '_')}.pdf`);
            } catch (err) {
                console.error('PDF export error:', err);
                alert('PDF export failed: ' + err.message);
            } finally {
                exportBtn.innerHTML = '📊 Export Report';
                exportBtn.disabled  = false;
            }
        });

        /* ─────────────────────────────────────────────────────
           CHART HELPERS
        ───────────────────────────────────────────────────── */
        function offscreenCanvas(w, h) {
            const c = document.createElement('canvas');
            c.width = w; c.height = h;
            c.style.cssText = 'position:absolute;left:-99999px;top:-99999px;pointer-events:none;';
            document.body.appendChild(c);
            return c;
        }

        async function chartToImage(config, w, h) {
            const canvas = offscreenCanvas(w, h);
            const chart  = new Chart(canvas.getContext('2d'), config);
            await new Promise(r => setTimeout(r, 120));
            const img = canvas.toDataURL('image/png', 1.0);
            chart.destroy();
            document.body.removeChild(canvas);
            return img;
        }

        /* ── Line + Bar combo: Daily Reservations & Revenue ── */
        async function buildDailyChart(dailyData, monthLabel) {
            const labels       = dailyData.map(d => d.day);
            const reservations = dailyData.map(d => d.reservations);
            const revenue      = dailyData.map(d => d.revenue);
            return chartToImage({
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Reservations',
                            data: reservations,
                            backgroundColor: 'rgba(99,153,243,0.75)',
                            borderColor: 'rgba(99,153,243,1)',
                            borderWidth: 1,
                            borderRadius: 3,
                            yAxisID: 'yRes',
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'Revenue (₱)',
                            data: revenue,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.12)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1.5,
                            fill: true,
                            tension: 0.42,
                            yAxisID: 'yRev',
                            order: 1,
                        }
                    ]
                },
                options: {
                    animation: { duration: 0 },
                    responsive: false,
                    layout: { padding: { top: 10, right: 20, bottom: 10, left: 10 } },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { size: 13, weight: '600' }, padding: 18, usePointStyle: true }
                        },
                        title: {
                            display: true,
                            text: `Daily Activity — ${monthLabel}`,
                            font: { size: 16, weight: 'bold' },
                            color: '#1a3a7a',
                            padding: { top: 4, bottom: 14 }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { size: 11 } }
                        },
                        yRes: {
                            type: 'linear', position: 'left',
                            title: { display: true, text: 'Reservations', font: { size: 11 }, color: '#6199f3' },
                            grid: { color: 'rgba(0,0,0,0.06)' },
                            beginAtZero: true,
                            ticks: { precision: 0, font: { size: 10 }, color: '#6199f3' }
                        },
                        yRev: {
                            type: 'linear', position: 'right',
                            title: { display: true, text: 'Revenue (₱)', font: { size: 11 }, color: '#f59e0b' },
                            grid: { drawOnChartArea: false },
                            beginAtZero: true,
                            ticks: {
                                font: { size: 10 }, color: '#f59e0b',
                                callback: v => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            }, 1700, 600);
        }

        /* ── Doughnut: Status Breakdown ── */
        async function buildStatusChart(statusBreakdown) {
            const colorMap = {
                'pending':     '#6199f3',
                'confirmed':   '#53e087',
                'checked-in':  '#14b8a6',
                'checked-out': '#9ca3af',
                'completed':   '#f48f23',
                'cancelled':   '#ef4444',
            };
            const keys   = Object.keys(statusBreakdown).filter(k => statusBreakdown[k] > 0);
            const values = keys.map(k => statusBreakdown[k]);
            const total  = values.reduce((a, b) => a + b, 0);

            return chartToImage({
                type: 'doughnut',
                data: {
                    labels: keys,
                    datasets: [{
                        data: values,
                        backgroundColor: keys.map(k => colorMap[k] || '#ccc'),
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    animation: { duration: 0 },
                    responsive: false,
                    cutout: '60%',
                    layout: { padding: 10 },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 14,
                                font: { size: 12 },
                                generateLabels: chart => {
                                    const d = chart.data;
                                    return d.labels.map((label, i) => ({
                                        text: `${label.charAt(0).toUpperCase() + label.slice(1)}  ${d.datasets[0].data[i]}`,
                                        fillStyle: d.datasets[0].backgroundColor[i],
                                        lineWidth: 0,
                                        index: i,
                                    }));
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: ['Status Distribution', `Total: ${total} reservations`],
                            font: { size: 14, weight: 'bold' },
                            color: '#1a3a7a',
                            padding: { top: 4, bottom: 10 }
                        }
                    }
                }
            }, 620, 500);
        }

        /* ── Grouped Bar: Room Types vs Venue (reservations only) ── */
        async function buildComparisonChart(data) {
            const roomTypes  = data.roomTypeBreakdown || [];
            const venueRow   = data.venueTypeRow || { type: 'Venue', bookings: data.venueCount || 0 };

            // Colour palette — up to 6 room types, then repeats
            const typeColors = [
                ['rgba(99,153,243,0.82)',  '#6199f3'],
                ['rgba(52,199,148,0.82)',  '#34c794'],
                ['rgba(255,159,64,0.82)',  '#ff9f40'],
                ['rgba(255,99,132,0.82)',  '#ff6384'],
                ['rgba(75,192,192,0.82)',  '#4bc0c0'],
                ['rgba(153,102,255,0.82)', '#9966ff'],
            ];

            const datasets = roomTypes.map((rt, i) => {
                const [bg, border] = typeColors[i % typeColors.length];
                return {
                    label: rt.type,
                    data: [rt.bookings],
                    backgroundColor: bg,
                    borderColor: border,
                    borderWidth: 1.5,
                    borderRadius: 5,
                };
            });

            // Append Venue as the last dataset
            datasets.push({
                label: 'Venue',
                data: [venueRow.bookings],
                backgroundColor: 'rgba(163,148,234,0.82)',
                borderColor: '#a394ea',
                borderWidth: 1.5,
                borderRadius: 5,
            });

            return chartToImage({
                type: 'bar',
                data: {
                    labels: ['Checked-out Bookings'],
                    datasets,
                },
                options: {
                    animation: { duration: 0 },
                    responsive: false,
                    layout: { padding: { top: 8, right: 16, bottom: 8, left: 8 } },
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 11, weight: '600' }, padding: 10, usePointStyle: true } },
                        title: {
                            display: true,
                            text: 'Room Types vs. Venue — Checked-out Reservations',
                            font: { size: 14, weight: 'bold' },
                            color: '#1a3a7a',
                            padding: { top: 4, bottom: 10 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.06)' },
                            ticks: { precision: 0, font: { size: 10 } },
                            title: { display: true, text: 'Reservations', font: { size: 11 }, color: '#555' }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            }, 620, 500);
        }

         /* ─────────────────────────────────────────────────────
           PDF BUILDER
        ───────────────────────────────────────────────────── */
        async function buildAnalyticsPDF(data) {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const W = 297, H = 210;

            // Palette
            const C = {
                blue:    [26,  58,  122],
                blueSoft:[235,240,255],
                white:   [255,255,255],
                lgray:   [246,247,251],
                dgray:   [90,  90,  105],
                mgray:   [150,150,165],
                amber:   [245,158,11],
                green:   [22, 163, 74],
                red:     [220,38,38],
            };

            // ── Sanitize: strip every char outside ISO-8859-1 (latin-1) ──
            // jsPDF's built-in Helvetica only covers U+0000–U+00FF.
            // Anything outside that range throws "Invalid arguments passed to jsPDF.text".
            const safeText = s => {
                if (s == null) return '';
                return String(s)
                    .replace(/[^\x00-\xFF]/g, '')   // drop non-latin-1
                    .trim() || '';                   // never return empty-after-trim as ''
            };

            // Thin wrapper so we can log the exact call that fails during development
            const T = (text, x, y, opts) => {
                const safe = safeText(text);
                try {
                    if (opts) pdf.text(safe, x, y, opts);
                    else      pdf.text(safe, x, y);
                } catch(e) {
                    console.error('pdf.text failed:', JSON.stringify(safe), x, y, opts, e);
                    throw e;
                }
            };

            const fmt = n => {
                const num = Number(n ?? 0);
                // manual comma-formatting — avoids locale-specific non-ASCII punctuation
                return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            };
            const peso = n => 'PHP ' + fmt(n);
            const pctTxt = v => {
                const n = Number(v ?? 0);
                if (n > 0) return '+' + n + '%  UP';
                if (n < 0) return '-' + Math.abs(n) + '%  DOWN';
                return '0%  flat';
            };
            const pctColor = v => {
                const n = Number(v ?? 0);
                return n > 0 ? C.green : n < 0 ? C.red : C.mgray;
            };

            /* ══════════ PAGE 1 ══════════ */

            // ── Shared date string (used in header + footer) ───────────────────
            const _d = new Date();
            const _months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const genDate = _months[_d.getMonth()] + ' ' + _d.getDate() + ', ' + _d.getFullYear();

            // ── Reusable header painter (called for page 1 and page 2) ──────────
            const drawPageHeader = (tall) => {
                // tall = true  → 30 mm (page 1 full header)
                // tall = false → 20 mm (page 2 compact header)
                const hH = tall ? 30 : 20;

                // ── Dark navy background ─────────────────────────────────────────
                pdf.setFillColor(15, 35, 90);          // deep navy
                pdf.rect(0, 0, W, hH, 'F');

                // Subtle lighter band across the top edge (2 mm) – depth effect
                pdf.setFillColor(30, 60, 130);
                pdf.rect(0, 0, W, 2, 'F');

                // ── Gold accent stripe at bottom ─────────────────────────────────
                pdf.setFillColor(212, 175, 55);
                pdf.rect(0, hH - 2, W, 2, 'F');

                // ── LEFT: Institution text (text-only) ───────────────────────────
                const txtX = 14;

                if (tall) {
                    // Italic university name
                    pdf.setFont('helvetica', 'italic');
                    pdf.setFontSize(8.5);
                    pdf.setTextColor(180, 205, 255);
                    T('Ateneo de Zamboanga University', txtX, 10);

                    // Large bold campus name
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(18);
                    pdf.setTextColor(255, 255, 255);
                    T('Lantaka Campus', txtX, 19);

                    // Subtitle tagline
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(7);
                    pdf.setTextColor(160, 190, 240);
                    T('The Ateneo de Zamboanga University Spirituality, Formation, and Training Center Since 2019', txtX, 25.5);
                } else {
                    // Compact page-2 left block
                    pdf.setFont('helvetica', 'italic');
                    pdf.setFontSize(7.5);
                    pdf.setTextColor(180, 205, 255);
                    T('Ateneo de Zamboanga University', txtX, 8);

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(13);
                    pdf.setTextColor(255, 255, 255);
                    T('Lantaka Campus', txtX, 15);
                }

                // ── Thin vertical divider between left block and right block ─────
                pdf.setDrawColor(212, 175, 55);
                pdf.setLineWidth(0.4);
                pdf.line(W - 90, 4, W - 90, hH - 4);

                // ── RIGHT corner text block ──────────────────────────────────────
                if (tall) {
                    // "Monthly Analytics Report" label
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(8.5);
                    pdf.setTextColor(180, 205, 255);
                    T('Monthly Analytics Report', W - 12, 9, { align: 'right' });

                    // Month + Year (most prominent)
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(17);
                    pdf.setTextColor(255, 255, 255);
                    T(safeText(data.monthLabel).toUpperCase(), W - 12, 20, { align: 'right' });

                    // Generated date
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(7.5);
                    pdf.setTextColor(160, 190, 240);
                    T('Generated: ' + genDate, W - 12, 26.5, { align: 'right' });
                } else {
                    // Compact page-2 right: just the report label + month
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(7.5);
                    pdf.setTextColor(180, 205, 255);
                    T('Lantaka Campus Analytics Report', W - 12, 8, { align: 'right' });

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(11);
                    pdf.setTextColor(255, 255, 255);
                    T(safeText(data.monthLabel).toUpperCase() + ' — Detailed Breakdown', W - 12, 15, { align: 'right' });
                }
            };

            drawPageHeader(true);

            /* ── 4 Stat Cards (y: 35–70) ── */
            const cardDefs = [
                { label: 'Total Reservations', value: String(data.totalReservations),
                  sub: pctTxt(data.resPctChange) + ' vs ' + data.prevMonthLabel,
                  subColor: pctColor(data.resPctChange), accent: [99, 153, 243] },
                { label: 'Total Revenue', value: peso(data.totalRevenue),
                  sub: pctTxt(data.revPctChange) + ' vs ' + data.prevMonthLabel,
                  subColor: pctColor(data.revPctChange), accent: [83, 224, 135] },
                { label: 'Room Bookings', value: String(data.roomCount),
                  sub: 'Revenue: ' + peso(data.roomRevenue),
                  subColor: C.mgray, accent: [250, 188, 88] },
                { label: 'Venue Bookings', value: String(data.venueCount),
                  sub: 'Revenue: ' + peso(data.venueRevenue),
                  subColor: C.mgray, accent: [163, 148, 234] },
            ];

            const gap  = 6;
            const cW   = (W - 14 * 2 - gap * 3) / 4;
            const cY   = 35;
            const cH   = 32;

            cardDefs.forEach((card, i) => {
                const cx = 14 + i * (cW + gap);

                // Shadow illusion (slightly offset gray rect)
                pdf.setFillColor(220, 222, 230);
                pdf.roundedRect(cx + 1, cY + 1, cW, cH, 2.5, 2.5, 'F');

                // Card bg
                pdf.setFillColor(...C.lgray);
                pdf.roundedRect(cx, cY, cW, cH, 2.5, 2.5, 'F');

                // Accent left bar
                pdf.setFillColor(...card.accent);
                pdf.roundedRect(cx, cY, 4, cH, 2, 2, 'F');
                pdf.rect(cx + 2, cY, 2, cH, 'F');

                // Value
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(14.5);
                pdf.setTextColor(...C.blue);
                T(card.value, cx + cW / 2 + 2, cY + 13, { align: 'center' });

                // Label
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(7);
                pdf.setTextColor(...C.dgray);
                T(card.label, cx + cW / 2 + 2, cY + 20, { align: 'center' });

                // Sub (percentage change)
                pdf.setFontSize(6.5);
                pdf.setTextColor(...card.subColor);
                T(card.sub, cx + cW / 2 + 2, cY + 27, { align: 'center' });
            });

            /* ── Daily Chart (y: 71–195) ── */
            const lineImg = await buildDailyChart(data.dailyData, data.monthLabel);
            pdf.addImage(lineImg, 'PNG', 14, 71, W - 28, 126);

            // ── Page 1 footer ────────────────────────────────────────────────────
            pdf.setFillColor(15, 35, 90);
            pdf.rect(0, H - 7, W, 7, 'F');
            pdf.setFillColor(212, 175, 55);
            pdf.rect(0, H - 7, W, 0.8, 'F');
            pdf.setTextColor(160, 190, 240);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7.5);
            T('Page 1 of 2  |  Lantaka Campus Analytics Report  |  Confidential', W / 2, H - 2.5, { align: 'center' });

            /* ══════════ PAGE 2 ══════════ */
            pdf.addPage();

            // Shared header (compact 20 mm variant)
            drawPageHeader(false);

            /* ── Two charts side by side (y: 24–106) ── */
            const [donutImg, barImg] = await Promise.all([
                buildStatusChart(data.statusBreakdown),
                buildComparisonChart(data),
            ]);

            const chartW = (W - 14 * 2 - 8) / 2;
            pdf.addImage(donutImg, 'PNG', 14,            24, chartW, 84);
            pdf.addImage(barImg,   'PNG', 14 + chartW + 8, 24, chartW, 84);

            /* ── Top Accommodations Table (y: 113–195) ── */
            const tY    = 113;
            const tW    = W - 28;

            // Section title
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(10);
            pdf.setTextColor(...C.blue);
            T('Top 10 Clients by Revenue', 14, tY);

            const topItems = (data.topClients || []).slice(0, 10);

            // Table column definitions
            const cols = [
                { header: '#',           w: 9,  align: 'center' },
                { header: 'Client Name', w: 96, align: 'left'   },
                { header: 'Bookings',    w: 28, align: 'center' },
                { header: 'Revenue',     w: 44, align: 'right'  },
            ];

            const hY = tY + 8;
            // Header bg
            pdf.setFillColor(...C.blue);
            pdf.rect(14, hY - 5, tW, 7, 'F');

            // Header text
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(7.5);
            pdf.setTextColor(...C.white);
            let cx2 = 14;
            cols.forEach(col => {
                const tx = col.align === 'right'  ? cx2 + col.w - 2 :
                           col.align === 'center' ? cx2 + col.w / 2 : cx2 + 2;
                T(col.header, tx, hY, { align: col.align === 'center' ? 'center' : col.align === 'right' ? 'right' : 'left' });
                cx2 += col.w;
            });

            // Rows
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7.5);

            if (topItems.length === 0) {
                pdf.setTextColor(...C.mgray);
                pdf.setFontSize(9);
                T('No bookings recorded for this month.', 14 + tW / 2, hY + 8, { align: 'center' });
            } else {
                topItems.forEach((item, idx) => {
                    const rowY = hY + 5 + idx * 7.5;

                    // Alternating row bg
                    if (idx % 2 === 0) {
                        pdf.setFillColor(...C.lgray);
                        pdf.rect(14, rowY - 4, tW, 7, 'F');
                    }

                    pdf.setTextColor(50, 55, 70);
                    let rx = 14;
                    const rowData = [
                        String(idx + 1),
                        safeText(item.name),
                        String(item.bookings),
                        peso(item.revenue)
                    ];
                    cols.forEach((col, ci) => {
                        pdf.setTextColor(50, 55, 70);
                        const tx = col.align === 'right'  ? rx + col.w - 2 :
                                   col.align === 'center' ? rx + col.w / 2 : rx + 2;
                        T(rowData[ci], tx, rowY, { align: col.align === 'center' ? 'center' : col.align === 'right' ? 'right' : 'left' });
                        rx += col.w;
                    });
                });
            }

            // ── Page 2 footer ────────────────────────────────────────────────────
            pdf.setFillColor(15, 35, 90);
            pdf.rect(0, H - 7, W, 7, 'F');
            pdf.setFillColor(212, 175, 55);
            pdf.rect(0, H - 7, W, 0.8, 'F');
            pdf.setTextColor(160, 190, 240);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7.5);
            T('Page 2 of 2  |  Lantaka Campus Analytics Report  |  Confidential', W / 2, H - 2.5, { align: 'center' });

            return pdf;
        }
    </script>

@endsection
                   
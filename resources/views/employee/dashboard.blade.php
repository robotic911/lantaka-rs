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
                    @else<span class="chg-neutral">—</span>@endif
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
                     <div class="day-header-container">
                        <div class="day-header">Sun</div>
                        <div class="day-header">Mon</div>
                        <div class="day-header">Tue</div>
                        <div class="day-header">Wed</div>
                        <div class="day-header">Thu</div>
                        <div class="day-header">Fri</div>
                        <div class="day-header">Sat</div>
                     </div>
                    <div class="days-container-month"></div>
                </div>
                <div class="calendar-grid-week hide">
                     <div class="day-header-container-week">
                        <div class="day-header-week">Sun</div>
                        <div class="day-header-week">Mon</div>
                        <div class="day-header-week">Tue</div>
                        <div class="day-header-week">Wed</div>
                        <div class="day-header-week">Thu</div>
                        <div class="day-header-week">Fri</div>
                        <div class="day-header-week">Sat</div>
                     </div>
                     <div class="days-container-week"></div>
                </div>
            </div>
            <br>
            <div class="calendar-legend">
                <div class="legend-item"><span class="legend-dot pending"></span>Pending (Initial Reservation)</div>
                <div class="legend-item"><span class="legend-dot confirmed"></span>Confirmed (Final Reservation)</div>
                <div class="legend-item"><span class="legend-dot checked-in"></span>Checked-In</div>
                <div class="legend-item"><span class="legend-dot checked-out"></span>Checked-Out</div>
            </div>
        </div>

        <!-- Export PDF Button -->
        <button class="export-btn" id="exportPdfBtn">📊 Export Report</button>
    </div>

    <script>
        window.reservations         = @json($reservations);
        window.statChanges          = @json($changes);
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

        /* ── Grouped Bar: Rooms vs Venues (reservations only) ── */
        async function buildComparisonChart(data) {
            return chartToImage({
                type: 'bar',
                data: {
                    labels: ['Checked-out Bookings'],
                    datasets: [
                        {
                            label: 'Rooms',
                            data: [data.roomCount],
                            backgroundColor: 'rgba(99,153,243,0.82)',
                            borderColor: '#6199f3',
                            borderWidth: 1.5,
                            borderRadius: 5,
                        },
                        {
                            label: 'Venues',
                            data: [data.venueCount],
                            backgroundColor: 'rgba(163,148,234,0.82)',
                            borderColor: '#a394ea',
                            borderWidth: 1.5,
                            borderRadius: 5,
                        }
                    ]
                },
                options: {
                    animation: { duration: 0 },
                    responsive: false,
                    layout: { padding: { top: 8, right: 16, bottom: 8, left: 8 } },
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 12, weight: '600' }, padding: 14, usePointStyle: true } },
                        title: {
                            display: true,
                            text: 'Rooms vs. Venues — Checked-out Reservations',
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

            // Header gradient band
            pdf.setFillColor(...C.blue);
            pdf.rect(0, 0, W, 30, 'F');

            // Decorative accent stripe
            pdf.setFillColor(255, 215, 0);
            pdf.rect(0, 28, W, 2.5, 'F');

            // Hotel name
            pdf.setTextColor(...C.white);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(20);
            T('LANTAKA HOTEL', 14, 13);

            // Report subtitle
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(10);
            pdf.setTextColor(180, 200, 255);
            T('Monthly Analytics Report', 14, 22);

            // Month label (right side)
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(17);
            pdf.setTextColor(...C.white);
            T(safeText(data.monthLabel).toUpperCase(), W - 14, 13, { align: 'right' });

            // Generated date (right)
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8.5);
            pdf.setTextColor(180, 200, 255);
            const _d = new Date();
            const _months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const genDate = _months[_d.getMonth()] + ' ' + _d.getDate() + ', ' + _d.getFullYear();
            T('Generated: ' + genDate, W - 14, 22, { align: 'right' });

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

            // Footer
            pdf.setFillColor(...C.blue);
            pdf.rect(0, H - 7, W, 7, 'F');
            pdf.setTextColor(180, 200, 255);
            pdf.setFontSize(7.5);
            T('Page 1 of 2  |  Lantaka Hotel Analytics Report  |  Confidential', W / 2, H - 2.5, { align: 'center' });

            /* ══════════ PAGE 2 ══════════ */
            pdf.addPage();

            // Header
            pdf.setFillColor(...C.blue);
            pdf.rect(0, 0, W, 20, 'F');
            pdf.setFillColor(255, 215, 0);
            pdf.rect(0, 18.5, W, 1.5, 'F');

            pdf.setTextColor(...C.white);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(13);
            T(safeText(data.monthLabel) + ' - Detailed Breakdown', 14, 13);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8.5);
            pdf.setTextColor(180, 200, 255);
            T('Lantaka Hotel Analytics Report', W - 14, 13, { align: 'right' });

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
            T('Top Performing Accommodations', 14, tY);

            const topItems = [
                ...data.topRooms.map(r  => ({ ...r, type: 'Room'  })),
                ...data.topVenues.map(v => ({ ...v, type: 'Venue' })),
            ].sort((a, b) => b.revenue - a.revenue).slice(0, 8);

            // Table column definitions
            const cols = [
                { header: '#',        w: 9,  align: 'center' },
                { header: 'Name',     w: 70, align: 'left'   },
                { header: 'Type',     w: 26, align: 'center' },
                { header: 'Bookings', w: 28, align: 'center' },
                { header: 'Revenue',  w: 44, align: 'right'  },
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

                    // Type badge color
                    const badgeColor = item.type === 'Room' ? [99, 153, 243] : [163, 148, 234];
                    pdf.setFillColor(...badgeColor);
                    const typeX = 14 + cols[0].w + cols[1].w;
                    pdf.roundedRect(typeX + 2, rowY - 3.5, cols[2].w - 4, 5.5, 1.5, 1.5, 'F');

                    pdf.setTextColor(50, 55, 70);
                    let rx = 14;
                    const rowData = [
                        String(idx + 1),
                        safeText(item.name),
                        safeText(item.type),
                        String(item.bookings),
                        peso(item.revenue)
                    ];
                    cols.forEach((col, ci) => {
                        if (ci === 2) {
                            pdf.setTextColor(255, 255, 255);
                        } else {
                            pdf.setTextColor(50, 55, 70);
                        }
                        const tx = col.align === 'right'  ? rx + col.w - 2 :
                                   col.align === 'center' ? rx + col.w / 2 : rx + 2;
                        T(rowData[ci], tx, rowY, { align: col.align === 'center' ? 'center' : col.align === 'right' ? 'right' : 'left' });
                        rx += col.w;
                    });
                });
            }

            // Footer
            pdf.setFillColor(...C.blue);
            pdf.rect(0, H - 7, W, 7, 'F');
            pdf.setTextColor(180, 200, 255);
            pdf.setFontSize(7.5);
            T('Page 2 of 2  |  Lantaka Hotel Analytics Report  |  Confidential', W / 2, H - 2.5, { align: 'center' });

            return pdf;
        }
    </script>

@endsection

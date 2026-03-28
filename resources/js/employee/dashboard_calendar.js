/**
 * dashboard_calendar.js  —  Gantt Timeline (grouped by Resource)
 *
 * Layout:
 *   [▼ ROOMS   · N rooms ]   ← collapsible group header
 *       🛏 Room 101           ← resource row  (bars = guest names)
 *       🛏 Room 102
 *   [▼ VENUES  · N venues]
 *       🏛 Function Hall A
 *
 * Preserved contracts:
 *   window.reservations        — initial blade @json
 *   window.calendarDataRoute   — live-poll endpoint
 *   window.reservationPage     — route for pending / confirmed
 *   window.guestPage           — route for checked-in / out
 *   updateStats(stats)         — KPI stat-card updater
 *   updateChanges(changes)     — MoM badge updater
 */

import dayjs from 'dayjs';

// ═══════════════════════════════════════════
//  CONSTANTS
// ═══════════════════════════════════════════
let   DAY_W       = 44;   // px per day column — recomputed each render()
const MIN_DAY_W   = 28;   // minimum column width before horizontal scrolling kicks in
const BAR_H       = 24;   // px — reservation bar height
const BAR_GAP     = 5;    // px — gap between stacked lanes in one row
const ROW_PAD     = 8;    // px — top/bottom padding in a resource row
const GROUP_H     = 36;   // px — group header row height

const MONTH_NAMES = ['January','Febraury','March','April','May','June','July','August','September','October','November','December'];
const DOW_NAMES   = ['Su','Mo','Tu','We','Th','Fr','Sa'];

// ═══════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════
const TODAY = (() => { const d = new Date(); d.setHours(0,0,0,0); return d; })();

let reservationData = window.reservations || [];
let rangeStart      = addDays(TODAY, -5);
let rangeSize       = 30;
let searchQ         = '';
let filterStatus    = '';
let filterType      = '';

// collapsed tracks group keys: 'rooms' | 'venues'
const collapsed    = new Set();
let syncingScroll  = false;

// ═══════════════════════════════════════════
//  DATE HELPERS
// ═══════════════════════════════════════════
function addDays(date, n) {
  const d = new Date(date);
  d.setDate(d.getDate() + n);
  return d;
}
function daysBetween(a, b) {
  return Math.round((b - a) / 86400000);
}
function parseDate(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  return new Date(y, m - 1, d);
}
function fmtShort(date) {
  return `${MONTH_NAMES[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}
function esc(s) {
  return String(s || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════
//  FILTER
// ═══════════════════════════════════════════
function getFiltered() {
  const q = searchQ.toLowerCase();
  return reservationData.filter(r => {
    const labelMatch  = !q || (r.label||'').toLowerCase().includes(q)
                           || (r.user?.name||'').toLowerCase().includes(q);
    const statMatch   = !filterStatus || r.status === filterStatus;
    const typeMatch   = !filterType   || r.type   === filterType;
    return labelMatch && statMatch && typeMatch;
  });
}

// ═══════════════════════════════════════════
//  GROUP BY RESOURCE (room / venue)
//  Always seeds every room/venue from window.allRooms / window.allVenues,
//  then merges in the filtered reservations — so empty rooms still show.
//  Returns two sorted arrays: rooms[], venues[]
//    each entry: { type, label, meta, rsvs[] }
// ═══════════════════════════════════════════
function groupByResource(data) {
  // Seed from the full room/venue catalogue
  const seedRooms  = (window.allRooms  || []).map(r => ({ ...r, rsvs: [] }));
  const seedVenues = (window.allVenues || []).map(v => ({ ...v, rsvs: [] }));

  // Build lookup maps keyed by label
  const roomMap  = new Map(seedRooms .map(r => [r.label, r]));
  const venueMap = new Map(seedVenues.map(v => [v.label, v]));

  // Attach filtered reservations to their resource row
  data.forEach(r => {
    const map = r.type === 'room' ? roomMap : venueMap;
    if (!map.has(r.label)) {
      // Reservation references a resource not in the catalogue — add it
      map.set(r.label, { type: r.type, label: r.label, meta: null, rsvs: [] });
    }
    map.get(r.label).rsvs.push(r);
  });

  const rooms  = [...roomMap .values()].sort((a, b) => a.label.localeCompare(b.label));
  const venues = [...venueMap.values()].sort((a, b) => a.label.localeCompare(b.label));
  return { rooms, venues };
}

// ═══════════════════════════════════════════
//  LANE ASSIGNMENT  (overlap stacking per row)
// ═══════════════════════════════════════════
function assignLanes(rsvs) {
  const sorted = [...rsvs].sort((a, b) => a.check_in.localeCompare(b.check_in));
  const laneEnds = [];

  const placed = sorted.map(res => {
    let lane = laneEnds.findIndex(end => res.check_in > end);
    if (lane === -1) { lane = laneEnds.length; laneEnds.push(res.check_out); }
    else             { laneEnds[lane] = res.check_out; }
    return { ...res, lane };
  });

  return { placed, laneCount: Math.max(1, laneEnds.length) };
}

// ═══════════════════════════════════════════
//  REDIRECT HELPER
// ═══════════════════════════════════════════
function getRedirect(res) {
  return ['checked-in','checked-out','completed'].includes(res.status)
    ? window.guestPage
    : window.reservationPage;
}

// ═══════════════════════════════════════════
//  RENDER — MAIN
// ═══════════════════════════════════════════
function render() {
  // ── Compute responsive column width from available chart area
  const rightEl = document.getElementById('ganttRight');
  if (rightEl) {
    DAY_W = Math.max(MIN_DAY_W, Math.floor(rightEl.clientWidth / rangeSize));
  }

  const data               = getFiltered();
  const { rooms, venues }  = groupByResource(data);
  const rangeEnd  = addDays(rangeStart, rangeSize);
  const totalW    = rangeSize * DAY_W;

  // Range label
  const lbl = document.getElementById('ganttRangeLabel');
  if (lbl) lbl.textContent =
    `${fmtShort(rangeStart)} – ${fmtShort(addDays(rangeEnd, -1))}`;

  // Resource count badge
  const ct = document.getElementById('ganttClientCount');
  if (ct) ct.textContent =
    `${rooms.length} room${rooms.length !== 1 ? 's' : ''}` +
    ` · ${venues.length} venue${venues.length !== 1 ? 's' : ''}`;

  // Inner width
  const inner = document.getElementById('ganttInner');
  if (inner) inner.style.width = totalW + 'px';

  renderDateHeader(rangeStart, rangeSize, totalW);
  renderResourceRows(rooms, venues, rangeStart, rangeEnd);
}

// ── Date header ──────────────────────────────
function renderDateHeader(start, days, totalW) {
  const el = document.getElementById('ganttDateHeader');
  if (!el) return;
  el.style.width = totalW + 'px';

  // Month spans
  const spans = [];
  let cur = null, curS = 0, curN = 0;
  for (let i = 0; i < days; i++) {
    const day = addDays(start, i);
    const key = `${day.getFullYear()}-${day.getMonth()}`;
    if (key !== cur) {
      if (cur !== null) spans.push({ s: curS, n: curN, d: addDays(start, curS) });
      cur = key; curS = i; curN = 1;
    } else { curN++; }
  }
  spans.push({ s: curS, n: curN, d: addDays(start, curS) });

  const monthHtml = spans.map(sp =>
    `<div style="position:absolute;left:${sp.s*DAY_W}px;width:${sp.n*DAY_W}px;height:22px;
      display:flex;align-items:center;padding-left:6px;font-size:9.5px;font-weight:700;
      color:#6b7280;text-transform:uppercase;letter-spacing:.3px;
      border-right:2px solid #e5e7eb;overflow:hidden;white-space:nowrap;box-sizing:border-box;">
      ${MONTH_NAMES[sp.d.getMonth()]} ${sp.d.getFullYear()}</div>`
  ).join('');

  const dayHtml = Array.from({ length: days }, (_, i) => {
    const day   = addDays(start, i);
    const isTod = daysBetween(TODAY, day) === 0;
    const isWkd = day.getDay() === 0 || day.getDay() === 6;
    const bg    = isTod ? 'rgba(59,130,246,.09)' : isWkd ? 'rgba(0,0,0,.02)' : 'transparent';
    const dc    = isTod ? '#1d4ed8' : '#6b7280';
    const fw    = isTod ? '700' : '600';
    return `<div style="position:absolute;left:${i*DAY_W}px;width:${DAY_W}px;height:32px;
      background:${bg};border-right:1px solid #e5e7eb;display:flex;flex-direction:column;
      align-items:center;justify-content:center;box-sizing:border-box;">
      <span style="font-size:9px;color:#9ca3af;line-height:1;">${DOW_NAMES[day.getDay()]}</span>
      <span style="font-size:11px;font-weight:${fw};color:${dc};line-height:1;">${day.getDate()}</span>
    </div>`;
  }).join('');

  el.innerHTML = `
    <div style="position:absolute;top:0;left:0;height:22px;width:${totalW}px;
      border-bottom:1px solid #e5e7eb;">${monthHtml}</div>
    <div style="position:absolute;top:22px;left:0;height:32px;width:${totalW}px;">${dayHtml}</div>`;
}

// ── Resource rows (sidebar + chart) ──────────
function renderResourceRows(rooms, venues, rangeStart, rangeEnd) {
  const sidebarEl = document.getElementById('ganttSidebarRows');
  const rowsEl    = document.getElementById('ganttRowsWrap');
  if (!sidebarEl || !rowsEl) return;

  const totalResources = rooms.length + venues.length;

  if (totalResources === 0) {
    sidebarEl.innerHTML = `<div class="gantt-empty">
      <div class="g-empty-icon">🔍</div>
      <p>No reservations match your filters.</p>
    </div>`;
    rowsEl.innerHTML = '';
    return;
  }

  // Column backgrounds (today + weekends) — spans full height
  let colBgsHtml = '';
  for (let i = 0; i < rangeSize; i++) {
    const day   = addDays(rangeStart, i);
    const isTod = daysBetween(TODAY, day) === 0;
    const isWkd = day.getDay() === 0 || day.getDay() === 6;
    if (isTod || isWkd) {
      colBgsHtml += `<div class="gantt-col-bg ${isTod ? 'g-today' : 'g-weekend'}"
        style="left:${i*DAY_W}px;width:${DAY_W}px;"></div>`;
    }
  }

  let sidebarHtml = '';
  let rowsHtml    = '';
  let rowIndex    = 0;  // for alt-row shading

  // ── Renders a single resource row (used by both flat + sub-grouped paths)
  function renderResourceRow(resource, icon) {
    const { placed, laneCount } = assignLanes(resource.rsvs);
    const rowH     = laneCount * (BAR_H + BAR_GAP) + ROW_PAD * 2;
    const rsvCount = resource.rsvs.length;
    const isEmpty  = rsvCount === 0;
    const countBadge = rsvCount > 0
      ? `<span class="g-res-count">${rsvCount}</span>`
      : '';

    sidebarHtml += `
      <div class="gantt-resource-row${isEmpty ? ' g-empty-row' : ''}" style="height:${rowH}px;">
        <span class="gantt-resource-icon">${icon}</span>
        <span class="gantt-resource-meta">
          <span class="gantt-resource-label">${esc(resource.label)}</span>
        </span>
        ${countBadge}
      </div>`;

    // Chart resource row — bars
    let barsHtml = '';
    placed.forEach(res => {
      const ciDate = parseDate(res.check_in);
      const coDate = parseDate(res.check_out);

      if (coDate < rangeStart || ciDate >= rangeEnd) return;

      const visStart = ciDate < rangeStart ? rangeStart : ciDate;
      const visEnd   = coDate > rangeEnd   ? rangeEnd   : coDate;
      const clipL    = ciDate < rangeStart;
      const clipR    = coDate > rangeEnd;

      const leftPx   = daysBetween(rangeStart, visStart) * DAY_W;
      const widthPx  = Math.max(DAY_W - 3, (daysBetween(visStart, visEnd) + 1) * DAY_W - 3);
      const topPx    = ROW_PAD + res.lane * (BAR_H + BAR_GAP);
      const nights   = daysBetween(ciDate, coDate);
      const clipCls  = [clipL ? 'g-clip-l' : '', clipR ? 'g-clip-r' : ''].filter(Boolean).join(' ');
      const redirect = getRedirect(res);
      const guestName = res.user?.name || '----';
      const purpose = res.purpose || '--';

      const ttData = JSON.stringify({
        label: res.label, type: res.type, status: res.status,
        check_in: res.check_in, check_out: res.check_out,
        name: guestName, nights,
        purpose: res.purpose || 'N/A',
      }).replace(/'/g, '&#39;');

      barsHtml += `
        <a class="gantt-bar ${res.status} ${clipCls}"
           style="left:${leftPx}px;width:${widthPx}px;top:${topPx}px;height:${BAR_H}px;"
           href="${getRedirect(res)}/${encodeURIComponent(res.id)}?type=${encodeURIComponent(res.type)}"
           data-tt='${ttData}'
           onmouseenter="window.__ganttShowTT(event,this)"
           onmouseleave="window.__ganttHideTT()">
          <span class="g-text g-spacing-text">${esc(purpose)} | ${esc(guestName)}</span>
        </a>`;
    });

    rowsHtml += `
      <div class="gantt-chart-row${isEmpty ? ' g-empty-row' : ''}" style="height:${rowH}px;">
        ${barsHtml}
      </div>`;
  }

  // ── Renders one group section (rooms or venues)
  //    useSubGroups=true → rooms are sub-divided by meta (Room_Type)
  function renderGroup(resources, groupKey, icon, label, accentClass, useSubGroups = false) {
    if (resources.length === 0 && filterType) return;

    const isCollapsed = collapsed.has(groupKey);
    const count       = resources.length;

    // ── Group header (sidebar)
    sidebarHtml += `
      <div class="gantt-group-header ${accentClass}"
           onclick="window.__ganttToggleGroup('${groupKey}')">
        <span class="gantt-group-icon">${icon}</span>
        <span class="gantt-group-label">${label}</span>
        <span class="gantt-group-count">${count} ${label.toLowerCase()}</span>
        <span class="gantt-group-arrow${isCollapsed ? ' g-collapsed' : ''}">▼</span>
      </div>`;

    // ── Group header (chart) — full-width tinted band
    rowsHtml += `
      <div class="gantt-chart-group-header ${accentClass}"
           style="height:${GROUP_H}px;">
      </div>`;

    if (isCollapsed) return;

    if (useSubGroups) {
      // ── Sub-group by Room_Type (meta)
      const typeMap = new Map();
      resources.forEach(r => {
        const t = r.meta || 'Other';
        if (!typeMap.has(t)) typeMap.set(t, []);
        typeMap.get(t).push(r);
      });

      // Sort type keys: Single → Double → Triple → anything else
      const ORDER = ['Single', 'Double', 'Triple'];
      const sortedTypes = [...typeMap.keys()].sort((a, b) => {
        const ai = ORDER.indexOf(a), bi = ORDER.indexOf(b);
        if (ai !== -1 && bi !== -1) return ai - bi;
        if (ai !== -1) return -1;
        if (bi !== -1) return  1;
        return a.localeCompare(b);
      });

      sortedTypes.forEach(typeName => {
        const typeKey        = `${groupKey}::${typeName}`;
        const typeResources  = typeMap.get(typeName);
        const typeCollapsed  = collapsed.has(typeKey);
        const typeCount      = typeResources.length;

        // Sub-group header — sidebar
        sidebarHtml += `
          <div class="gantt-type-header"
               onclick="window.__ganttToggleGroup('${typeKey}')">
            <span class="gantt-type-label">${esc(typeName)}</span>
            <span class="gantt-group-arrow${typeCollapsed ? ' g-collapsed' : ''}">▼</span>
          </div>`;

        // Sub-group header — chart band
        rowsHtml += `<div class="gantt-chart-type-header" style="height:${GROUP_H - 6}px;"></div>`;

        if (!typeCollapsed) {
          typeResources.forEach(resource => renderResourceRow(resource, icon));
        }
      });
    } else {
      // ── Flat (venues)
      resources.forEach(resource => renderResourceRow(resource, icon));
    }

    // (resource row rendering now handled inside renderResourceRow / sub-group block)
    return;
  }

  // Render both groups — rooms use sub-grouping by Room_Type
  renderGroup(rooms,  'rooms',  '🛏', 'Rooms',  'g-group-room',  true);
  renderGroup(venues, 'venues', '🏛', 'Venues', 'g-group-venue', false);

  sidebarEl.innerHTML = sidebarHtml;
  rowsEl.innerHTML    = colBgsHtml + rowsHtml;
}

// ═══════════════════════════════════════════
//  SCROLL SYNC
// ═══════════════════════════════════════════
function setupScrollSync() {
  const right   = document.getElementById('ganttRight');
  const sidebar = document.getElementById('ganttSidebarRows');
  if (!right || !sidebar) return;

  right.addEventListener('scroll', () => {
    if (syncingScroll) return;
    syncingScroll = true;
    sidebar.scrollTop = right.scrollTop;
    syncingScroll = false;
  }, { passive: true });

  sidebar.addEventListener('scroll', () => {
    if (syncingScroll) return;
    syncingScroll = true;
    right.scrollTop = sidebar.scrollTop;
    syncingScroll = false;
  }, { passive: true });
}

// ═══════════════════════════════════════════
//  COLLAPSE TOGGLE  (group level)
// ═══════════════════════════════════════════
window.__ganttToggleGroup = function (groupKey) {
  if (collapsed.has(groupKey)) collapsed.delete(groupKey);
  else collapsed.add(groupKey);
  render();
};

// ═══════════════════════════════════════════
//  TOOLTIP
// ═══════════════════════════════════════════
const ttEl = document.getElementById('ganttTooltip');

window.__ganttShowTT = function (e, el) {
  if (!ttEl) return;
  const d    = JSON.parse(el.dataset.tt);
  ttEl.innerHTML = `
    <div class="g-tt-title">${esc(d.label)}</div>
    <div class="g-tt-row"><span class="g-tt-key">Guest</span>${esc(d.name)}</div>
    <div class="g-tt-row"><span class="g-tt-key">Check-in</span>${d.check_in}</div>
    <div class="g-tt-row"><span class="g-tt-key">Check-out</span>${d.check_out}</div>
    <div class="g-tt-row"><span class="g-tt-key">Duration</span>${d.nights} night${d.nights !== 1 ? 's' : ''}</div>
    <div class="g-tt-row"><span class="g-tt-key">Purpose</span>${d.purpose}</div>
    <div class="g-tt-row"><span class="g-tt-key">Status</span>
      <span class="g-tt-badge ${d.status}">${d.status}</span></div>`;
  ttEl.style.display = 'block';
  moveTT(e);
};
window.__ganttHideTT = function () { if (ttEl) ttEl.style.display = 'none'; };

function moveTT(e) {
  if (!ttEl) return;
  const PAD = 14;
  let x = e.clientX + PAD, y = e.clientY + PAD;
  if (x + 260 > window.innerWidth)  x = e.clientX - 260 - PAD;
  if (y + 170 > window.innerHeight) y = e.clientY - 170 - PAD;
  ttEl.style.left = x + 'px';
  ttEl.style.top  = y + 'px';
}
document.addEventListener('mousemove', e => {
  if (ttEl && ttEl.style.display === 'block') moveTT(e);
});

// ═══════════════════════════════════════════
//  STAT CARD UPDATERS  (unchanged)
// ═══════════════════════════════════════════
function updateStats(stats) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('totalReservationsValue', stats.totalReservations ?? 0);
  set('occupancyRateValue',     `${Number(stats.occupancyRate ?? 0).toFixed(1)}%`);
  set('totalRevenueValue',      `₱${Number(stats.totalRevenue ?? 0).toLocaleString()}`);
  set('activeGuestsValue',      stats.activeGuests ?? 0);
  set('checkOutsTodayValue',    stats.checkOutsTodayCount ?? 0);
}

function changeHtml(val, labelText) {
  const v = Number(val ?? 0);
  const badge = v > 0 ? `<span class="chg-positive">↑ ${v}%</span>`
              : v < 0 ? `<span class="chg-negative">↓ ${Math.abs(v)}%</span>`
                      : `<span class="chg-neutral">—</span>`;
  return `${badge} <span class="chg-label">${labelText}</span>`;
}

function updateChanges(changes) {
  if (!changes) return;
  const lbl = changes.lastMonthLabel || 'last month';
  const map = {
    changeTotalReservations: [changes.totalReservations, `vs ${lbl}`],
    changeOccupancyRate:     [changes.occupancyRate,     'vs prev 30 days'],
    changeRevenue:           [changes.revenue,            `vs ${lbl}`],
    changeActiveGuests:      [changes.activeGuests,       `vs ${lbl}`],
    changeCheckOuts:         [changes.checkOutsToday,     `vs ${lbl}`],
  };
  Object.entries(map).forEach(([id, [val, label]]) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = changeHtml(val, label);
  });
}

// ═══════════════════════════════════════════
//  LIVE FETCH  (polls every 10 s)
// ═══════════════════════════════════════════
async function fetchReservations() {
  try {
    const res = await fetch(window.calendarDataRoute, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    reservationData = data.reservations || [];
    updateStats(data.stats || {});
    updateChanges(data.changes || window.statChanges || {});
    render();
  } catch (err) {
    console.warn('Gantt: refresh failed —', err);
  }
}

// ═══════════════════════════════════════════
//  CONTROLS
// ═══════════════════════════════════════════
function scrollTodayIntoView() {
  requestAnimationFrame(() => {
    const right = document.getElementById('ganttRight');
    if (!right) return;
    right.scrollLeft = Math.max(0, daysBetween(rangeStart, TODAY) * DAY_W - right.clientWidth / 3);
  });
}

document.addEventListener('DOMContentLoaded', () => {

  document.getElementById('ganttPrev')?.addEventListener('click', () => {
    rangeStart = addDays(rangeStart, -Math.floor(rangeSize / 2));
    render();
  });

  document.getElementById('ganttNext')?.addEventListener('click', () => {
    rangeStart = addDays(rangeStart, Math.floor(rangeSize / 2));
    render();
  });

  document.getElementById('ganttToday')?.addEventListener('click', () => {
    rangeStart = addDays(TODAY, -Math.floor(rangeSize / 5));
    render();
    scrollTodayIntoView();
  });

  document.querySelectorAll('.gantt-range-group button[data-r]').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.gantt-range-group button').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      rangeSize = parseInt(this.dataset.r);
      render();
    });
  });

  let searchTimer;
  document.getElementById('ganttSearch')?.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { searchQ = this.value.trim(); render(); }, 200);
  });

  document.getElementById('ganttStatusSel')?.addEventListener('change', function () {
    filterStatus = this.value; render();
  });

  document.getElementById('ganttTypeSel')?.addEventListener('change', function () {
    filterType = this.value; render();
  });

  setupScrollSync();
  render();
  scrollTodayIntoView();

  // Re-render whenever the chart area is resized (sidebar toggle, window resize, etc.)
  const rightEl = document.getElementById('ganttRight');
  if (rightEl && typeof ResizeObserver !== 'undefined') {
    let resizeTimer;
    new ResizeObserver(() => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(render, 60);  // debounce — 60 ms
    }).observe(rightEl);
  }

  fetchReservations();
  setInterval(fetchReservations, 10000);
});

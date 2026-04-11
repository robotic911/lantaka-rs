/**
 * dashboard_calendar.js — Apple Calendar-style Reservation Calendar
 *
 * Views    : Month (default), Week
 * Modes    : Detailed (default), Stacked
 * Features :
 *   • Status legend colour-coding
 *   • Cancelled + Rejected hidden by default; visible when explicitly filtered
 *   • Status filter dropdown (includes Cancelled / Rejected)
 *   • Room-reservation grouping (same dates + guest name + purpose → one chip)
 *   • Hover tooltip — shows all grouped rooms
 *   • Multi-day event spanning bars in Month view (Apple-Calendar style)
 *   • Click a date cell → expands that cell AND all cells sharing the same
 *     reservations (multi-week spanning) to show every event; click again to collapse
 *   • Live-poll refresh every 10 s
 *
 * Preserved contracts:
 *   window.reservations / window.calendarDataRoute
 *   window.reservationPage / window.guestPage
 *   updateStats(stats) / updateChanges(changes)
 */

// ══════════════════════════════════════════════
//  CONSTANTS
// ══════════════════════════════════════════════

const STATUS_CFG = {
  'pending':     { bg: '#fef3c7', border: '#fbbf24', text: '#92400e', solid: '#f59e0b' },
  'confirmed':   { bg: '#dbeafe', border: '#60a5fa', text: '#1e40af', solid: '#3b82f6' },
  'checked-in':  { bg: '#dcfce7', border: '#4ade80', text: '#166534', solid: '#22c55e' },
  'checked-out': { bg: '#f3f4f6', border: '#9ca3af', text: '#374151', solid: '#9ca3af' },
  'completed':   { bg: '#ede9fe', border: '#a78bfa', text: '#5b21b6', solid: '#8b5cf6' },
  'cancelled':   { bg: '#fee2e2', border: '#f87171', text: '#991b1b', solid: '#ef4444' },
  'rejected':    { bg: '#fee2e2', border: '#f87171', text: '#991b1b', solid: '#ef4444' },
};

/**
 * Statuses hidden unless the user explicitly filters for them.
 * When statusFilter === 'cancelled' | 'rejected' → show those.
 * When statusFilter === '' (All) → still hide these.
 */
const HIDDEN_BY_DEFAULT = new Set(['cancelled', 'rejected']);

const DOW_SHORT    = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
const MONTH_NAMES  = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];

const MAX_VISIBLE_LANES = 3;   // month view: lanes beyond this fold to "+N more"
const LANE_H_DETAIL     = 22;  // px
const LANE_H_STACK      = 14;  // px

// ══════════════════════════════════════════════
//  STATE
// ══════════════════════════════════════════════

let reservationData = window.reservations || [];
let calView         = 'month';    // 'month' | 'week'
let dispMode        = 'detailed'; // 'detailed' | 'stacked'
let statusFilter    = '';

const TODAY = (() => { const d = new Date(); d.setHours(0,0,0,0); return d; })();
let viewDate = new Date(TODAY);

/**
 * Tracks which WEEK ROWS are currently expanded.
 * Keys are ISO strings of the week-row's Monday (Sun-based weekStart).
 * An expanded week row shows ALL lanes — no "+N more" truncation.
 */
const expandedWeeks = new Set();

// ══════════════════════════════════════════════
//  DATE HELPERS
// ══════════════════════════════════════════════

function parseDate(iso) {
  const [y,m,d] = iso.split('-').map(Number);
  return new Date(y, m-1, d);
}
function addDays(date, n) {
  const d = new Date(date); d.setDate(d.getDate() + n); return d;
}
function daysBetween(a, b) {
  return Math.round((b - a) / 86400000);
}
function isSameDay(a, b) {
  return a.getFullYear() === b.getFullYear()
      && a.getMonth()    === b.getMonth()
      && a.getDate()     === b.getDate();
}
function dateToISO(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function esc(s) {
  return String(s||'')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function getWeekStart(date) {
  const d = new Date(date);
  d.setDate(d.getDate() - d.getDay());
  d.setHours(0,0,0,0);
  return d;
}

// ══════════════════════════════════════════════
//  FILTER
//  • 'cancelled' / 'rejected' only shown when explicitly selected
//  • All other statuses shown normally
// ══════════════════════════════════════════════

function getFiltered() {
  return reservationData.filter(r => {
    // If user explicitly chose cancelled or rejected, show only that status
    if (statusFilter === 'cancelled' || statusFilter === 'rejected') {
      return r.status === statusFilter;
    }
    // Otherwise always hide cancelled / rejected
    if (HIDDEN_BY_DEFAULT.has(r.status)) return false;
    if (statusFilter && r.status !== statusFilter) return false;
    return true;
  });
}

// ══════════════════════════════════════════════
//  GROUPING
//  Room reservations: identical check_in + check_out + guest + purpose
//  → merged into one chip; tooltip shows all rooms.
// ══════════════════════════════════════════════

function groupEvents(data) {
  const rooms  = data.filter(r => r.type === 'room');
  const venues = data.filter(r => r.type !== 'room');
  const processed = new Set();
  const events = [];

  rooms.forEach(r => {
    if (processed.has(r.id)) return;
    const siblings = rooms.filter(o =>
      !processed.has(o.id) &&
      o.check_in  === r.check_in  &&
      o.check_out === r.check_out &&
      (o.user?.name || '') === (r.user?.name || '') &&
      (o.purpose  || '') === (r.purpose  || '')
    );
    siblings.forEach(s => processed.add(s.id));
    events.push({
      id        : r.id,
      type      : 'room',
      status    : r.status,
      check_in  : r.check_in,
      check_out : r.check_out,
      guestName : r.user?.name  || 'Guest',
      purpose   : r.purpose     || 'N/A',
      rooms     : siblings.map(s => s.label),
      label     : siblings.length > 1 ? `${siblings.length} Rooms` : r.label,
      isGrouped : siblings.length > 1,
    });
  });

  venues.forEach(r => {
    events.push({
      id        : r.id,
      type      : 'venue',
      status    : r.status,
      check_in  : r.check_in,
      check_out : r.check_out,
      guestName : r.user?.name || 'Guest',
      purpose   : r.purpose    || 'N/A',
      rooms     : [],
      label     : r.label,
      isGrouped : false,
    });
  });

  return events;
}

// ══════════════════════════════════════════════
//  LANE ASSIGNMENT  (greedy, per-week-row)
// ══════════════════════════════════════════════

function assignLanesForWeek(events, weekStart) {
  const weekEnd = addDays(weekStart, 7);

  const sorted = [...events].sort((a, b) => {
    const aLen = daysBetween(parseDate(a.check_in), parseDate(a.check_out));
    const bLen = daysBetween(parseDate(b.check_in), parseDate(b.check_out));
    if (bLen !== aLen) return bLen - aLen;
    return a.check_in.localeCompare(b.check_in);
  });

  const laneEnds = [];
  const placed   = [];

  sorted.forEach(evt => {
    const evtStart = parseDate(evt.check_in);
    const evtEnd   = parseDate(evt.check_out);
    const visStart = evtStart < weekStart ? weekStart : evtStart;
    const visEnd   = evtEnd   > weekEnd   ? weekEnd   : evtEnd;
    const colStart = daysBetween(weekStart, visStart);
    // +1 makes the bar span check_in → check_out INCLUSIVE (checkout day is visible),
    // capped at 7 so it never overflows the week row.
    const colEndIncl = Math.min(7, daysBetween(weekStart, visEnd) + 1);
    const spanLen    = Math.max(1, colEndIncl - colStart);

    let lane = laneEnds.findIndex(end => end <= colStart);
    if (lane === -1) { lane = laneEnds.length; laneEnds.push(colEndIncl); }
    else             { laneEnds[lane] = colEndIncl; }

    placed.push({
      evt,
      lane,
      colStart,
      spanLen,
      startsInWeek : evtStart >= weekStart,
      endsInWeek   : evtEnd   <= weekEnd,
    });
  });

  return { placed, maxLane: laneEnds.length };
}

// ══════════════════════════════════════════════
//  CLICK-TO-EXPAND  (month view)
//
//  Clicking a date cell expands the week row(s) of all events
//  that touch that date — so a reservation spanning 2 weeks
//  will expand both rows simultaneously.
//  Clicking again collapses the same set.
// ══════════════════════════════════════════════

function handleDayClick(clickedDate) {
  const clickedWeekStart = getWeekStart(clickedDate);
  const clickedKey       = dateToISO(clickedWeekStart);
  const isExpanded       = expandedWeeks.has(clickedKey);

  // Gather all events that touch the clicked date
  const events = groupEvents(getFiltered());
  const touching = events.filter(evt => {
    const s = parseDate(evt.check_in);
    const e = parseDate(evt.check_out);
    return s <= clickedDate && e > clickedDate;
  });

  // Collect every week-row that those events span
  const affectedKeys = new Set([clickedKey]);
  touching.forEach(evt => {
    let ws  = getWeekStart(parseDate(evt.check_in));
    const e = parseDate(evt.check_out);
    while (ws < e) {
      affectedKeys.add(dateToISO(ws));
      ws = addDays(ws, 7);
    }
  });

  if (isExpanded) {
    affectedKeys.forEach(k => expandedWeeks.delete(k));
  } else {
    affectedKeys.forEach(k => expandedWeeks.add(k));
  }

  render();
}

// ══════════════════════════════════════════════
//  CHIP CONTENT BUILDERS
// ══════════════════════════════════════════════

function buildChipContent_detailed(evt) {
  const icon    = evt.type === 'room' ? '🛏' : '🏛';
  const roomStr = evt.isGrouped ? `${evt.rooms.length} Rooms` : evt.label;

  const purpose = (evt.purpose || '').trim();
  const hasPurpose = purpose && purpose.toLowerCase() !== 'n/a';

  return `<span class="ac-chip-icon">${icon}</span> | `
    + `<span class="ac-chip-body">`
    +   (hasPurpose ? `<span class="ac-chip-purpose">${esc(purpose)}</span>` : ``)
    +   `<span class="ac-chip-name">${esc(evt.guestName)}</span>`
    +   `<span class="ac-chip-room">${esc(roomStr)}</span>`
    + `</span>`;
}

function buildChipContent_stacked(evt, dotColor) {
  const purpose = (evt.purpose || '').trim();
  const hasPurpose = purpose && purpose.toLowerCase() !== 'n/a';
  const purposePart = hasPurpose ? `<span class="ac-chip-purpose-stacked"> · ${esc(purpose)}</span>` : '';
  return `<span class="ac-chip-dot" style="background:${dotColor};"></span>`
    + `<span class="ac-chip-stacked-text">`
    +   `<span class="ac-chip-name-only">${esc(evt.guestName)}</span>`
    +   purposePart
    + `</span>`;
}

function chipTTData(evt) {
  return JSON.stringify({
    guestName : evt.guestName,
    purpose   : evt.purpose,
    status    : evt.status,
    check_in  : evt.check_in,
    check_out : evt.check_out,
    type      : evt.type,
    label     : evt.label,
    rooms     : evt.rooms,
    isGrouped : evt.isGrouped,
  }).replace(/'/g,'&#39;');
}

// ══════════════════════════════════════════════
//  REDIRECT HELPER
// ══════════════════════════════════════════════

function getRedirect(evt) {
  return ['checked-in','checked-out','completed'].includes(evt.status)
    ? window.guestPage
    : window.reservationPage;
}

// ══════════════════════════════════════════════
//  MONTH VIEW
// ══════════════════════════════════════════════

function renderMonthView(events) {
  const grid = document.getElementById('calGrid');
  if (!grid) return;

  const year  = viewDate.getFullYear();
  const month = viewDate.getMonth();

  const firstDay  = new Date(year, month, 1);
  const gridStart = new Date(firstDay);
  gridStart.setDate(gridStart.getDate() - gridStart.getDay());

  const lastDay = new Date(year, month + 1, 0);
  const gridEnd = new Date(lastDay);
  if (gridEnd.getDay() !== 6) gridEnd.setDate(gridEnd.getDate() + (6 - gridEnd.getDay()));
  gridEnd.setDate(gridEnd.getDate() + 1); // exclusive

  const totalWeeks = Math.ceil(daysBetween(gridStart, gridEnd) / 7);
  const laneH      = dispMode === 'stacked' ? LANE_H_STACK : LANE_H_DETAIL;

  let html = `<div class="ac-month-grid">`;

  // Day-of-week header
  html += `<div class="ac-dow-row">`;
  DOW_SHORT.forEach(d => { html += `<div class="ac-dow-cell">${d}</div>`; });
  html += `</div>`;

  // Week rows
  for (let w = 0; w < totalWeeks; w++) {
    const weekStart  = addDays(gridStart, w * 7);
    const weekEnd    = addDays(weekStart, 7);
    const weekKeyStr = dateToISO(weekStart);
    const isExpanded = expandedWeeks.has(weekKeyStr);

    // Events overlapping this week
    const weekEvents = events.filter(evt => {
      const s = parseDate(evt.check_in);
      const e = parseDate(evt.check_out);
      return s < weekEnd && e > weekStart;
    });

    const { placed, maxLane } = assignLanesForWeek(weekEvents, weekStart);

    // When expanded → show every lane; otherwise cap at MAX_VISIBLE_LANES
    const visibleLaneCount = isExpanded ? maxLane : Math.min(maxLane, MAX_VISIBLE_LANES);

    // "+N more" per day (only when NOT expanded)
    const dayMore = Array(7).fill(0);
    if (!isExpanded) {
      placed.forEach(({ lane, colStart, spanLen }) => {
        if (lane >= MAX_VISIBLE_LANES) {
          for (let c = colStart; c < colStart + spanLen && c < 7; c++) dayMore[c]++;
        }
      });
    }
    const hasMore  = dayMore.some(n => n > 0);
    // Always at least 100 px so empty weeks stay visually uniform.
    const rowH = Math.max(100, 30 + visibleLaneCount * (laneH + 3) + (hasMore ? 20 : 0));

    html += `<div class="ac-week-row${isExpanded ? ' ac-row-expanded' : ''}"
      data-week-start="${weekKeyStr}"
      style="min-height:${rowH}px;">`;

    // ── Day-cell backgrounds + date numbers ──
    html += `<div class="ac-week-days">`;
    for (let d = 0; d < 7; d++) {
      const day          = addDays(weekStart, d);
      const isToday      = isSameDay(day, TODAY);
      const isOtherMonth = day.getMonth() !== month;
      const isWeekend    = d === 0 || d === 6;
      html += `<div class="ac-day-cell${isToday?' ac-today':''}${isOtherMonth?' ac-other-month':''}${isWeekend?' ac-weekend':''}">
        <span class="ac-day-num-wrap">
          <span class="ac-day-num${isToday?' ac-today-circle':''}">${day.getDate()}</span>
        </span>
      </div>`;
    }
    html += `</div>`;

    // ── Expand hint (top-right of row when has hidden events) ──
    if (hasMore && !isExpanded) {
      html += `<div class="ac-expand-hint" title="Click any date cell to expand">▾</div>`;
    }
    if (isExpanded) {
      html += `<div class="ac-collapse-hint" title="Click any date cell to collapse">▴ collapse</div>`;
    }

    // ── Event chips (absolutely positioned spanning bars) ──
    html += `<div class="ac-events-layer">`;
    placed
      .filter(p => isExpanded || p.lane < MAX_VISIBLE_LANES)
      .forEach(({ evt, lane, colStart, spanLen, startsInWeek, endsInWeek }) => {
        const cfg  = STATUS_CFG[evt.status] || STATUS_CFG['pending'];
        const top  = 30 + lane * (laneH + 3);
        const l    = `calc(${(colStart/7)*100}% + 1px)`;
        const w    = `calc(${(spanLen/7)*100}% - 2px)`;
        const cls  = [
          'ac-chip', evt.status,
          !startsInWeek ? 'ac-cont-l' : '',
          !endsInWeek   ? 'ac-cont-r' : '',
          dispMode === 'stacked' ? 'ac-stacked' : '',
        ].filter(Boolean).join(' ');

        const inner = dispMode === 'detailed'
          ? buildChipContent_detailed(evt)
          : buildChipContent_stacked(evt, cfg.solid);

        html += `<a class="${cls}"
          style="top:${top}px;left:${l};width:${w};height:${laneH}px;background:${cfg.bg};border-color:${cfg.border};color:${cfg.text};"
          href="${getRedirect(evt)}/${encodeURIComponent(evt.id)}?type=${encodeURIComponent(evt.type)}"
          data-tt='${chipTTData(evt)}'
          onmouseenter="window.__calShowTT(event,this)"
          onmouseleave="window.__calHideTT()"
        >${inner}</a>`;
      });

    // "+N more" labels (only when not expanded)
    if (hasMore) {
      for (let d = 0; d < 7; d++) {
        if (!dayMore[d]) continue;
        const top  = 30 + MAX_VISIBLE_LANES * (laneH + 3);
        const l    = `calc(${(d/7)*100}% + 3px)`;
        const w    = `calc(${(1/7)*100}% - 6px)`;
        html += `<span class="ac-more-lbl" style="top:${top}px;left:${l};width:${w};">+${dayMore[d]} more</span>`;
      }
    }

    html += `</div>`; // /ac-events-layer
    html += `</div>`; // /ac-week-row
  }

  html += `</div>`; // /ac-month-grid
  grid.innerHTML = html;
}

// ══════════════════════════════════════════════
//  WEEK VIEW
// ══════════════════════════════════════════════

function renderWeekView(events) {
  const grid = document.getElementById('calGrid');
  if (!grid) return;

  const weekStart = getWeekStart(viewDate);
  const weekEnd   = addDays(weekStart, 7);

  const weekEvents = events.filter(evt => {
    const s = parseDate(evt.check_in);
    const e = parseDate(evt.check_out);
    return s < weekEnd && e > weekStart;
  });

  const { placed, maxLane } = assignLanesForWeek(weekEvents, weekStart);
  const laneH   = dispMode === 'stacked' ? 28 : 62;
  const evtArea = Math.max(160, maxLane * (laneH + 6) + 16);

  let html = `<div class="ac-week-grid">`;

  // Column headers
  html += `<div class="ac-week-header-row">`;
  for (let d = 0; d < 7; d++) {
    const day       = addDays(weekStart, d);
    const isToday   = isSameDay(day, TODAY);
    const isWeekend = d === 0 || d === 6;
    html += `<div class="ac-week-hcell${isToday?' ac-today':''}${isWeekend?' ac-weekend':''}">
      <span class="ac-week-dow-lbl">${DOW_SHORT[d]}</span>
      <span class="ac-week-date-num${isToday?' ac-today-circle':''}">${day.getDate()}</span>
    </div>`;
  }
  html += `</div>`;

  // Event canvas
  html += `<div class="ac-week-body" style="height:${evtArea}px;position:relative;">`;

  // Column bg strips
  for (let d = 0; d < 7; d++) {
    const day       = addDays(weekStart, d);
    const isToday   = isSameDay(day, TODAY);
    const isWeekend = d === 0 || d === 6;
    html += `<div class="ac-week-col-bg${isToday?' ac-today-col':''}${isWeekend?' ac-wknd-col':''}"
      style="left:${(d/7)*100}%;width:${100/7}%;"></div>`;
  }

  // Event chips
  placed.forEach(({ evt, lane, colStart, spanLen, startsInWeek, endsInWeek }) => {
    const cfg    = STATUS_CFG[evt.status] || STATUS_CFG['pending'];
    const nights = daysBetween(parseDate(evt.check_in), parseDate(evt.check_out));
    const top    = lane * (laneH + 6) + 6;
    const l      = `calc(${(colStart/7)*100}% + 4px)`;
    const w      = `calc(${(spanLen/7)*100}% - 8px)`;
    const cls    = [
      'ac-week-chip', evt.status,
      !startsInWeek ? 'ac-cont-l' : '',
      !endsInWeek   ? 'ac-cont-r' : '',
      dispMode === 'stacked' ? 'ac-stacked' : '',
    ].filter(Boolean).join(' ');
    const icon    = evt.type === 'room' ? '🛏' : '🏛';
    const roomStr = evt.isGrouped ? `${evt.rooms.length} Rooms` : evt.label;

    let inner = '';
    if (dispMode === 'detailed') {
      inner = `<div class="ac-wk-chip-head">
          <span class="ac-chip-icon">${icon}</span>
          <span class="ac-wk-sdot" style="background:${cfg.solid};"></span>
          <span class="ac-wk-gname">${esc(evt.guestName)}</span>
        </div>
        <div class="ac-wk-chip-body">
          <span class="ac-wk-purpose">${esc(evt.purpose)}</span>
          <span class="ac-wk-room">${esc(roomStr)}</span>
          <span class="ac-wk-nights">${nights}n</span>
        </div>`;
    } else {
      inner = buildChipContent_stacked(evt, cfg.solid);
    }

    html += `<a class="${cls}"
      style="position:absolute;top:${top}px;left:${l};width:${w};min-height:${laneH}px;
             background:${cfg.bg};border-left:3px solid ${cfg.solid};color:${cfg.text};"
      href="${getRedirect(evt)}/${encodeURIComponent(evt.id)}?type=${encodeURIComponent(evt.type)}"
      data-tt='${chipTTData(evt)}'
      onmouseenter="window.__calShowTT(event,this)"
      onmouseleave="window.__calHideTT()"
    >${inner}</a>`;
  });

  html += `</div>`; // /ac-week-body
  html += `</div>`; // /ac-week-grid
  grid.innerHTML = html;
}

// ══════════════════════════════════════════════
//  TOOLTIP
// ══════════════════════════════════════════════

const ttEl = (() => {
  const el = document.createElement('div');
  el.className     = 'ac-tooltip';
  el.style.display = 'none';
  document.body.appendChild(el);
  return el;
})();

window.__calShowTT = function(e, el) {
  const d      = JSON.parse(el.dataset.tt);
  const cfg    = STATUS_CFG[d.status] || {};
  const nights = daysBetween(parseDate(d.check_in), parseDate(d.check_out));
  const icon   = d.type === 'room' ? '🛏' : '🏛';
  const statusLabel = d.status.replace(/-/g,' ').replace(/\b\w/g, c => c.toUpperCase());

  let roomsHtml = '';
  if (d.isGrouped && d.rooms && d.rooms.length > 1) {
    roomsHtml = `<div class="ac-tt-rooms">${d.rooms.map(r=>`<span class="ac-tt-room-tag">${esc(r)}</span>`).join('')}</div>`;
  }

  ttEl.innerHTML = `
    <div class="ac-tt-head">
      <span class="ac-tt-icon">${icon}</span>
      <span class="ac-tt-guest">${esc(d.guestName)}</span>
      <span class="ac-tt-badge" style="background:${cfg.bg};color:${cfg.text};border:1px solid ${cfg.border||'transparent'};">${statusLabel}</span>
    </div>
    <div class="ac-tt-row"><span class="ac-tt-k">Purpose</span><span>${esc(d.purpose)}</span></div>
    <div class="ac-tt-row"><span class="ac-tt-k">Check-in</span><span>${d.check_in}</span></div>
    <div class="ac-tt-row"><span class="ac-tt-k">Check-out</span><span>${d.check_out}</span></div>
    <div class="ac-tt-row"><span class="ac-tt-k">Duration</span><span>${nights} night${nights!==1?'s':''}</span></div>
    ${d.isGrouped
      ? `<div class="ac-tt-row"><span class="ac-tt-k">Rooms (${d.rooms.length})</span></div>${roomsHtml}`
      : `<div class="ac-tt-row"><span class="ac-tt-k">${d.type==='room'?'Room':'Venue'}</span><span>${esc(d.label)}</span></div>`
    }`;

  ttEl.style.display = 'block';
  moveTT(e);
};

window.__calHideTT = function() { ttEl.style.display = 'none'; };

function moveTT(e) {
  const PAD = 16;
  let x = e.clientX + PAD;
  let y = e.clientY + PAD;
  if (x + 300 > window.innerWidth)  x = e.clientX - 300 - PAD;
  if (y + 260 > window.innerHeight) y = e.clientY - 260 - PAD;
  ttEl.style.left = x + 'px';
  ttEl.style.top  = y + 'px';
}
document.addEventListener('mousemove', e => {
  if (ttEl.style.display === 'block') moveTT(e);
});

// ══════════════════════════════════════════════
//  HEADER LABEL
// ══════════════════════════════════════════════

function updateHeaderLabel() {
  const lbl = document.getElementById('calHeaderLabel');
  if (!lbl) return;
  if (calView === 'month') {
    lbl.textContent = `${MONTH_NAMES[viewDate.getMonth()]} ${viewDate.getFullYear()}`;
  } else {
    const ws = getWeekStart(viewDate);
    const we = addDays(ws, 6);
    if (ws.getMonth() === we.getMonth()) {
      lbl.textContent = `${MONTH_NAMES[ws.getMonth()]} ${ws.getDate()}–${we.getDate()}, ${ws.getFullYear()}`;
    } else {
      lbl.textContent = `${MONTH_NAMES[ws.getMonth()]} ${ws.getDate()} – ${MONTH_NAMES[we.getMonth()]} ${we.getDate()}, ${ws.getFullYear()}`;
    }
  }
}

// ══════════════════════════════════════════════
//  MAIN RENDER
// ══════════════════════════════════════════════

function render() {
  updateHeaderLabel();
  const events = groupEvents(getFiltered());
  if (calView === 'month') renderMonthView(events);
  else                     renderWeekView(events);
}

// ══════════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════════

function navigate(dir) {
  expandedWeeks.clear(); // collapse all when navigating
  if (calView === 'month') {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + dir, 1);
  } else {
    viewDate = addDays(viewDate, dir * 7);
  }
  render();
}

// ══════════════════════════════════════════════
//  STAT CARD UPDATERS  (preserved contract)
// ══════════════════════════════════════════════

function updateStats(stats) {
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('totalReservationsValue', stats.totalReservations   ?? 0);
  set('occupancyRateValue',     `${Number(stats.occupancyRate ?? 0).toFixed(1)}%`);
  set('totalRevenueValue',      `₱${Number(stats.totalRevenue ?? 0).toLocaleString()}`);
  set('activeGuestsValue',      stats.activeGuests        ?? 0);
  set('checkOutsTodayValue',    stats.checkOutsTodayCount ?? 0);
}

function changeHtml(val, label) {
  const v = Number(val ?? 0);
  const badge = v > 0 ? `<span class="chg-positive">↑ ${v}%</span>`
              : v < 0 ? `<span class="chg-negative">↓ ${Math.abs(v)}%</span>`
                      : `<span class="chg-neutral">—</span>`;
  return `${badge} <span class="chg-label">${label}</span>`;
}

function updateChanges(changes) {
  if (!changes) return;
  const lbl = changes.lastMonthLabel || 'last month';
  const map = {
    changeTotalReservations : [changes.totalReservations, `vs ${lbl}`],
    changeOccupancyRate     : [changes.occupancyRate,     'vs prev 30 days'],
    changeRevenue           : [changes.revenue,           `vs ${lbl}`],
    changeActiveGuests      : [changes.activeGuests,      `vs ${lbl}`],
    changeCheckOuts         : [changes.checkOutsToday,    `vs ${lbl}`],
  };
  Object.entries(map).forEach(([id, [v, l]]) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = changeHtml(v, l);
  });
}

// ══════════════════════════════════════════════
//  LIVE FETCH  (10 s polling)
// ══════════════════════════════════════════════

async function fetchReservations() {
  try {
    const res = await fetch(window.calendarDataRoute, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    reservationData = data.reservations || [];
    updateStats(data.stats    || {});
    updateChanges(data.changes || window.statChanges || {});
    render();
  } catch (err) {
    console.warn('Calendar: refresh failed —', err);
  }
}

// ══════════════════════════════════════════════
//  INIT
// ══════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

  // ── View toggle (Month / Week) ──
  document.querySelectorAll('.ac-view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.ac-view-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      calView = this.dataset.view;
      expandedWeeks.clear();
      render();
    });
  });

  // ── Display mode toggle (Detailed / Stacked) ──
  document.querySelectorAll('.ac-mode-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.ac-mode-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      dispMode = this.dataset.mode;
      render();
    });
  });

  // ── Navigation ──
  document.getElementById('calPrev')
    ?.addEventListener('click', () => navigate(-1));
  document.getElementById('calNext')
    ?.addEventListener('click', () => navigate(1));
  document.getElementById('calToday')
    ?.addEventListener('click', () => {
      expandedWeeks.clear();
      viewDate = new Date(TODAY);
      render();
    });

  // ── Status filter ──
  document.getElementById('calStatusFilter')
    ?.addEventListener('change', function() {
      statusFilter = this.value;
      expandedWeeks.clear();
      render();
    });

  // ── Click-to-expand delegation (month view day cells) ──
  //    Clicks that land on a chip navigate as normal (link).
  //    Clicks anywhere else on a week row toggle expansion.
  document.getElementById('calGrid')
    ?.addEventListener('click', (e) => {
      // Let chip clicks pass through (they're <a> tags)
      if (e.target.closest('.ac-chip, .ac-week-chip')) return;

      // Only handle month-view week rows
      const weekRow = e.target.closest('.ac-week-row[data-week-start]');
      if (!weekRow) return;

      e.preventDefault();

      // Determine which day column was clicked
      const rect   = weekRow.getBoundingClientRect();
      const col    = Math.max(0, Math.min(6, Math.floor((e.clientX - rect.left) / rect.width * 7)));
      const wStart = parseDate(weekRow.dataset.weekStart);
      const clicked = addDays(wStart, col);

      handleDayClick(clicked);
    });

  // ── Initial render ──
  render();

  // ── Live poll ──
  fetchReservations();
  setInterval(fetchReservations, 10000);
});

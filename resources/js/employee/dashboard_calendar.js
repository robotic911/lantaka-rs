console.log('dashboard_calendar.js is connected');
import dayjs from 'dayjs';

let view = dayjs();
let reservationData = window.reservations || [];
console.log(reservationData);
const monthHeader = document.getElementById('calendar-month-header');
const weekHeader = document.getElementById('calendar-week-header');

const displayDayContainerMonth = document.querySelector('.days-container-month');
const displayDayContainerWeek = document.querySelector('.days-container-week');

const navMonth = document.querySelector('.calendar-nav-month');
const nextMonth = document.querySelector('.next-month');
const prevMonth = document.querySelector('.prev-month');

const navWeek = document.querySelector('.calendar-nav-week');
const nextWeek = document.querySelector('.next-week');
const prevWeek = document.querySelector('.prev-week');

const calendarMonthRender = document.querySelector('.calendar-grid-month');
const calendarWeekRender = document.querySelector('.calendar-grid-week');

const btnMonthly = document.getElementById('btnMonthly');
const btnWeekly = document.getElementById('btnWeekly');
const refresh = document.getElementById('refresh');

function getRedirectAndStatus(res) {
  let redirect = '';
  let status = '';

  if (res.status === 'pending') {
    status = 'pending';
    redirect = window.reservationPage;
  } else if (res.status === 'confirmed') {
    status = 'confirmed';
    redirect = window.reservationPage;
  } else if (res.status === 'completed') {
    status = 'completed';
    redirect = window.guestPage;
  } else if (res.status === 'checked-in') {
    status = 'checked-in';
    redirect = window.guestPage;
  } else if (res.status === 'checked-out') {
    status = 'checked-out';
    redirect = window.guestPage;
  }

  return { status, redirect };
}

function paintReservationsMonth(data) {
  document.querySelectorAll('.date-cell-month').forEach(cell => {
    const date = cell.dataset.iso;
    const container = cell.querySelector('.event-label-month-container');
    container.innerHTML = '';

    data.forEach(res => {
      const { status, redirect } = getRedirectAndStatus(res);
      const label = res.label || 'N/A';
      if (date >= res.check_in && date <= res.check_out && status) {
        container.innerHTML += `
          <a href="${redirect}/${encodeURIComponent(res.id)}?type=${encodeURIComponent(res.type)}" class="event-label ${status}">
            ${label} - ${res.user ? res.user.name : 'Unknown'}
          </a>
        `;
      }
    })
  });
}

function paintReservationsWeek(data) {
  document.querySelectorAll('.date-cell-week').forEach(cell => {
    const date = cell.dataset.iso;
    const container = cell.querySelector('.event-label-week-container');
    container.innerHTML = '';

    data.forEach(res => {
      const { status, redirect } = getRedirectAndStatus(res);
      const label = res.label || 'N/A';

      if (date >= res.check_in && date <= res.check_out && status) {
        container.innerHTML += `
          <a href="${redirect}?search=${encodeURIComponent(res.id)}" class="event-label ${status}">
            ${label} - ${res.user ? res.user.name : 'Unknown'}
          </a>
        `;
      }
    });
  });
}

function updateMonth() {
  monthHeader.textContent = view.format('MMMM YYYY');
}

function updateWeekHeader(weekStart) {
  const weekEnd = weekStart.add(6, 'day');

  const sameMonth = weekStart.isSame(weekEnd, 'month');
  const sameYear = weekStart.isSame(weekEnd, 'year');

  if (sameMonth && sameYear) {
    weekHeader.textContent = `${weekStart.format('MMMM D')} – ${weekEnd.format('D, YYYY')}`;
  } else if (!sameMonth && sameYear) {
    weekHeader.textContent = `${weekStart.format('MMMM D')} – ${weekEnd.format('MMMM D, YYYY')}`;
  } else {
    weekHeader.textContent = `${weekStart.format('MMMM D, YYYY')} – ${weekEnd.format('MMMM D, YYYY')}`;
  }
}

function renderMonth() {
  let daysRender = '';
  updateMonth();

  const firstDayMonth = view.startOf('month');
  const startDay = firstDayMonth.day();
  const displayStartday = firstDayMonth.subtract(startDay, 'day');

  for (let i = 0; i < 35; i++) {
    const displayDay = displayStartday.add(i, 'day');
    const dayAnotherMonth = displayDay.month() !== view.month();
    const dateOfToday = displayDay.isSame(dayjs(), 'day');

    let dateStatus = '';

    if (dayAnotherMonth) {
      dateStatus = 'empty';
    } else if (dateOfToday) {
      dateStatus = 'event';
    }

    daysRender += `
      <div class="date-cell-month ${dateStatus}" data-iso="${displayDay.format('YYYY-MM-DD')}">
        <span class="day-number">${displayDay.format('D')}</span>
        <div class="event-label-month-container"></div>
      </div>
    `;
  }

  displayDayContainerMonth.innerHTML = daysRender;
  paintReservationsMonth(reservationData);
}

function renderWeek() {
  let daysRender = '';
  const weekStart = view.startOf('week');

  updateWeekHeader(weekStart);

  for (let i = 0; i < 7; i++) {
    const displayDay = weekStart.add(i, 'day');
    const dayAnotherMonth = displayDay.month() !== view.month();
    const dateOfToday = displayDay.isSame(dayjs(), 'day');

    let dateStatus = '';

    if (dayAnotherMonth) {
      dateStatus = 'empty';
    } else if (dateOfToday) {
      dateStatus = 'event';
    }

    daysRender += `
      <div class="date-cell-week ${dateStatus}" data-iso="${displayDay.format('YYYY-MM-DD')}">
        <span class="day-number">${displayDay.format('D')}</span>
        <div class="event-label-week-container"></div>
      </div>
    `;
  }

  displayDayContainerWeek.innerHTML = daysRender;
  paintReservationsWeek(reservationData);
}

function updateStats(stats) {
  const totalReservationsEl = document.getElementById('totalReservationsValue');
  const occupancyRateEl = document.getElementById('occupancyRateValue');
  const totalRevenueEl = document.getElementById('totalRevenueValue');
  const activeGuestsEl = document.getElementById('activeGuestsValue');
  const checkOutsTodayEl = document.getElementById('checkOutsTodayValue');

  if (totalReservationsEl) totalReservationsEl.textContent = stats.totalReservations ?? 0;
  if (occupancyRateEl) occupancyRateEl.textContent = `${Number(stats.occupancyRate ?? 0).toFixed(1)}%`;
  if (totalRevenueEl) totalRevenueEl.textContent = `₱${Number(stats.totalRevenue ?? 0).toLocaleString()}`;
  if (activeGuestsEl) activeGuestsEl.textContent = stats.activeGuests ?? 0;
  if (checkOutsTodayEl) checkOutsTodayEl.textContent = stats.checkOutsTodayCount ?? 0;
}

function changeHtml(val, labelText) {
  const v = Number(val ?? 0);
  let badge = '';
  if (v > 0)      badge = `<span class="chg-positive">↑ ${v}%</span>`;
  else if (v < 0) badge = `<span class="chg-negative">↓ ${Math.abs(v)}%</span>`;
  else            badge = `<span class="chg-neutral">—</span>`;
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

refresh.addEventListener('click', () => {
  btnWeekly.classList.remove('active');
  btnMonthly.classList.add('active');

  navMonth.classList.remove('hidden');
  navWeek.classList.add('hidden');

  calendarWeekRender.classList.add('hide');
  calendarMonthRender.classList.remove('hide');

  view = dayjs();
  renderMonth();
});

renderMonth();

nextMonth.addEventListener('click', () => {
  view = view.add(1, 'month');
  renderMonth();
});

prevMonth.addEventListener('click', () => {
  view = view.subtract(1, 'month');
  renderMonth();
});

nextWeek.addEventListener('click', () => {
  view = view.add(1, 'week');
  renderWeek();
});

prevWeek.addEventListener('click', () => {
  view = view.subtract(1, 'week');
  renderWeek();
});

btnWeekly.addEventListener('click', () => {
  btnMonthly.classList.remove('active');
  btnWeekly.classList.add('active');

  navWeek.classList.remove('hidden');
  navMonth.classList.add('hidden');

  calendarMonthRender.classList.add('hide');
  calendarWeekRender.classList.remove('hide');

  renderWeek();
});

btnMonthly.addEventListener('click', () => {
  btnWeekly.classList.remove('active');
  btnMonthly.classList.add('active');

  navMonth.classList.remove('hidden');
  navWeek.classList.add('hidden');

  calendarWeekRender.classList.add('hide');
  calendarMonthRender.classList.remove('hide');

  renderMonth();
});

document.addEventListener('DOMContentLoaded', () => {
  async function fetchReservations() {
    try {
      const res = await fetch(window.calendarDataRoute, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!res.ok) {
        throw new Error(`HTTP error! Status: ${res.status}`);
      }

      const data = await res.json();

      reservationData = data.reservations || [];
      updateStats(data.stats || {});
      updateChanges(data.changes || window.statChanges || {});

      const weekVisible = !document.querySelector('.calendar-grid-week')?.classList.contains('hide');

      if (weekVisible) {
        renderWeek();
      } else {
        renderMonth();
      }
    } catch (err) {
      console.warn('Failed to fetch calendar data:', err);
    }
  }

  fetchReservations();
  setInterval(fetchReservations, 10000);
});
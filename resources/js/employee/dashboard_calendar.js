console.log('dashboard_calendar.js is connected')
import dayjs from 'dayjs'

let view = dayjs()

function paintReservationsMonth() {
  const reservations = window.reservations || [];
  console.log(reservations)
  document.querySelectorAll('.date-cell-month').forEach(cell => {

    const date = cell.dataset.iso;
    const container = cell.querySelector('.event-label-month-container');
    container.innerHTML = '';
    reservations.forEach(res => {
      var status = ""
      var redirect = ""

      // Safe check for Room OR Venue
      var label = res.room ? res.room.room_number : (res.venue ? res.venue.name : 'N/A');
      if (res.status === "pending") {
        status = res.status
        redirect = window.reservationPage
      } else if (res.status === "confirmed") {
        status = res.status
        redirect = window.reservationPage
      } else if (res.status === "completed") {
        status = res.status
        redirect = window.guestPage
      } else if (res.status === "checked-in") {
        status = res.status
        redirect = window.guestPage
      } else if (res.status === "checked-out") {
        status = res.status
        redirect = window.guestPage
      } else {
        status = ""
      }

      if (date >= res.check_in && date <= res.check_out) {
        if (status !== "") {
          container.innerHTML += `
              <a href="${redirect}?search=${encodeURIComponent(label)}" class="event-label ${status}">
                  ${label} - ${res.user ? res.user.name : 'Unknown'}
              </a>
          `;
        }
      }
    });
  });
}

function paintReservationsWeek() {
  const reservations = window.reservations || [];
  console.log("reservations:" + reservations)
  document.querySelectorAll('.date-cell-week').forEach(cell => {

    const date = cell.dataset.iso;
    const container = cell.querySelector('.event-label-week-container');
    container.innerHTML = '';

    reservations.forEach(res => {
      var status = ""
      var redirect = ""

      var label = "";
      if (res.room) {
        label = res.room.room_number;
      } else if (res.venue) {
        label = res.venue.name;
      } else {
        label = "N/A";
      }
      if (res.status === "pending") {
        status = res.status
        redirect = window.reservationPage
      } else if (res.status === "confirmed") {
        status = res.status
        redirect = window.guestPage
      } else if (res.status === "completed") {
        status = res.status
      } else if (res.status === "checked-in") {
        status = res.status
        redirect = window.guestPage
      } else if (res.status === "checked-out") {
        status = res.status
        redirect = window.guestPage
      } else {
        status = ""
      }
      console.log(status)
      if (date >= res.check_in && date <= res.check_out) {
        if (status !== "") {
          container.innerHTML += `
           <a href="${redirect}?search=${encodeURIComponent(`${res.id}`)}" class="event-label ${status}">
            ${label} - ${res.user ? res.user.name : 'Unknown'}
        </a>
        `;
        }
      }
    });
  });
}


const calendar = document.querySelector('.calendar')
const monthHeader = document.getElementById('calendar-month-header')
const weekHeader = document.getElementById('calendar-week-header')

const displayDayContainerMonth = document.querySelector('.days-container-month')
const displayDayContainerWeek = document.querySelector('.days-container-week')

const navMonth = document.querySelector('.calendar-nav-month')
const nextMonth = document.querySelector('.next-month')
const prevMonth = document.querySelector('.prev-month')

const navWeek = document.querySelector('.calendar-nav-week')
const nextWeek = document.querySelector('.next-week')
const prevWeek = document.querySelector('.prev-week')

const calendarMonthRender = document.querySelector('.calendar-grid-month')
const calendarWeekRender = document.querySelector('.calendar-grid-week')

const btnMonthly = document.getElementById('btnMonthly')
const btnWeekly = document.getElementById('btnWeekly')
const refresh = document.getElementById('refresh')

refresh.addEventListener('click', () => {
  btnWeekly.classList.remove('active')
  btnMonthly.classList.add('active')

  navMonth.classList.remove('hidden')
  navWeek.classList.add('hidden')

  calendarWeekRender.classList.add('hide')
  calendarMonthRender.classList.remove('hide')

  view = dayjs()
  renderMonth()
})

function updateMonth() {
  monthHeader.textContent = view.format('MMMM YYYY')
}

function updateWeekHeader(weekStart) {
  const weekEnd = weekStart.add(6, 'day')

  const sameMonth = weekStart.isSame(weekEnd, 'month')
  const sameYear = weekStart.isSame(weekEnd, 'year')

  if (sameMonth && sameYear) {
    weekHeader.textContent = `${weekStart.format('MMMM D')} – ${weekEnd.format('D, YYYY')}`
  } else if (!sameMonth && sameYear) {
    weekHeader.textContent = `${weekStart.format('MMMM D')} – ${weekEnd.format('MMMM D, YYYY')}`
  } else {
    weekHeader.textContent = `${weekStart.format('MMMM D, YYYY')} – ${weekEnd.format('MMMM D, YYYY')}`
  }
}


function renderMonth() {
  let daysRender = ''
  updateMonth()
  const firstDayMonth = view.startOf('month')

  const startDay = firstDayMonth.day()
  const displayStartday = firstDayMonth.subtract(startDay, 'day')
  const currentDate = view.format('day')

  for (let i = 0; i < 35; i++) {
    const displayDay = displayStartday.add(i, 'day')
    const dayAnotherMonth = displayDay.month() !== view.month()
    const dateOfToday = displayDay.isSame(dayjs(), 'day')

    let dateStatus = ``

    if (dayAnotherMonth) {
      dateStatus = 'empty'
    } else if (dateOfToday && !dayAnotherMonth) {
      dateStatus += 'event'
    } else {
      dateStatus = ''
    }

    daysRender +=
      `<div class="date-cell-month ${dateStatus}" data-iso="${displayDay.format('YYYY-MM-DD')}">
        <span class="day-number" >${displayDay.format('D')}</span>
        <div class="event-label-month-container">
          
        </div>
    </div>`

  }
  displayDayContainerMonth.innerHTML = daysRender
  paintReservationsMonth();
}

function renderWeek() {
  let daysRender = ''

  const weekStart = view.startOf('week')

  updateWeekHeader(weekStart)

  for (let i = 0; i < 7; i++) {
    const displayDay = weekStart.add(i, 'day')

    const dayAnotherMonth = displayDay.month() !== view.month()
    const dateOfToday = displayDay.isSame(dayjs(), 'day')

    let dateStatus = ''
    if (dayAnotherMonth) {
      dateStatus = 'empty'
    } else if (dateOfToday && !dayAnotherMonth) {
      dateStatus = 'event'
    } else {
      dateStatus = ''
    }

    daysRender += `
      <div class="date-cell-week ${dateStatus}" data-iso="${displayDay.format('YYYY-MM-DD')}">
        <span class="day-number">${displayDay.format('D')}</span>
          <div class="event-label-week-container">
          </div>
      </div>
    `
  }

  displayDayContainerWeek.innerHTML = daysRender
  paintReservationsWeek();

}

renderMonth()

nextMonth.addEventListener('click', () => {
  view = view.add(1, 'month')
  renderMonth()
  console.log('next')
})

prevMonth.addEventListener('click', () => {
  view = view.subtract(1, 'month')
  renderMonth()
  console.log('prev')
})

nextWeek.addEventListener('click', () => {
  view = view.add(1, 'week')
  renderWeek()
  console.log('next')
})

prevWeek.addEventListener('click', () => {
  view = view.subtract(1, 'week')
  renderWeek()
  console.log('prev')
})

btnWeekly.addEventListener('click', () => {
  btnMonthly.classList.remove('active')
  btnWeekly.classList.add('active')

  navWeek.classList.remove('hidden')
  navMonth.classList.add('hidden')

  calendarMonthRender.classList.add('hide')
  calendarWeekRender.classList.remove('hide')

  renderWeek()
  console.log('week format')
})

btnMonthly.addEventListener('click', () => {
  btnWeekly.classList.remove('active')
  btnMonthly.classList.add('active')

  navMonth.classList.remove('hidden')
  navWeek.classList.add('hidden')

  calendarWeekRender.classList.add('hide')
  calendarMonthRender.classList.remove('hide')

  renderMonth()
  console.log('month format')
})




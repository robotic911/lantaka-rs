console.log('dashboard_calendar.js is connected')
import dayjs from 'dayjs'

let view = dayjs()

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

refresh.addEventListener('click', ()=>{
  btnWeekly.classList.remove('active')
  btnMonthly.classList.add('active')

  navMonth.classList.remove('hidden')
  navWeek.classList.add('hidden')
  
  calendarWeekRender.classList.add('hide')
  calendarMonthRender.classList.remove('hide')

  view = dayjs()
  renderMonth()
})

function updateMonth(){
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


function renderMonth(){
  let daysRender = ''
    updateMonth()
  const firstDayMonth = view.startOf('month')

  const startDay = firstDayMonth.day()
  const displayStartday = firstDayMonth.subtract(startDay,'day')
  const currentDate = view.format('day')
  
  for(let i = 0; i < 35; i++){
    const displayDay = displayStartday.add(i,'day')
    const dayAnotherMonth = displayDay.month() !== view.month()
    const dateOfToday = displayDay.isSame(dayjs(), 'day')

    let dateStatus = ``

    if(dayAnotherMonth) {
      dateStatus = 'empty'
      }else if(dateOfToday && !dayAnotherMonth){
        dateStatus += 'event'
      }else{
        dateStatus = ''
      }
    
    daysRender += 
    `<div class="date-cell-month ${dateStatus}" data-iso="${displayDay.format('DD MM YYYY')}">
        <span class="day-number" >${displayDay.format('D')}</span>
        <div class="event-label-month-container">
          <a href="#" class="event-label confirmed">
            Hall A - CMO
          </a>
          <a href="#" class="event-label pending">
            Room 202 - Kenneth Patino
          </a> 
        </div>
    </div>`
    
  }
  displayDayContainerMonth.innerHTML= daysRender
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
    if (dayAnotherMonth){
      dateStatus = 'empty'
    }else if (dateOfToday && !dayAnotherMonth){
      dateStatus = 'event' 
    }else{
      dateStatus = ''
    }

    daysRender += `
      <div class="date-cell-week ${dateStatus}" data-iso="${displayDay.format('YYYY-MM-DD')}">
        <span class="day-number">${displayDay.format('D')}</span>

        <div class="event-label-month-container">
          <a href="#" class="event-label confirmed">Hall A - CMO</a>
          <a href="#" class="event-label pending">Room 202 - Kenneth Patino</a>
        </div>
      </div>
    `
  }

  displayDayContainerWeek.innerHTML = daysRender
}

renderMonth()

nextMonth.addEventListener('click', ()=>{
  view = view.add(1,'month')
  renderMonth()
  console.log('next')
})

prevMonth.addEventListener('click', ()=>{
  view = view.subtract(1,'month')
  renderMonth()
  console.log('prev')
}) 

nextWeek.addEventListener('click', ()=>{
  view = view.add(1,'week')
  renderWeek()
  console.log('next')
})

prevWeek.addEventListener('click', ()=>{
  view = view.subtract(1,'week')
  renderWeek()
  console.log('prev')
}) 

btnWeekly.addEventListener('click', ()=>{
  btnMonthly.classList.remove('active')
  btnWeekly.classList.add('active')

  navWeek.classList.remove('hidden')
  navMonth.classList.add('hidden')

  calendarMonthRender.classList.add('hide')
  calendarWeekRender.classList.remove('hide')

  renderWeek()
  console.log('week format')
})

btnMonthly.addEventListener('click', ()=>{
  btnWeekly.classList.remove('active')
  btnMonthly.classList.add('active')

  navMonth.classList.remove('hidden')
  navWeek.classList.add('hidden')
  
  calendarWeekRender.classList.add('hide')
  calendarMonthRender.classList.remove('hide')

  renderMonth()
  console.log('month format')
})


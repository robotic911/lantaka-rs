import dayjs from 'dayjs'

const calendarDays = document.querySelector('.calendar-days')
const header = document.querySelector('.month')
const nextMonth = document.getElementById('nextMonth')
const prevMonth = document.getElementById('prevMonth')
const checkinDisplay = document.getElementById('checkinDisplay')
const checkoutDisplay = document.getElementById('checkoutDisplay')
const checkinInput = document.getElementById('checkinDate')
const checkoutInput = document.getElementById('checkoutDate')

// ✅ add an error element in your HTML: <p id="dateError" class="date-error"></p>
const dateError = document.getElementById('dateError')

let monthNow = dayjs().month()

// RANGE STATE (ISO strings)
let rangeStart = null // "YYYY-MM-DD"
let rangeEnd = null   // "YYYY-MM-DD"

// OCCUPIED DATES (array of ISO date strings)
let occupiedDates = window.serverOccupiedDates || [];

// CURRENT RESERVATION DATES — dates that belong to the reservation being edited.
// These are selectable (not blocked) and shown in blue.
let currentReservationDates = window.serverCurrentReservationDates || [];

// Pre-fill dates if returning from cart edit
const _urlParams = new URLSearchParams(window.location.search)
const _preCheckIn  = _urlParams.get('check_in')
const _preCheckOut = _urlParams.get('check_out')
if (_preCheckIn)  rangeStart = _preCheckIn
if (_preCheckOut) rangeEnd   = _preCheckOut
if (_preCheckIn)  monthNow   = dayjs(_preCheckIn).month()

// --- helpers ---
const iso = (d) => d.format('YYYY-MM-DD')

// Monday-first index: Mo=0 ... Su=6
const mondayIndex = (d) => (d.day() + 6) % 7

const isInRange = (dateStr) => {
  if (!rangeStart || !rangeEnd) return false
  return dateStr >= rangeStart && dateStr <= rangeEnd
}

const isCurrentReservation = (dateStr) => currentReservationDates.includes(dateStr)
const isOccupied = (dateStr) => occupiedDates.includes(dateStr) && !isCurrentReservation(dateStr)

// ✅ error helpers
function showError(msg) {
  if (!dateError) return
  dateError.textContent = msg
  dateError.style.display = 'block'
}

function clearError() {
  if (!dateError) return
  dateError.textContent = ''
  dateError.style.display = 'none'
}

// ✅ checks if ANY occupied date exists within [start, end] inclusive
function rangeHasOccupied(startIso, endIso) {
  if (!startIso || !endIso) return false

  let cur = dayjs(startIso)
  const end = dayjs(endIso)

  while (cur.isBefore(end, 'day') || cur.isSame(end, 'day')) {
    const d = cur.format('YYYY-MM-DD')
    if (occupiedDates.includes(d)) return true
    cur = cur.add(1, 'day')
  }
  return false
}

// ✅ get all dates within [start, end] inclusive (for logging / DB checks)
function getDatesBetweenInclusive(startIso, endIso) {
  if (!startIso || !endIso) return []
  const dates = []
  let cur = dayjs(startIso)
  const end = dayjs(endIso)

  while (cur.isBefore(end, 'day') || cur.isSame(end, 'day')) {
    dates.push(cur.format('YYYY-MM-DD'))
    cur = cur.add(1, 'day')
  }
  return dates
}

// ✅ put console.logs INSIDE this function
function updateDateDisplay() {
  if (rangeStart) {
    const startFormatted = dayjs(rangeStart).format('MMM DD, YYYY')
    checkinDisplay.textContent = startFormatted
    if (checkinInput) checkinInput.value = rangeStart
  } else {
    checkinDisplay.textContent = '-'
    if (checkinInput) checkinInput.value = ''
  }

  if (rangeEnd) {
    const endFormatted = dayjs(rangeEnd).format('MMM DD, YYYY')
    checkoutDisplay.textContent = endFormatted
    if (checkoutInput) checkoutInput.value = rangeEnd
  } else {
    checkoutDisplay.textContent = '-'
    if (checkoutInput) checkoutInput.value = ''
  }

  // ✅ logs will show every time you click (because you call updateDateDisplay() on click)
  console.log('CHECK-IN (rangeStart):', rangeStart)
  console.log('CHECK-OUT (rangeEnd):', rangeEnd)

  // logs only when complete range
  if (rangeStart && rangeEnd) {
    const rangeOfDates = getDatesBetweenInclusive(rangeStart, rangeEnd)
    console.log('DATES IN RANGE (incl):' + rangeOfDates)

  }
}

function calendarRender() {
  const view = dayjs().month(monthNow).startOf('month')
  header.textContent = view.format('MMMM YYYY')

  // Start the 35-cell grid on the Monday of the week containing the 1st
  const gridStart = view.subtract(mondayIndex(view), 'day')

  // 🔥 NEW: Get today's date format for comparison
  const todayStr = dayjs().format('YYYY-MM-DD')

  let html = ''

  for (let i = 0; i < 42; i++) {
    const cellDate = gridStart.add(i, 'day')
    const dateStr = iso(cellDate)
    const dayNum = cellDate.date()

    // mark days outside the current viewed month
    const otherMonth = cellDate.month() !== view.month()

    let cls = 'day'

    if (otherMonth) cls += ' other-month'
    if (isOccupied(dateStr)) cls += ' occupied'

    // 🔥 NEW: Check if the date string is before today's date string
    if (dateStr < todayStr) cls += ' past-date'

    if (dateStr === rangeStart) cls += ' range-start'
    if (dateStr === rangeEnd) cls += ' range-end'
    if (isInRange(dateStr)) cls += ' in-range'

    // Show light-blue "current reservation" indicator only when the date is NOT
    // already covered by the user's newly selected range (range colours take priority)
    const coveredByRange = dateStr === rangeStart || dateStr === rangeEnd || isInRange(dateStr)
    if (isCurrentReservation(dateStr) && !coveredByRange) cls += ' current-reservation'

    html += `<div class="${cls}" data-date="${dateStr}">${dayNum}</div>`
  }

  calendarDays.innerHTML = html
}

// Click to pick range
calendarDays.addEventListener('click', (e) => {
  const cell = e.target.closest('.day')

  // Ignore clicks on other-month, truly occupied, or past dates.
  // current-reservation dates ARE allowed (they belong to the reservation being edited).
  if (!cell || cell.classList.contains('other-month') || cell.classList.contains('occupied') || cell.classList.contains('past-date')) return

  clearError()

  const clicked = cell.dataset.date

  // 1st click OR already had a complete range => start new range
  if (!rangeStart || (rangeStart && rangeEnd)) {
    rangeStart = clicked
    rangeEnd = null
    calendarRender()
    updateDateDisplay()
    return
  }

  // 2nd click: set end (swap if needed)
  if (clicked < rangeStart) {
    rangeEnd = rangeStart
    rangeStart = clicked
  } else {
    rangeEnd = clicked
  }

  // ✅ error if range contains any occupied date
  if (rangeHasOccupied(rangeStart, rangeEnd)) {
    showError('Selected range includes occupied dates. Please choose another range.')
    rangeStart = null
    rangeEnd = null
    calendarRender()
    updateDateDisplay() // will log cleared state too
    return
  }

  calendarRender()
  updateDateDisplay() // ✅ logs happen here after every click
})

// Navigate months
nextMonth.addEventListener('click', () => {
  monthNow++
  calendarRender()
})

prevMonth.addEventListener('click', () => {
  monthNow--
  calendarRender()
})

calendarRender()
updateDateDisplay()

/* dito naka store ang check in and check out 
  checkin: rangeStart
  checkout: rangeEnd
  rangeOfDates: getDatesBetweenInclusive(rangeStart, rangeEnd)
*/
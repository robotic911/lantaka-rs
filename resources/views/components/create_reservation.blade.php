@vite('resources/js/employee/create_reservation.js')
  <link rel="stylesheet" href="{{ asset('css/create_reservation.css') }}">

  <div class="cr-modal-overlay"> 
  <div class="cr-modal-content">
    <button class="cr-close-btn">&times;</button>
    <h1 class="cr-title">Create Reservation</h1>

    <form class="cr-form">
      <div class="cr-form-section">
        <label>Account Search</label>
        <input type="text" placeholder="Search for Existing Account" class="cr-full-width">
      </div>

      <div class="cr-button-group">
        <button type="button" class="cr-btn cr-btn-cancel">CANCEL</button>
        <button type="submit" class="cr-btn cr-btn-proceed">PROCEED</button>
      </div>
    </form>
  </div>
</div>

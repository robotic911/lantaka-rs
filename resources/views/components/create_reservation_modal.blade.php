@vite('resources/js/employee/search_accounts.js')
<link rel="stylesheet" href="{{ asset('css/create_reservation.css') }}">

<div class="cr-modal-overlay">
  <div class="cr-modal-content">
    <button class="cr-close-btn" type="button">&times;</button>

    <h1 class="cr-title"></h1>

    <form class="cr-form" id="reservationForm" action="{{ route('showAssignedAccomodation') }}" method="GET">

      <div class="cr-form-section">
        <label>Account Search</label>

        <input 
          type="text" 
          id="account_search"
          placeholder="Search for Existing Account"
          class="cr-full-width"
          autocomplete="off"
        >

        <!-- ERROR MESSAGE -->

        <!-- SEARCH RESULTS -->
        <div id="account_results" class="cr-search-results"></div>

        <!-- SELECTED ACCOUNT DISPLAY -->
        <div id="selected_account_box" class="cr-selected-account" style="display:none;">
            Selected Client: <strong id="selected_account_name"></strong>
        </div>
        <p id="account_error" class="cr-error-message"></p>


      </div>

      <!-- accommodation -->
      <input type="hidden" name="category" id="type_search">
      <input type="hidden" name="id" id="id_search">

      <!-- selected client -->
      <input type="hidden" name="user_id" id="selected_user_id">

      <div class="cr-button-group">
        <button type="button" class="cr-btn cr-btn-cancel">CANCEL</button>
        <button type="submit" class="cr-btn cr-btn-proceed">Proceed</button>
      </div>

    </form>
  </div>
</div>
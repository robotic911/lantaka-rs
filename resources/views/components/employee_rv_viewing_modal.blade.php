<link rel="stylesheet" href="{{ asset('css/employee_rv_viewing_modal.css') }}">

<!-- Modal Overlay -->
<div class="rv-modal-overlay" id="rvModalOverlay"></div>

<!-- Edit Room / Venue Modal -->
<div class="rv-modal" id="rvEditModal">
  <div class="rv-modal-content">

    <div class="rv-modal-header">
      <h2>Edit Room / Venue Details</h2>
      <button class="rv-close-btn" id="rvCloseModal" type="button">&times;</button>
    </div>

    <form class="rv-modal-form" id="rvUpdateForm" action="{{ route('room_venue.update') }}" method="POST">
      @csrf
      @method('PUT')

      <input type="hidden" name="category" id="rv_category_input" value="Room">
      <input type="hidden" name="id" id="rv_item_id">

      <!-- Room FORM -->
      <div class="form-grid-rv" id="room-form-rv">
        <div class="form-column">
          
          <div class="form-group">
            <label>Room Name (Number)</label>
            <input type="text" name="name" placeholder="e.g. 101" class="form-input">
          </div>

          <div class="form-group">
            <label>Room Type</label>
            <input type="text" name="type" placeholder="e.g. Deluxe" class="form-input">
          </div>

          <div class="form-group">
            <label>Internal Pricing</label>
            <input type="number" name="internal_price" placeholder="₱ 0" class="form-input">
            
            <label>External Pricing</label>
            <input type="number" name="external_price" placeholder="₱ 0" class="form-input">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-input">
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
              </select>
            </div>

            <div class="form-group">
              <label>Capacity</label>
              <input type="number" name="capacity" placeholder="Enter Capacity" class="form-input">
            </div>
          </div>
        </div>

        <div class="form-column">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Room Description" class="form-textarea"></textarea>
          </div>
        </div>
      </div>

      <!-- VENUE FORM -->
      <div class="form-grid-rv" id="venue-form-rv">
        <div class="form-column">
          
          <div class="form-group">
            <label>Venue Name</label>
            <input type="text" name="name" placeholder="e.g. Grand Hall" class="form-input">
          </div>

          <div class="form-group">
            <label>Internal Pricing</label>
            <input type="number" name="internal_price" placeholder="₱ 0" class="form-input">
            
            <label>External Pricing</label>
            <input type="number" name="external_price" placeholder="₱ 0" class="form-input">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-input">
                <option value="Available">Available</option>
                <option value="Unavailable">Unavailable</option>
              </select>
            </div>

            <div class="form-group">
              <label>Capacity</label>
              <input type="number" name="capacity" placeholder="Enter Capacity" class="form-input">
            </div>
          </div>
        </div>

        <div class="form-column">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Venue Description" class="form-textarea"></textarea>
          </div>
        </div>
      </div>

      <!-- buttons -->
      <div class="rv-form-actions">
        <button type="button" class="rv-btn rv-btn-primary" id="rvCreateReservation">
          Create Reservation
        </button>

        <button type="button" class="rv-btn rv-btn-secondary" id="rvCancelBtn">
          CANCEL
        </button>

        <button type="submit" class="rv-btn rv-btn-success">
          SAVE
        </button>
      </div>
    </form>
  
  </div>
</div>  
@vite('resources/js/employee/add_room_venue.js')

<div class="modal-backdrop">
  <div class="modal">
    <button class="modal-close">&times;</button>

    <h2 class="modal-title">Create <span id="title-category">Room</span></h2>

    <div class="modal-tabs">
      <button type="button" class="tab-btn tab-active" id="room-option" onclick="setCategory('Room')">Room</button>
      <button type="button" class="tab-btn" id="venue-option" onclick="setCategory('Venue')">Venue</button>
    </div>

    <div class="modal-content">
      <form action="{{ route('room_venue.store') }}" method="POST">
        @csrf 
        
        <input type="hidden" name="category" id="category_input" value="Room">

        <div class="form-grid active" id="room-form">
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
        
        <div class="form-grid" id="venue-form">
           <div class="form-column">
            
            <div class="form-group">
              <label>Venue Name</label>
              <input type="text" name="name" placeholder="e.g. Grand Hall" class="form-input" >
            </div>

            <div class="form-group">
                <label>Internal Pricing</label>
                <input type="number" name="internal_price" placeholder="₱ 0" class="form-input" >
                
                <label>External Pricing</label>
                <input type="number" name="external_price" placeholder="₱ 0" class="form-input" >
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-input" >
                  <option value="Available">Available</option>
                  <option value="Unavailable">Unavailable</option>
                </select>
              </div>
              <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" placeholder="Enter Capacity" class="form-input" >
              </div>
            </div>
          </div>

          <div class="form-column">
             <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Venue Description" class="form-textarea" ></textarea>
              </div>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="close-modal">CANCEL</button>
          <button type="submit" class="btn-success">SAVE</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function setCategory(cat) {
    document.getElementById('category_input').value = cat;
    document.getElementById('title-category').innerText = cat;

    const roomForm = document.getElementById('room-form');
    const venueForm = document.getElementById('venue-form');
    
    // Select all inputs inside both forms
    const roomInputs = roomForm.querySelectorAll('input, select, textarea');
    const venueInputs = venueForm.querySelectorAll('input, select, textarea');

    if(cat === 'Room') {
        roomForm.style.display = 'flex';
        venueForm.style.display = 'none';
        roomInputs.forEach(el => el.disabled = false);
        venueInputs.forEach(el => el.disabled = true);
        document.getElementById('room-option').classList.add('tab-active');
        document.getElementById('venue-option').classList.remove('tab-active');
    } else {
        roomForm.style.display = 'none';
        venueForm.style.display = 'flex';
        venueInputs.forEach(el => el.disabled = false);
        roomInputs.forEach(el => el.disabled = true);
        document.getElementById('venue-option').classList.add('tab-active');
        document.getElementById('room-option').classList.remove('tab-active');
    }
  }
</script>
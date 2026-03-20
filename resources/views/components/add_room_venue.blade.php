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
      <form action="{{ route('room_venue.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="category" id="category_input" value="Room">

        {{-- ROOM FORM --}}
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

            <div class="form-group">
              <label>Photo <span class="img-label-hint">(optional · JPG/PNG/WebP · max 5MB)</span></label>
              <div class="img-upload-zone" id="roomImgZone" onclick="document.getElementById('roomImgInput').click()">
                <input type="file" name="image" id="roomImgInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                       onchange="rvPreviewImage(this,'roomImgThumb','roomImgPreview','roomImgPlaceholder')">
                <div class="img-preview-wrap" id="roomImgPreview" style="display:none">
                  <img id="roomImgThumb" class="img-thumb">
                  <button type="button" class="img-clear-btn"
                          onclick="event.stopPropagation();rvClearImage('roomImgInput','roomImgThumb','roomImgPreview','roomImgPlaceholder')">✕</button>
                </div>
                <div class="img-placeholder" id="roomImgPlaceholder">
                  <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path d="M12 16V8m0 0-3 3m3-3 3 3" stroke="#aaa" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="18" height="18" rx="4" stroke="#ddd" stroke-width="1.4"/></svg>
                  <span>Click to upload photo</span>
                  <span class="img-hint">Auto-resized &amp; compressed on save</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- VENUE FORM --}}
        <div class="form-grid" id="venue-form">
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

            <div class="form-group">
              <label>Photo <span class="img-label-hint">(optional · JPG/PNG/WebP · max 5MB)</span></label>
              <div class="img-upload-zone" id="venueImgZone" onclick="document.getElementById('venueImgInput').click()">
                <input type="file" name="image" id="venueImgInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                       onchange="rvPreviewImage(this,'venueImgThumb','venueImgPreview','venueImgPlaceholder')">
                <div class="img-preview-wrap" id="venueImgPreview" style="display:none">
                  <img id="venueImgThumb" class="img-thumb">
                  <button type="button" class="img-clear-btn"
                          onclick="event.stopPropagation();rvClearImage('venueImgInput','venueImgThumb','venueImgPreview','venueImgPlaceholder')">✕</button>
                </div>
                <div class="img-placeholder" id="venueImgPlaceholder">
                  <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path d="M12 16V8m0 0-3 3m3-3 3 3" stroke="#aaa" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="18" height="18" rx="4" stroke="#ddd" stroke-width="1.4"/></svg>
                  <span>Click to upload photo</span>
                  <span class="img-hint">Auto-resized &amp; compressed on save</span>
                </div>
              </div>
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

<style>
.img-label-hint { font-weight: 400; color: #888; font-size: 12px; }

.img-upload-zone {
  border: 1.5px dashed #d1d5db;
  border-radius: 10px;
  cursor: pointer;
  overflow: hidden;
  transition: border-color .2s, background .2s;
  min-height: 108px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
  position: relative;
}
.img-upload-zone:hover { border-color: #1e3a5f; background: #f5f8ff; }

.img-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 16px;
  color: #aaa;
  font-size: 13px;
  text-align: center;
  pointer-events: none;
}
.img-hint { font-size: 11px; color: #ccc; margin-top: 2px; }

.img-preview-wrap { width: 100%; position: relative; }
.img-thumb { width: 100%; max-height: 160px; object-fit: cover; display: block; border-radius: 8px; }

.img-clear-btn {
  position: absolute; top: 6px; right: 6px;
  width: 24px; height: 24px; border-radius: 50%;
  border: none; background: rgba(0,0,0,.55);
  color: #fff; font-size: 12px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.img-clear-btn:hover { background: rgba(180,0,0,.8); }
</style>

<script>
  function setCategory(cat) {
    document.getElementById('category_input').value = cat;
    document.getElementById('title-category').innerText = cat;

    const roomForm    = document.getElementById('room-form');
    const venueForm   = document.getElementById('venue-form');
    const roomInputs  = roomForm.querySelectorAll('input, select, textarea');
    const venueInputs = venueForm.querySelectorAll('input, select, textarea');

    if (cat === 'Room') {
      roomForm.style.display  = 'flex';
      venueForm.style.display = 'none';
      roomInputs.forEach(el  => el.disabled = false);
      venueInputs.forEach(el => el.disabled = true);
      document.getElementById('room-option').classList.add('tab-active');
      document.getElementById('venue-option').classList.remove('tab-active');
    } else {
      roomForm.style.display  = 'none';
      venueForm.style.display = 'flex';
      venueInputs.forEach(el => el.disabled = false);
      roomInputs.forEach(el  => el.disabled = true);
      document.getElementById('venue-option').classList.add('tab-active');
      document.getElementById('room-option').classList.remove('tab-active');
    }
  }

  function rvPreviewImage(input, thumbId, previewId, placeholderId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById(thumbId).src = e.target.result;
      document.getElementById(previewId).style.display    = 'block';
      document.getElementById(placeholderId).style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  function rvClearImage(inputId, thumbId, previewId, placeholderId) {
    document.getElementById(inputId).value = '';
    document.getElementById(thumbId).src   = '';
    document.getElementById(previewId).style.display    = 'none';
    document.getElementById(placeholderId).style.display = 'flex';
  }
</script>

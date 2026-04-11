<link rel="stylesheet" href="{{ asset('css/employee_rv_viewing_modal.css') }}">

<!-- Modal Overlay -->
<div class="rv-modal-overlay" id="rvModalOverlay"></div>

<!-- Edit Room / Venue Modal -->
<div class="rv-modal" id="rvEditModal">
  <div class="rv-modal-content">

    <div class="rv-modal-header">
      <div class="rv-modal-header-left">
        <h2>@if(auth()->user()->Account_Role === 'admin') Edit Room / Venue Details @else Room / Venue Details @endif</h2>
        <span class="rv-status-badge" id="rvStatusBadge"></span>
      </div>
      <button class="rv-close-btn" id="rvCloseModal" type="button">&times;</button>
    </div>

    @php $isAdmin = auth()->user()->Account_Role === 'admin'; @endphp
    <form class="rv-modal-form" id="rvUpdateForm" action="{{ route('room_venue.update') }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <input type="hidden" name="category" id="rv_category_input" value="Room">
      <input type="hidden" name="id" id="rv_item_id">

      <!-- Room FORM -->
      <div class="form-grid-rv" id="room-form-rv">
        <div class="form-column">

          <div class="form-group">
            <label>Room Name (Number)</label>
            <input type="text" name="name" placeholder="e.g. 101" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
          </div>

          <div class="form-group">
            <label>Room Type</label>
            <input type="text" name="type" placeholder="e.g. Deluxe" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
          </div>

          <div class="form-group">
            <label>Internal Pricing</label>
            <input type="number" name="internal_price" placeholder="₱ 0" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>

            <label>External Pricing</label>
            <input type="number" name="external_price" placeholder="₱ 0" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              @if($isAdmin)
                <select name="status" id="rvRoomStatusSelect" class="form-input">
                  <option value="Available">Available</option>
                  <option value="UnderMaintenance">Under Maintenance</option>
                </select>
              @else
                {{-- Staff cannot change status; submit current value as hidden --}}
                <input type="hidden" name="status" id="rvRoomStatusHidden" value="Available">
                <div class="rv-status-readonly" id="rvRoomStatusDisplay">Available</div>
              @endif
            </div>

            <div class="form-group">
              <label>Capacity</label>
              <input type="number" name="capacity" placeholder="Enter Capacity" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
            </div>
          </div>
        </div>

        <div class="form-column">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Room Description" class="form-textarea" {{ $isAdmin ? '' : 'readonly' }}></textarea>
          </div>

          {{-- Image section --}}
          <div class="form-group">
            <label>Photo</label>
            <div class="rv-img-current" id="rvRoomImgCurrent">
              <img id="rvRoomImgPreviewThumb" class="rv-img-thumb" src="" alt="" style="display:none">
              <span class="rv-img-none" id="rvRoomImgNone">No photo uploaded</span>
            </div>
            @if($isAdmin)
              <div class="rv-img-upload-zone" id="rvRoomImgZone" onclick="document.getElementById('rvRoomImgInput').click()">
                <input type="file" name="image" id="rvRoomImgInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                       onchange="rvEditPreview(this,'rvRoomImgPreviewThumb','rvRoomImgNone','rvRoomImgNewBadge')">
                <span class="rv-img-upload-label" id="rvRoomImgNewBadge">Replace photo</span>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- VENUE FORM -->
      <div class="form-grid-rv" id="venue-form-rv">
        <div class="form-column">

          <div class="form-group">
            <label>Venue Name</label>
            <input type="text" name="name" placeholder="e.g. Grand Hall" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
          </div>

          <div class="form-group">
            <label>Internal Pricing</label>
            <input type="number" name="internal_price" placeholder="₱ 0" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>

            <label>External Pricing</label>
            <input type="number" name="external_price" placeholder="₱ 0" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
          </div>

          <div class="form-row">

              @if($isAdmin)
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" id="rvVenueStatusSelect" class="form-input">
                    <option value="Available">Available</option>
                    <option value="UnderMaintenance">Under Maintenance</option>
                  </select>
                </div>
              @else
                {{-- Staff cannot change status; submit current value as hidden --}}
                <input type="hidden" name="status" id="rvVenueStatusHidden" value="Available">
              @endif

            <div class="form-group">
              <label>Capacity</label>
              <input type="number" name="capacity" placeholder="Enter Capacity" class="form-input" {{ $isAdmin ? '' : 'readonly' }}>
            </div>
          </div>
        </div>

        <div class="form-column">
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Venue Description" class="form-textarea" {{ $isAdmin ? '' : 'readonly' }}></textarea>
          </div>

          {{-- Image section --}}
          <div class="form-group">
            <label>Photo</label>
            <div class="rv-img-current" id="rvVenueImgCurrent">
              <img id="rvVenueImgPreviewThumb" class="rv-img-thumb" src="" alt="" style="display:none">
              <span class="rv-img-none" id="rvVenueImgNone">No photo uploaded</span>
            </div>
            @if($isAdmin)
              <div class="rv-img-upload-zone" id="rvVenueImgZone" onclick="document.getElementById('rvVenueImgInput').click()">
                <input type="file" name="image" id="rvVenueImgInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                       onchange="rvEditPreview(this,'rvVenueImgPreviewThumb','rvVenueImgNone','rvVenueImgNewBadge')">
                <span class="rv-img-upload-label" id="rvVenueImgNewBadge">📷 Replace photo</span>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- buttons -->
      <div class="rv-form-actions">
        <button type="button" class="rv-btn rv-btn-primary" id="rvCreateReservation">
          Create Reservation
        </button>


        @if($isAdmin)
        <button type="button" class="rv-btn rv-btn-secondary" id="rvCancelBtn">
          CANCEL
        </button>

        <button type="submit" class="rv-btn rv-btn-success">
          SAVE
        </button>
        @endif
      </div>
    </form>

  </div>
</div>

<style>
/* ── Modal header layout ── */
.rv-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.rv-modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}



.rv-img-current {
  border-radius: 8px;
  overflow: hidden;
  background: #f5f5f5;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
}
.rv-img-thumb {
  width: 100%;
  max-height: 150px;
  object-fit: cover;
  display: block;
  border-radius: 8px;
}
.rv-img-none {
  font-size: 12px;
  color: #bbb;
  padding: 14px;
  font-style: italic;
}
.rv-img-upload-zone {
  border: 1.5px dashed #d1d5db;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
  transition: border-color .2s, background .2s;
}
.rv-img-upload-zone:hover { border-color: #1e3a5f; background: #f0f4ff; }
.rv-img-upload-label { font-size: 12px; color: #888; pointer-events: none; }

/* Read-only status display for non-admin staff */
.rv-status-readonly {
  padding: 8px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
  color: #6b7280;
  font-size: 14px;
  cursor: default;
}
</style>

<script>
  function rvEditPreview(input, thumbId, noneId, badgeId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const thumb = document.getElementById(thumbId);
      const none  = document.getElementById(noneId);
      const badge = document.getElementById(badgeId);
      thumb.src           = e.target.result;
      thumb.style.display = 'block';
      if (none)  none.style.display  = 'none';
      if (badge) badge.textContent   = '✓ New photo selected';
    };
    reader.readAsDataURL(file);
  }
</script>

document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("rvModalOverlay");
  const modal = document.getElementById("rvEditModal");
  const updateForm = document.getElementById("rvUpdateForm");

  const roomCards = document.querySelectorAll(".room-card");
  const venueCards = document.querySelectorAll(".venue-card");

  const roomForm = document.getElementById("room-form-rv");
  const venueForm = document.getElementById("venue-form-rv");

  const categoryInput = document.getElementById("rv_category_input");
  const itemId = document.getElementById("rv_item_id");

  const closeBtn = document.getElementById("rvCloseModal");
  const cancelBtn = document.getElementById("rvCancelBtn");
  const createReservationBtn = document.getElementById("rvCreateReservation");

  if (!overlay || !modal || !updateForm || !roomForm || !venueForm || !categoryInput || !itemId) {
    console.log("Missing update modal elements");
    return;
  }

  function openModal() {
    overlay.classList.add("active");
    modal.classList.add("active");
  }

  function closeModal(revertCard = true) {
    overlay.classList.remove("active");
    modal.classList.remove("active");
    // Revert card colour if the admin cancelled without saving
    if (revertCard && activeCard) {
      const details = activeCard.querySelector('.room-details, .venue-details');
      if (details) {
        const originalStatus = details.dataset.effective_status || 'available';
        activeCard.classList.remove(...ALL_STATUS_CLASSES);
        activeCard.classList.add(originalStatus);
      }
    }
    activeCard = null;
  }

  function enableRoomForm() {
    const roomInputs = roomForm.querySelectorAll("input, select, textarea");
    const venueInputs = venueForm.querySelectorAll("input, select, textarea");

    roomForm.style.display = "grid";
    venueForm.style.display = "none";

    roomInputs.forEach(input => input.disabled = false);
    venueInputs.forEach(input => input.disabled = true);

    categoryInput.value = "Room";
  }

  function enableVenueForm() {
    const roomInputs = roomForm.querySelectorAll("input, select, textarea");
    const venueInputs = venueForm.querySelectorAll("input, select, textarea");

    venueForm.style.display = "grid";
    roomForm.style.display = "none";

    venueInputs.forEach(input => input.disabled = false);
    roomInputs.forEach(input => input.disabled = true);

    categoryInput.value = "Venue";
  }

  function clearRoomForm() {
    roomForm.querySelector('input[name="name"]').value = "";
    roomForm.querySelector('input[name="type"]').value = "";
    roomForm.querySelector('input[name="capacity"]').value = "";
    roomForm.querySelector('input[name="internal_price"]').value = "";
    roomForm.querySelector('input[name="external_price"]').value = "";
    roomForm.querySelector('textarea[name="description"]').value = "";
    setModalImage('room', '');
  }

  function clearVenueForm() {
    venueForm.querySelector('input[name="name"]').value = "";
    venueForm.querySelector('input[name="capacity"]').value = "";
    venueForm.querySelector('input[name="internal_price"]').value = "";
    venueForm.querySelector('input[name="external_price"]').value = "";
    venueForm.querySelector('textarea[name="description"]').value = "";
    setModalImage('venue', '');
  }

  // Status label map for the badge
  const STATUS_LABELS = {
    available:        'Available',
    occupied:         'Occupied',
    reserved:         'Reserved',
    undermaintenance: 'Under Maintenance',
  };

  // Map the DB-stored select value to the CSS key
  const DB_STATUS_TO_KEY = {
    'Available':        'available',
    'Occupied':         'occupied',
    'Reserved':         'reserved',
    'UnderMaintenance': 'undermaintenance',
  };

  const ALL_STATUS_CLASSES = ['available', 'occupied', 'reserved', 'undermaintenance'];

  // Currently active card (room or venue element)
  let activeCard = null;

  function setStatusBadge(effectiveStatus) {
    const badge = document.getElementById('rvStatusBadge');
    if (!badge) return;
    badge.classList.remove(...ALL_STATUS_CLASSES);
    const key = (effectiveStatus || 'available').toLowerCase().replace(/[\s_]/g, '');
    badge.classList.add(key);
    badge.textContent = STATUS_LABELS[key] || effectiveStatus;
  }

  // Update the card color AND badge live when the admin changes the dropdown
  function applyCardStatusPreview(selectEl) {
    if (!activeCard) return;
    const key = DB_STATUS_TO_KEY[selectEl.value] || 'available';
    activeCard.classList.remove(...ALL_STATUS_CLASSES);
    activeCard.classList.add(key);
    setStatusBadge(key);
  }

  // Placeholder paths for rooms and venues (static public assets, always available)
  const PLACEHOLDER = {
    room:  '/images/placeholder_room.svg',
    venue: '/images/placeholder_venue.svg',
  };

  function setModalImage(type, src) {
    const thumb  = document.getElementById(type === 'room' ? 'rvRoomImgPreviewThumb' : 'rvVenueImgPreviewThumb');
    const none   = document.getElementById(type === 'room' ? 'rvRoomImgNone'         : 'rvVenueImgNone');
    const badge  = document.getElementById(type === 'room' ? 'rvRoomImgNewBadge'     : 'rvVenueImgNewBadge');
    const input  = document.getElementById(type === 'room' ? 'rvRoomImgInput'        : 'rvVenueImgInput');

    if (input) input.value = '';   // clear any previous file selection

    const displaySrc = src || PLACEHOLDER[type] || '';
    if (thumb) { thumb.src = displaySrc; thumb.style.display = 'block'; }
    if (none)  none.style.display = 'none';
    if (badge) badge.textContent = src ? '📷 Replace photo' : '📷 Add photo';
  }

  // Wire up live status preview on the room status select
  const roomStatusSelect = roomForm.querySelector('select[name="status"]');
  if (roomStatusSelect) {
    roomStatusSelect.addEventListener('change', () => applyCardStatusPreview(roomStatusSelect));
  }

  // Wire up live status preview on the venue status select
  const venueStatusSelect = venueForm.querySelector('select[name="status"]');
  if (venueStatusSelect) {
    venueStatusSelect.addEventListener('change', () => applyCardStatusPreview(venueStatusSelect));
  }

  roomCards.forEach(card => {
    card.addEventListener("click", () => {
      const details = card.querySelector(".room-details");
      if (!details) return;

      const data = details.dataset;

      activeCard = card;
      clearRoomForm();
      clearVenueForm();
      enableRoomForm();

      itemId.value = data.id || "";
      roomForm.querySelector('input[name="name"]').value = data.name || "";
      roomForm.querySelector('input[name="type"]').value = data.type || "";
      roomForm.querySelector('input[name="capacity"]').value = data.capacity || "";
      roomForm.querySelector('input[name="internal_price"]').value = data.price || "";
      roomForm.querySelector('input[name="external_price"]').value = data.external_price || "";
      if (roomStatusSelect) roomStatusSelect.value = data.status || "Available";
      roomForm.querySelector('textarea[name="description"]').value = data.description || "";
      setModalImage('room', data.image || '');
      setStatusBadge(data.effective_status || 'available');

      openModal();
    });
  });

  venueCards.forEach(card => {
    card.addEventListener("click", () => {
      const details = card.querySelector(".venue-details");
      if (!details) return;

      const data = details.dataset;

      activeCard = card;
      clearRoomForm();
      clearVenueForm();
      enableVenueForm();

      itemId.value = data.id || "";

      venueForm.querySelector('input[name="name"]').value = data.name || "";
      venueForm.querySelector('input[name="capacity"]').value = data.capacity || "";
      venueForm.querySelector('input[name="internal_price"]').value = data.price || "";
      venueForm.querySelector('input[name="external_price"]').value = data.external_price || "";
      if (venueStatusSelect) venueStatusSelect.value = data.status || "Available";
      venueForm.querySelector('textarea[name="description"]').value = data.description || "";
      setModalImage('venue', data.image || '');
      setStatusBadge(data.effective_status || 'available');

      openModal();
    });
  });

  closeBtn?.addEventListener("click", closeModal);
  cancelBtn?.addEventListener("click", closeModal);

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      closeModal();
    }
  });

  // Form submit (Save) — don't revert the card; page will reload with the saved status
  updateForm?.addEventListener("submit", () => {
    activeCard = null;
  });

  createReservationBtn?.addEventListener("click", () => {
    closeModal(false);
  });

  // safe default
  enableRoomForm();
  roomForm.style.display = "none";
  venueForm.style.display = "none";
});
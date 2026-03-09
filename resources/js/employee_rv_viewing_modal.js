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

  function closeModal() {
    overlay.classList.remove("active");
    modal.classList.remove("active");
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
    roomForm.querySelector('select[name="status"]').value = "Available";
  }

  function clearVenueForm() {
    venueForm.querySelector('input[name="name"]').value = "";
    venueForm.querySelector('input[name="capacity"]').value = "";
    venueForm.querySelector('input[name="internal_price"]').value = "";
    venueForm.querySelector('input[name="external_price"]').value = "";
    venueForm.querySelector('textarea[name="description"]').value = "";
    venueForm.querySelector('select[name="status"]').value = "Available";
  }

  roomCards.forEach(card => {
    card.addEventListener("click", () => {
      const details = card.querySelector(".room-details");
      if (!details) return;

      const data = details.dataset;

      clearRoomForm();
      clearVenueForm();
      enableRoomForm();

      itemId.value = data.id || "";

      roomForm.querySelector('input[name="name"]').value = data.name || "";
      roomForm.querySelector('input[name="type"]').value = data.type || "";
      roomForm.querySelector('input[name="capacity"]').value = data.capacity || "";
      roomForm.querySelector('input[name="internal_price"]').value = data.price || "";
      roomForm.querySelector('input[name="external_price"]').value = data.external_price || "";
      roomForm.querySelector('textarea[name="description"]').value = data.description || "";
      roomForm.querySelector('select[name="status"]').value = data.status || "Available";

      openModal();
    });
  });

  venueCards.forEach(card => {
    card.addEventListener("click", () => {
      const details = card.querySelector(".venue-details");
      if (!details) return;

      const data = details.dataset;

      clearRoomForm();
      clearVenueForm();
      enableVenueForm();

      itemId.value = data.id || "";

      venueForm.querySelector('input[name="name"]').value = data.name || "";
      venueForm.querySelector('input[name="capacity"]').value = data.capacity || "";
      venueForm.querySelector('input[name="internal_price"]').value = data.price || "";
      venueForm.querySelector('input[name="external_price"]').value = data.external_price || "";
      venueForm.querySelector('textarea[name="description"]').value = data.description || "";
      venueForm.querySelector('select[name="status"]').value = data.status || "Available";

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

  createReservationBtn?.addEventListener("click", () => {
    closeModal();
  });

  // safe default
  enableRoomForm();
  roomForm.style.display = "none";
  venueForm.style.display = "none";
});
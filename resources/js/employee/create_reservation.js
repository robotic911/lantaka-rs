document.addEventListener("DOMContentLoaded", () => {
  const crOverlay = document.querySelector(".cr-modal-overlay");
  const crCloseBtn = document.querySelector(".cr-close-btn");
  const crCancelBtn = document.querySelector(".cr-btn-cancel");
  const openBtn = document.getElementById("rvCreateReservation");
  const crTitle = document.querySelector(".cr-title");

  if (!crOverlay) return;

  function openModal() {
    const getId = document.getElementById("rv_item_id");
    const getType = document.getElementById("rv_category_input");

    const idInput = document.getElementById("id_search");
    const typeInput = document.getElementById("type_search");

    crOverlay.classList.add("show");

    if (idInput) idInput.value = getId.value;
    if (typeInput) typeInput.value = getType.value;

    if (crTitle) {
      crTitle.textContent = "Create Reservation";
    }
  }

  function closeModal() {
    crOverlay.classList.remove("show");
  }

  openBtn?.addEventListener("click", openModal);
  crCloseBtn?.addEventListener("click", closeModal);
  crCancelBtn?.addEventListener("click", closeModal);

  crOverlay.addEventListener("click", (e) => {
    if (e.target === crOverlay) {
      closeModal();
    }
  });
  
});
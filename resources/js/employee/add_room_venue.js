document.addEventListener('DOMContentLoaded', () => {
  const modal = document.querySelector('.modal-backdrop');
  const addButton = document.getElementById('add_room_venue_button');
  const cancelButton = document.getElementById('close-modal');
  const closeButton = document.querySelector('.modal-close');

  const roomOption = document.getElementById('room-option');
  const venueOption = document.getElementById('venue-option');
  const roomForm = document.getElementById('room-form');
  const venueForm = document.getElementById('venue-form');

  const categoryInput = document.getElementById('category_input');
  const titleCategory = document.getElementById('title-category');

  if (!modal || !roomForm || !venueForm) return;

  function activate(type) {
    const roomInputs = roomForm.querySelectorAll('input, select, textarea');
    const venueInputs = venueForm.querySelectorAll('input, select, textarea');

    if (type === 'room') {
      roomOption?.classList.add('tab-active');
      venueOption?.classList.remove('tab-active');

      roomForm.style.display = 'grid';
      venueForm.style.display = 'none';

      roomInputs.forEach(input => {
        input.disabled = false;
      });

      venueInputs.forEach(input => {
        input.disabled = true;
      });

      if (categoryInput) categoryInput.value = 'Room';
      if (titleCategory) titleCategory.textContent = 'Room';
    } else {
      venueOption?.classList.add('tab-active');
      roomOption?.classList.remove('tab-active');

      venueForm.style.display = 'grid';
      roomForm.style.display = 'none';

      venueInputs.forEach(input => {
        input.disabled = false;
      });

      roomInputs.forEach(input => {
        input.disabled = true;
      });

      if (categoryInput) categoryInput.value = 'Venue';
      if (titleCategory) titleCategory.textContent = 'Venue';
    }
  }

  function openModal() {
    modal.classList.add('show');
    activate('room');
  }

  function closeModal() {
    modal.classList.remove('show');
  }

  addButton?.addEventListener('click', openModal);
  cancelButton?.addEventListener('click', closeModal);
  closeButton?.addEventListener('click', closeModal);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('show')) {
      closeModal();
    }
  });

  roomOption?.addEventListener('click', () => activate('room'));
  venueOption?.addEventListener('click', () => activate('venue'));

  activate('room');
});
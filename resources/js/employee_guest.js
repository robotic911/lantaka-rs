const statusCards = document.querySelector('.status-cards');

statusCards?.addEventListener('click', (e) => {
  const activeCard = e.target.closest('.status-card');
  if (!activeCard) return;

  const isActive = activeCard.classList.contains('active');

  statusCards.querySelectorAll('.status-card').forEach(c => c.classList.remove('active'));

  if (!isActive) activeCard.classList.add('active');
});

const modal        = document.querySelector('.modal-overlay');
const modalBody    = document.querySelector('.modal-body');
const moreDetailsBtn = document.querySelector('.expand-btn');
const closeBtnModal  = document.querySelector('.close-btn');

closeBtnModal?.addEventListener('click', () => {
  modal?.classList.remove('show');
});

modal?.addEventListener('click', (e) => {
  if (e.target === modal) modal.classList.remove('show');
});

moreDetailsBtn?.addEventListener('click', () => {
  modal?.classList.add('show');
});
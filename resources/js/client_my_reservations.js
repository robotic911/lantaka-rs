document.addEventListener('DOMContentLoaded', () => {

  /* ── References ── */
  const overlay    = document.getElementById('crmOverlay');
  const closeBtn   = document.getElementById('crmClose');
  const expandBtns = document.querySelectorAll('.expand-button');

  /* ── Helpers ── */
  function el(id) { return document.getElementById(id); }

  function statusClass(status) {
    const map = {
      pending: 'pending', confirmed: 'confirmed', cancelled: 'cancelled',
      rejected: 'rejected', 'checked-in': 'checked-in',
      'checked-out': 'checked-out', completed: 'completed',
    };
    return map[status?.toLowerCase()] ?? '';
  }

  function calcDuration(rawIn, rawOut, type) {
    if (!rawIn || !rawOut) return '—';
    const d1   = new Date(rawIn);
    const d2   = new Date(rawOut);
    const diff = Math.round((d2 - d1) / 86400000);
    if (diff <= 0) return '—';
    const unit = type === 'venue'
      ? (diff === 1 ? 'day'   : 'days')
      : (diff === 1 ? 'night' : 'nights');
    return `${diff} ${unit}`;
  }

  function buildFoodHtml(foods) {
    if (!foods || foods.length === 0) {
      return '<p class="crm-empty">No food reserved.</p>';
    }

    const grouped = {};
    foods.forEach(f => {
      const raw  = f.pivot?.Food_Reservation_Serving_Date || f.Food_Reservation_Serving_Date || null;
      const name = f.Food_Name || f.name || 'Unknown item';
      const meal = f.pivot?.Food_Reservation_Meal_time || f.Food_Reservation_Meal_time || null;
      if (!raw) return;

      const dateKey = raw.substring(0, 10); // "YYYY-MM-DD"
      if (!grouped[dateKey]) grouped[dateKey] = [];
      grouped[dateKey].push({ name, meal });
    });

    const dates = Object.keys(grouped).sort();
    if (dates.length === 0) return '<p class="crm-empty">No food reserved.</p>';

    return dates.map(date => {
      const label = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'short', month: 'short', day: 'numeric', year: 'numeric',
      });
      const items = grouped[date].map(item => `
        <div class="crm-food-item">
          <span class="crm-food-dot"></span>
          <span>${item.name}</span>
          ${item.meal ? `<span class="crm-food-meal">${item.meal}</span>` : ''}
        </div>
      `).join('');

      return `
        <div class="crm-food-date-group">
          <p class="crm-food-date-header">${label}</p>
          <div class="crm-food-items">${items}</div>
        </div>
      `;
    }).join('');
  }

  function infoNoteText(status) {
    const notes = {
      'pending':     "Your reservation is awaiting review. We'll notify you once it's confirmed.",
      'confirmed':   'Your reservation is confirmed! Please arrive on time for check-in.',
      'checked-in':  "You're currently checked in. Enjoy your stay!",
      'checked-out': 'Your stay has ended. Thank you for choosing Lantaka!',
      'completed':   'Your stay has ended. Thank you for choosing Lantaka!',
      'cancelled':   'This reservation has been cancelled.',
      'rejected':    'This reservation was not approved. Please contact us for details.',
    };
    return notes[status?.toLowerCase()] ?? '';
  }

  /* ── Open modal ── */
  expandBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-info'));

      /* Header */
      el('crmResId').textContent = `Reservation #${data.display_id}`;
      el('crmTypePill').textContent = data.type === 'room' ? 'Room' : 'Venue';

      const statusEl = el('crmStatusBadge');
      const s = data.status || '';
      statusEl.textContent = s.charAt(0).toUpperCase() + s.slice(1);
      statusEl.className   = 'crm-status-badge ' + statusClass(s);

      /* Details */
      el('crmAccommodation').textContent = data.accommodation || '—';
      el('crmPax').textContent           = data.pax           || '—';
      el('crmCheckIn').textContent       = data.check_in      || '—';
      el('crmCheckOut').textContent      = data.check_out     || '—';
      el('crmDuration').textContent      = calcDuration(data.check_in_raw, data.check_out_raw, data.type);

      /* Total */
      el('crmTotal').textContent = `₱ ${data.total || '0.00'}`;

      /* Payment badge — only meaningful after checkout */
      const afterCheckout = ['checked-out', 'completed'].includes(s.toLowerCase());
      el('crmPaymentRow').style.display = (afterCheckout && data.payment_status) ? '' : 'none';
      if (afterCheckout && data.payment_status) {
        const badge = el('crmPaymentBadge');
        const ps = data.payment_status.toLowerCase();
        badge.textContent = ps.charAt(0).toUpperCase() + ps.slice(1);
        badge.className   = 'crm-payment-badge ' + ps;
      }

      /* Food */
      el('crmFoodList').innerHTML = buildFoodHtml(data.foods);

      /* Info note */
      const note    = infoNoteText(s);
      const noteBox = el('crmInfoNote');
      if (note) {
        el('crmInfoText').textContent = note;
        noteBox.style.display = '';
      } else {
        noteBox.style.display = 'none';
      }

      /* Contact card — show only for pending reservations */
      const cancelSection = el('crmCancelSection');
      if (cancelSection) {
        cancelSection.style.display = s.toLowerCase() === 'pending' ? '' : 'none';
      }

      overlay.classList.add('open');
    });
  });

  /* ── Close modal ── */
  function closeModal() { overlay.classList.remove('open'); }
  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (overlay)   overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  // Cancel is handled by contacting Lantaka — no client-side AJAX needed.
});

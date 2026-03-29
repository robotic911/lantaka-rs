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

      /* ── Cancellation request section ── */
      // Show for pending or confirmed reservations only
      const cancelSection = el('crmCancelSection');
      if (cancelSection) {
        const showCancel = ['pending', 'confirmed'].includes(s.toLowerCase());
        cancelSection.style.display = showCancel ? '' : 'none';

        if (showCancel) {
          const cancelStatus = (data.cancellation_status || '').toLowerCase();
          if (cancelStatus === 'pending') {
            // Already know it's pending from server-rendered data — apply immediately, no AJAX needed
            _cancelResId   = data.real_id;
            _cancelResType = data.type;
            setCancelState('crmCancelPending');
          } else if (cancelStatus === 'rejected') {
            _cancelResId   = data.real_id;
            _cancelResType = data.type;
            setCancelState('crmCancelRejected');
          } else {
            // No cancellation request yet — just show idle
            _cancelResId   = data.real_id;
            _cancelResType = data.type;
            setCancelState('crmCancelIdle');
          }
        }
      }

      overlay.classList.add('open');
    });
  });

  /* ── Cancellation request state machine ── */

  // Current reservation context (set when modal opens)
  let _cancelResId   = null;
  let _cancelResType = null;

  function setCancelState(state, adminNote) {
    // Hide form and rejected panels; idle card is always shown (button state changes)
    const hideIds = ['crmCancelForm', 'crmCancelRejected'];
    hideIds.forEach(id => { const e = el(id); if (e) e.style.display = 'none'; });

    const idleDiv  = el('crmCancelIdle');
    const idleCard = el('crmCancelIdleCard');
    const idleBtn  = el('crmCancelOpenFormBtn');
    const idleTitle = el('crmCancelIdleTitle');
    const idleBody  = el('crmCancelIdleBody');

    // Always keep idle card visible
    if (idleDiv) idleDiv.style.display = '';

    if (state === 'crmCancelPending') {
      // Disable the button, turn card amber, add "waiting" note
      if (idleCard)  idleCard.classList.add('crm-cancel-idle--waiting');
      if (idleTitle) idleTitle.textContent = 'Cancellation Pending';
      if (idleBody)  idleBody.innerHTML =
        '<span class="crm-waiting-pulse"></span> Your request is under review. We\'ll notify you of the outcome soon.';
      if (idleBtn) {
        idleBtn.disabled    = true;
        idleBtn.textContent = '⏳ Waiting for Cancellation';
        idleBtn.classList.add('crm-cancel-waiting');
      }

    } else if (state === 'crmCancelIdle') {
      // Reset to normal idle state
      if (idleCard)  idleCard.classList.remove('crm-cancel-idle--waiting');
      if (idleTitle) idleTitle.textContent = 'Need to cancel?';
      if (idleBody)  idleBody.textContent  = "You can submit a cancellation request and our team will review it shortly.";
      if (idleBtn) {
        idleBtn.disabled    = false;
        idleBtn.textContent = 'Request Cancellation';
        idleBtn.classList.remove('crm-cancel-waiting');
      }

    } else if (state === 'crmCancelForm') {
      const form = el('crmCancelForm');
      if (form) form.style.display = '';

    } else if (state === 'crmCancelRejected') {
      const rejected = el('crmCancelRejected');
      if (rejected) rejected.style.display = '';
      // Reset idle card to normal so they can retry via the card below
      if (idleCard)  idleCard.classList.remove('crm-cancel-idle--waiting');
      if (idleTitle) idleTitle.textContent = 'Need to cancel?';
      if (idleBody)  idleBody.textContent  = "You can submit a cancellation request and our team will review it shortly.";
      if (idleBtn) {
        idleBtn.disabled    = false;
        idleBtn.textContent = 'Request Cancellation';
        idleBtn.classList.remove('crm-cancel-waiting');
      }
      if (adminNote) {
        const noteEl = el('crmCancelRejectedNote');
        if (noteEl) noteEl.textContent = 'Your cancellation request was not approved. ' + adminNote;
      }
    }
  }

  function fetchCancellationState(resId, resType) {
    _cancelResId   = resId;
    _cancelResType = resType;

    // Reset to idle while loading
    setCancelState('crmCancelIdle');

    fetch(`/client/reservations/${resId}/cancellation-status?type=${resType}`, {
      headers: { 'Accept': 'application/json' },
    })
      .then(r => r.json())
      .then(data => {
        const req = data.request;
        if (!req) {
          setCancelState('crmCancelIdle');
        } else if (req.status === 'pending') {
          setCancelState('crmCancelPending');
        } else if (req.status === 'rejected') {
          setCancelState('crmCancelRejected', req.admin_note || '');
        } else {
          // approved — reservation is already cancelled; hide the section
          const sec = el('crmCancelSection');
          if (sec) sec.style.display = 'none';
        }
      })
      .catch(() => setCancelState('crmCancelIdle')); // graceful fallback
  }

  // Open form button
  const openFormBtn = el('crmCancelOpenFormBtn');
  if (openFormBtn) {
    openFormBtn.addEventListener('click', () => {
      const reasonEl = el('crmCancelReason');
      if (reasonEl) reasonEl.value = '';
      const errEl = el('crmCancelError');
      if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
      setCancelState('crmCancelForm');
    });
  }

  // Retry button (after rejection → re-open form)
  const retryBtn = el('crmCancelRetryBtn');
  if (retryBtn) {
    retryBtn.addEventListener('click', () => {
      const reasonEl = el('crmCancelReason');
      if (reasonEl) reasonEl.value = '';
      const errEl = el('crmCancelError');
      if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
      setCancelState('crmCancelForm');
    });
  }

  // Back button
  const backBtn = el('crmCancelBackBtn');
  if (backBtn) {
    backBtn.addEventListener('click', () => setCancelState('crmCancelIdle'));
  }

  // Submit button
  const submitBtn = el('crmCancelSubmitBtn');
  if (submitBtn) {
    submitBtn.addEventListener('click', () => {
      const reason  = (el('crmCancelReason')?.value || '').trim();
      const errEl   = el('crmCancelError');

      if (reason.length < 10) {
        if (errEl) {
          errEl.textContent  = 'Please provide a reason of at least 10 characters.';
          errEl.style.display = '';
        }
        return;
      }
      if (errEl) errEl.style.display = 'none';

      submitBtn.disabled   = true;
      submitBtn.textContent = 'Submitting…';

      // Get CSRF token from the meta tag
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      fetch(`/client/reservations/${_cancelResId}/request-cancellation?type=${_cancelResType}`, {
        method : 'POST',
        headers: {
          'Content-Type' : 'application/json',
          'Accept'       : 'application/json',
          'X-CSRF-TOKEN' : csrf,
        },
        body: JSON.stringify({ reason }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            setCancelState('crmCancelPending');
            // Show toast confirmation
            if (typeof window.showToast === 'function') {
              window.showToast('Cancellation request submitted. We\'ll review it shortly.', 'success');
            }
          } else {
            if (errEl) {
              errEl.textContent   = data.message || 'Something went wrong. Please try again.';
              errEl.style.display = '';
            }
          }
        })
        .catch(() => {
          if (errEl) {
            errEl.textContent   = 'Network error. Please check your connection and try again.';
            errEl.style.display = '';
          }
        })
        .finally(() => {
          submitBtn.disabled    = false;
          submitBtn.textContent = 'Submit Request';
        });
    });
  }

  /* ── Close modal ── */
  function closeModal() { overlay.classList.remove('open'); }
  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (overlay)   overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
});

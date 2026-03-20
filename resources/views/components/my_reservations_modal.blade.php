{{-- Client Reservation Detail Modal --}}
<div class="crm-overlay" id="crmOverlay">
  <div class="crm-modal">

    {{-- ── HEADER ── --}}
    <div class="crm-header">
      <div class="crm-header-meta">
        <span class="crm-res-id" id="crmResId">—</span>
        <span class="crm-type-pill" id="crmTypePill">—</span>
        <span class="crm-status-badge" id="crmStatusBadge">—</span>
      </div>
      <button class="crm-close" id="crmClose" aria-label="Close">&times;</button>
    </div>

    {{-- ── BODY ── --}}
    <div class="crm-body">

      {{-- LEFT: booking details + food --}}
      <div class="crm-left">

        <div class="crm-card">
          <p class="crm-card-title">Booking Details</p>
          <div class="crm-grid">
            <div class="crm-field">
              <span class="crm-label">Room / Venue</span>
              <span class="crm-value" id="crmAccommodation">—</span>
            </div>
            <div class="crm-field">
              <span class="crm-label">No. of Pax</span>
              <span class="crm-value" id="crmPax">—</span>
            </div>
            <div class="crm-field">
              <span class="crm-label">Check-in</span>
              <span class="crm-value" id="crmCheckIn">—</span>
            </div>
            <div class="crm-field">
              <span class="crm-label">Check-out</span>
              <span class="crm-value" id="crmCheckOut">—</span>
            </div>
            <div class="crm-field crm-field--full">
              <span class="crm-label">Duration</span>
              <span class="crm-value" id="crmDuration">—</span>
            </div>
          </div>
        </div>

        <div class="crm-card">
          <p class="crm-card-title">Food Orders</p>
          <div id="crmFoodList" class="crm-food-list">
            <p class="crm-empty">No food reserved.</p>
          </div>
        </div>

      </div>

      {{-- RIGHT: summary + action --}}
      <div class="crm-right">

        <div class="crm-summary">
          <p class="crm-card-title">Summary</p>

          <div class="crm-summary-amount">
            <span class="crm-summary-label">Total Amount</span>
            <span class="crm-summary-value" id="crmTotal">₱ 0.00</span>
          </div>

          <div class="crm-divider"></div>

          <div class="crm-payment-row" id="crmPaymentRow" style="display:none;">
            <span class="crm-summary-label">Payment</span>
            <span class="crm-payment-badge" id="crmPaymentBadge">—</span>
          </div>
        </div>

        <div class="crm-info-note" id="crmInfoNote" style="display:none;">
          <p id="crmInfoText"></p>
        </div>

        {{-- Cancel contact card — only shown for pending reservations --}}
        <div id="crmCancelSection" style="display:none;">
          <div class="crm-contact-card">
            <p class="crm-contact-title">Need to cancel?</p>
            <p class="crm-contact-body">
              Cancellations are handled by our team. Please reach out to us directly and we'll assist you right away.
            </p>
            <a href="mailto:lantaka@adzu.edu.ph" class="crm-contact-link">
              ✉ lantaka@adzu.edu.ph
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
/* ── Overlay ── */
.crm-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.46);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  animation: crmFade .2s ease;
}
.crm-overlay.open { display: flex; }
@keyframes crmFade { from { opacity:0; } to { opacity:1; } }

/* ── Modal shell ── */
.crm-modal {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 820px;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 60px rgba(0,0,0,.22);
  animation: crmSlide .24s ease;
  overflow: hidden;
}
@keyframes crmSlide {
  from { transform: translateY(16px); opacity:0; }
  to   { transform: translateY(0);    opacity:1; }
}

/* ── Header ── */
.crm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #e9eaec;
  flex-shrink: 0;
  gap: 12px;
}
.crm-header-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.crm-res-id {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a8a;
}
.crm-type-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
  background: #eff6ff;
  color: #1d4ed8;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.crm-status-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  text-transform: capitalize;
}
.crm-status-badge.pending     { background:#dbeafe; color:#1e40af; }
.crm-status-badge.confirmed   { background:#d1fae5; color:#065f46; }
.crm-status-badge.checked-in  { background:#065f46; color:#fff; }
.crm-status-badge.checked-out { background:#fef3c7; color:#92400e; }
.crm-status-badge.cancelled   { background:#fee2e2; color:#991b1b; }
.crm-status-badge.rejected    { background:#fee2e2; color:#991b1b; }
.crm-status-badge.completed   { background:#fef3c7; color:#92400e; }

.crm-close {
  background: none;
  border: none;
  font-size: 26px;
  line-height: 1;
  color: #9ca3af;
  cursor: pointer;
  padding: 0 4px;
  transition: color .15s;
  flex-shrink: 0;
}
.crm-close:hover { color: #111; }

/* ── Body layout ── */
.crm-body {
  display: flex;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* Left column */
.crm-left {
  flex: 1;
  overflow-y: auto;
  padding: 20px 18px 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  border-right: 1px solid #f0f1f3;
}
.crm-left::-webkit-scrollbar { width: 4px; }
.crm-left::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

/* Right column */
.crm-right {
  width: 250px;
  flex-shrink: 0;
  padding: 20px 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  overflow-y: auto;
  background: #f8f9fb;
}

/* ── Cards ── */
.crm-card {
  background: #fff;
  border: 1px solid #e9eaec;
  border-radius: 10px;
  padding: 16px 18px;
}
.crm-card-title {
  font-size: 10px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: .9px;
  margin: 0 0 14px;
}

/* Info grid */
.crm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px 24px;
}
.crm-field { display: flex; flex-direction: column; gap: 3px; }
.crm-field--full { grid-column: 1 / -1; }

.crm-label {
  font-size: 10px;
  font-weight: 600;
  color: #b0b7c3;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.crm-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.3;
}

/* ── Food list ── */
.crm-food-list { display: flex; flex-direction: column; gap: 12px; }
.crm-empty { font-size: 13px; color: #9ca3af; margin: 0; }

.crm-food-date-header {
  font-size: 10px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: .6px;
  margin: 0 0 6px;
  padding-bottom: 5px;
  border-bottom: 1px solid #f0f1f3;
}
.crm-food-items { display: flex; flex-direction: column; gap: 4px; }
.crm-food-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #374151;
  padding: 5px 8px;
  background: #f8f9fb;
  border-radius: 6px;
}
.crm-food-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #93c5fd;
  flex-shrink: 0;
}
.crm-food-meal {
  font-size: 10px;
  color: #9ca3af;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .3px;
  margin-left: auto;
}

/* ── Summary box ── */
.crm-summary {
  background: #fff;
  border: 1px solid #e9eaec;
  border-radius: 10px;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.crm-summary-amount { display: flex; flex-direction: column; gap: 4px; }
.crm-summary-label {
  font-size: 10px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.crm-summary-value {
  font-size: 24px;
  font-weight: 800;
  color: #1e3a8a;
}
.crm-divider { height: 1px; background: #f0f1f3; }
.crm-payment-row { display: flex; flex-direction: column; gap: 6px; }
.crm-payment-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  align-self: flex-start;
  text-transform: uppercase;
  letter-spacing: .3px;
}
.crm-payment-badge.paid   { background:#d1fae5; color:#065f46; }
.crm-payment-badge.unpaid { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }

/* ── Info note ── */
.crm-info-note {
  background: #eff6ff;
  border-radius: 8px;
  padding: 12px 14px;
  font-size: 12px;
  color: #1d4ed8;
  line-height: 1.55;
}
.crm-info-note p { margin: 0; }

/* ── Cancel contact card ── */
.crm-contact-card {
  background: #fff8f8;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.crm-contact-title {
  font-size: 12px;
  font-weight: 700;
  color: #991b1b;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.crm-contact-body {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
  line-height: 1.5;
}
.crm-contact-link {
  font-size: 12px;
  font-weight: 600;
  color: #1e3a8a;
  text-decoration: none;
  word-break: break-all;
}
.crm-contact-link:hover { text-decoration: underline; }

/* ── Mobile ── */
@media (max-width: 620px) {
  .crm-body { flex-direction: column; }
  .crm-left { border-right: none; border-bottom: 1px solid #f0f1f3; padding: 16px; }
  .crm-right { width: 100%; padding: 16px; background: #f8f9fb; }
  .crm-modal { max-height: 95vh; border-radius: 12px 12px 0 0; }
}
</style>

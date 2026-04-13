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
            <div class="crm-field crm-field--full" id="crmPurposeRow" style="display:none;">
              <span class="crm-label">Purpose</span>
              <span class="crm-value" id="crmPurpose">—</span>
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

          {{-- Breakdown: always shown, rows toggled per type --}}
          <div id="crmBreakdown" style="display:none;">
            <div class="crm-breakdown-row" id="crmRoomRow" style="display:none;">
              <span class="crm-breakdown-label">🛏 Room</span>
              <span class="crm-breakdown-val" id="crmRoomTotal">₱ 0.00</span>
            </div>
            <div class="crm-breakdown-row" id="crmVenueRow" style="display:none;">
              <span class="crm-breakdown-label">🏛 Venue</span>
              <span class="crm-breakdown-val" id="crmVenueTotal">₱ 0.00</span>
            </div>
            <div class="crm-breakdown-row" id="crmFoodRow" style="display:none;">
              <span class="crm-breakdown-label">🍽 Food</span>
              <span class="crm-breakdown-val" id="crmFoodTotal">₱ 0.00</span>
            </div>
            <div class="crm-breakdown-divider"></div>
          </div>

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

        {{-- Cancellation request section — shown for pending / confirmed reservations --}}
        <div id="crmCancelSection" style="display:none;">

          {{-- Section-level error (e.g. 3-day gate or time cutoff) --}}
          <p id="crmCancelGateError" class="crm-cancel-error" style="display:none; margin-bottom:8px;"></p>

          {{-- STATE: idle / pending — same card, button changes state --}}
          <div id="crmCancelIdle">
            <div class="crm-cancel-idle-card" id="crmCancelIdleCard">
              <p class="crm-cancel-idle-title" id="crmCancelIdleTitle">Need to cancel?</p>
              <p class="crm-cancel-idle-body" id="crmCancelIdleBody">
                You can submit a cancellation request 3 days prior to your check-in date.
              </p>
              <div class="crm-cancel-notice">
                <p class="crm-cancel-notice__text">
                  Cancellation requests are accepted <strong>Monday &ndash; Friday, 8:00 AM &ndash; 4:00 PM</strong> only. Requests submitted outside these hours will be processed on the next business day.
                </p>
              </div>
              <button type="button" id="crmCancelOpenFormBtn" class="crm-cancel-open-btn">
                Request Cancellation
              </button>
            </div>
          </div>

          {{-- STATE: form — textarea + submit --}}
          <div id="crmCancelForm" style="display:none;">
            <div class="crm-cancel-form-card">
              <p class="crm-cancel-form-title">Cancellation Request</p>
              <label class="crm-cancel-label" for="crmCancelReason">
                Please explain why you wish to cancel this reservation:
              </label>
              <textarea id="crmCancelReason" class="crm-cancel-textarea"
                        rows="4" maxlength="1000"
                        placeholder="e.g. Change of plans, schedule conflict…"></textarea>
              <p id="crmCancelError" class="crm-cancel-error" style="display:none;"></p>
              <div class="crm-cancel-form-actions">
                <button type="button" id="crmCancelBackBtn"   class="crm-cancel-back-btn">Back</button>
                <button type="button" id="crmCancelSubmitBtn" class="crm-cancel-submit-btn">Submit Request</button>
              </div>
            </div>
          </div>

          {{-- STATE: rejected (admin rejected the request) --}}
          <div id="crmCancelRejected" style="display:none;">
            <div class="crm-cancel-status-card crm-cancel-status--rejected">
              <div>
                <p class="crm-cancel-status-title">Request Not Approved</p>
                <p class="crm-cancel-status-body" id="crmCancelRejectedNote">
                  Your cancellation request was not approved.
                </p>
                <button type="button" id="crmCancelRetryBtn" class="crm-cancel-open-btn" style="margin-top:8px;">
                  Submit New Request
                </button>
              </div>
            </div>
          </div>

        </div>{{-- /crmCancelSection --}}

        {{-- ── Request for Changes section — shown for pending / confirmed reservations ── --}}
        {{-- Clicking the button redirects to the room/venue viewing page (mirrors checkout Edit flow) --}}
        <div id="crmChangeSection" style="display:none; margin-top:10px;">

          {{-- STATE: idle — redirect button --}}
          <div id="crmChangeIdle">
            <div class="crm-cancel-idle-card crm-change-idle-card">
              <p class="crm-cancel-idle-title crm-change-idle-title">Request for Changes</p>
              <p class="crm-cancel-idle-body">
                Need to reschedule your stay or modify your food orders? Click below — you'll be taken through the same booking flow to update your details.
              </p>
              <button type="button" id="crmChangeOpenFormBtn" class="crm-change-open-btn">
                Submit Request for Changes
              </button>
            </div>
          </div>

          {{-- STATE: pending --}}
          <div id="crmChangePending" style="display:none;">
            <div class="crm-cancel-status-card crm-cancel-status--pending">
              <div>
                <p class="crm-cancel-status-title" style="color:#1e40af;">Request for Changes — Pending Review</p>
                <p class="crm-cancel-status-body">
                  Your request is under review. We'll notify you of the outcome soon.
                </p>
              </div>
            </div>
          </div>

          {{-- STATE: rejected --}}
          <div id="crmChangeRejected" style="display:none;">
            <div class="crm-cancel-status-card crm-cancel-status--rejected">
              <div>
                <p class="crm-cancel-status-title">Request Not Approved</p>
                <p class="crm-cancel-status-body" id="crmChangeRejectedNote">
                  Your request for changes was not approved.
                </p>
                <button type="button" id="crmChangeRetryBtn" class="crm-change-open-btn" style="margin-top:8px;">
                  Submit New Request
                </button>
              </div>
            </div>
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

/* Per-date group: card with header */
.crm-food-date-group {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}
.crm-food-date-header {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(90deg, #1e3a8a, #2c5282);
  padding: 7px 13px;
  margin: 0;
  letter-spacing: .4px;
}
.crm-food-date-inner {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: #fff;
}

/* Set line — amber card */
.crm-food-line--set {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1e3a8a;
  padding: 6px 10px;
  background: #fffbeb;
  border-left: 3px solid #f59e0b;
  border-radius: 0 6px 6px 0;
  margin: 2px 0;
  line-height: 1.4;
}

/* Individual food line — light indent */
.crm-food-line {
  font-size: 12px;
  color: #374151;
  margin: 0;
  line-height: 1.6;
  padding: 2px 4px 2px 10px;
  border-left: 2px solid #e5e7eb;
}

/* Meal separator — small uppercase label */
.crm-food-line--meal {
  font-size: 10px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: .5px;
  padding: 6px 0 2px;
  border-left: none;
  margin-top: 2px;
}

/* Upgrade line — extra viand / dessert from Customize modal */
.crm-food-line--upgrade {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #1d4ed8;
  margin: 2px 0;
  padding: 3px 8px;
  background: #eff6ff;
  border-left: 2px solid #3b82f6;
  border-radius: 0 4px 4px 0;
  line-height: 1.4;
}
.crm-upgrade-tag {
  font-size: 9px;
  font-weight: 700;
  background: #dbeafe;
  color: #1d4ed8;
  padding: 1px 5px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: .4px;
  white-space: nowrap;
  flex-shrink: 0;
}
.crm-upgrade-price {
  margin-left: auto;
  font-size: 11px;
  font-weight: 700;
  color: #2563eb;
  white-space: nowrap;
}

/* ── Legacy individual food row (kept for backward compat) ── */
.crm-food-item {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  color: #374151;
}
.crm-food-cat-pill {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  background: #eff6ff;
  color: #1d4ed8;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: .3px;
  flex-shrink: 0;
}
.crm-food-name { flex: 1; color: #1f2937; }

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
/* Breakdown rows (venue + food above grand total) */
.crm-breakdown-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 4px 0; font-size: 12px;
}
.crm-breakdown-label { color: #6b7280; font-weight: 500; }
.crm-breakdown-val   { color: #1f2937; font-weight: 600; }
.crm-breakdown-divider { height: 1px; background: #e5e7eb; margin: 6px 0 10px; }
/* Purpose row in booking details */
#crmPurposeRow .crm-value { text-transform: capitalize; }
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

/* ── Cancellation request UI ── */

/* Idle card (before form opens) */
.crm-cancel-idle-card {
  background: #fff8f8;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.crm-cancel-idle-title {
  font-size: 12px;
  font-weight: 700;
  color: #991b1b;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.crm-cancel-idle-body {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
  line-height: 1.5;
}
.crm-cancel-notice {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 7px;
  padding: 8px 10px;
}
.crm-cancel-notice__icon {
  font-size: 13px;
  flex-shrink: 0;
  margin-top: 1px;
}
.crm-cancel-notice__text {
  font-size: 11px;
  color: #78350f;
  margin: 0;
  line-height: 1.5;
}
.crm-cancel-open-btn {
  align-self: flex-start;
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 14px;
  cursor: pointer;
  letter-spacing: .3px;
  transition: background .15s, opacity .15s;
}
.crm-cancel-open-btn:hover:not(:disabled) { background: #b91c1c; }

/* Disabled waiting state — keeps red but muted + not-allowed cursor */
.crm-cancel-open-btn:disabled,
.crm-cancel-open-btn.crm-cancel-waiting {
  background: #dc2626;
  opacity: .65;
  cursor: not-allowed;
  pointer-events: none;
}

/* Idle card turns orange-amber border when in waiting state */
.crm-cancel-idle-card.crm-cancel-idle--waiting {
  background: #fff7ed;
  border-color: #f97316;
}
.crm-cancel-idle-card.crm-cancel-idle--waiting .crm-cancel-idle-title {
  color: #9a3412;
}
.crm-cancel-waiting-note {
  font-size: 11px;
  color: #c2410c;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}
.crm-waiting-pulse {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #f97316;
  display: inline-block;
  animation: crmWaitPulse 1.4s ease-in-out infinite;
  flex-shrink: 0;
}
@keyframes crmWaitPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: .4; transform: scale(1.5); }
}

/* Form card */
.crm-cancel-form-card {
  background: #fff;
  border: 1px solid #e9eaec;
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.crm-cancel-form-title {
  font-size: 10px;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: .8px;
  margin: 0;
}
.crm-cancel-label {
  font-size: 12px;
  color: #374151;
  line-height: 1.4;
}
.crm-cancel-textarea {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 12px;
  padding: 8px 10px;
  resize: vertical;
  line-height: 1.5;
  font-family: inherit;
  color: #1f2937;
  transition: border-color .15s;
}
.crm-cancel-textarea:focus {
  outline: none;
  border-color: #3b82f6;
}
.crm-cancel-error {
  font-size: 11px;
  color: #dc2626;
  margin: 0;
}
.crm-cancel-form-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
.crm-cancel-back-btn {
  background: none;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  padding: 6px 14px;
  cursor: pointer;
  transition: background .15s;
}
.crm-cancel-back-btn:hover { background: #f3f4f6; }
.crm-cancel-submit-btn {
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 14px;
  cursor: pointer;
  transition: background .15s;
}
.crm-cancel-submit-btn:hover  { background: #b91c1c; }
.crm-cancel-submit-btn:disabled { background: #fca5a5; cursor: not-allowed; }

/* Status cards (pending / rejected) */
.crm-cancel-status-card {
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
.crm-cancel-status--pending {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}
.crm-cancel-status--rejected {
  background: #fff8f8;
  border: 1px solid #fecaca;
}
.crm-cancel-status-icon {
  font-size: 18px;
  line-height: 1;
  flex-shrink: 0;
}
.crm-cancel-status-title {
  font-size: 12px;
  font-weight: 700;
  margin: 0 0 4px;
}
.crm-cancel-status--pending .crm-cancel-status-title { color: #1e40af; }
.crm-cancel-status--rejected .crm-cancel-status-title { color: #991b1b; }
.crm-cancel-status-body {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
  line-height: 1.5;
}

/* ── Request for Changes UI ── */
.crm-change-idle-card {
  background: #eff6ff;
  border-color: #bfdbfe;
}
.crm-change-idle-title {
  color: #1e40af;
}
.crm-change-open-btn {
  align-self: flex-start;
  background: #1e40af;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 14px;
  cursor: pointer;
  letter-spacing: .3px;
  transition: background .15s, opacity .15s;
}
.crm-change-open-btn:hover:not(:disabled) { background: #1e3a8a; }
.crm-change-open-btn:disabled {
  background: #1e40af;
  opacity: .6;
  cursor: not-allowed;
  pointer-events: none;
}

/* Request-type toggle buttons */
.crm-change-type-row {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-top: 6px;
}
.crm-change-type-btn {
  background: #f3f4f6;
  border: 1.5px solid #d1d5db;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #374151;
  padding: 5px 10px;
  cursor: pointer;
  transition: background .15s, border-color .15s, color .15s;
}
.crm-change-type-btn.active {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #1e40af;
}
.crm-change-type-btn:hover:not(.active) { background: #e5e7eb; }

/* ── Mobile ── */
@media (max-width: 620px) {
  .crm-body { flex-direction: column; }
  .crm-left { border-right: none; border-bottom: 1px solid #f0f1f3; padding: 16px; }
  .crm-right { width: 100%; padding: 16px; background: #f8f9fb; }
  .crm-modal { max-height: 95vh; border-radius: 12px 12px 0 0; }
}
</style>

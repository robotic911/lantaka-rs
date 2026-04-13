<div class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle"></h2>
        <button class="close-btn">&times;</button>
      </div>

      <div class="modal-body modal-body-grid">

        {{-- MOVED ERROR BLOCK HERE --}}
        @if ($errors->any())
        <div style="background-color: #ff4c4c; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; grid-column: 1 / -1;">
          <strong>The form didn't save because:</strong>
          <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <div class="modal-left-column">
          <form id="modificationForm" class="modal-form" method="POST" action="{{ route('employee.updateGuests') }}">

            @csrf
            @method('PUT')

            <div class="meals-container" id="meal-container-left">
              <div style="display:flex; justify-content:start; width:100%; margin:15px 0px; font-size:13px;">
                <h3>Food Reservation</h3>
              </div>
              <div id="foodTablesContainer" class="em-food-container"></div>
            </div>

            <input type="hidden" name="reservation_id" id="modalResId">
            <input type="hidden" name="res_type" id="modalResType">

            <input type="text" id="firstName" name="firstName" hidden>
            <input type="text" id="lastName" name="lastName" hidden>
            <input type="text" id="phoneNumber" name="phone" hidden>
            <input type="text" id="email" name="email" hidden>
            <input type="text" id="affiliation" name="affiliation" hidden>


            <div style="display:flex; flex-direction:column; gap:4px; margin-top:12px;" id="notesSection">
              <label style="font-size:12px; font-weight:600; color:#374151;">Additional Notes</label>
              <textarea
                id="notesInput"
                name="notes"
                rows="3"
                placeholder="No notes."
                readonly
                style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:12px; resize:vertical; font-family:inherit; color:#374151; box-sizing:border-box; background:#f9fafb;"></textarea>
            </div>

            <div id="editSection">
                <button type="button" id="editLink" class="check-out-btn" style="margin-top:20px; width: fit-content; align-self: flex-end; font-size: 12px; appearance: none; justify-content: center;">
                  Edit
                </button>
              </div>

            <div class="modal-left-bottom-container" id="modal-bottom">
              <div class="add-fees-discount-container" id="additionalChargesSection" style="align-items: flex-end;">
                <div class="add-fees-container">
                  <div class="additional-charges-header">
                    <button type="button" class="add-btn" id="addAdditionalCharges">+</button>
                    <label>Additional Charges:</label>

                  </div>

                  <div id="chargesContainer" class="charges-container">
                    <div class="charges-container-sub">
                      <input type="date" class="charge-input" name="additional_fees_date[]" style="width: 140px;" title="Date of charge">
                      <input id="addChargesDes" type="text" placeholder="Description" class="charge-input" name="additional_fees_desc[]">
                      <input id="addChargesQty" type="number" placeholder="Qty" class="charge-input" name="additional_fees_qty[]">
                      <input id="addChargesAmount" type="number" placeholder="₱" class="charge-input" name="additional_fees[]">
                    </div>
                  </div>
                </div>
                
                <div class="form-group-mini none" id="discountSection">
                <label style="padding: 9px 0px;">Discount:</label>
                  {{-- ── Discount type selector ── --}}
                  <select id="discountType" class="charge-input disc-type-select">
                    <option value="none">— No Discount —</option>
                    <option value="pwd">PWD (20%)</option>
                    <option value="senior">Senior Citizen (20%)</option>
                    <option value="others">Others</option>
                  </select>

                  {{-- PWD / Senior Citizen: shows auto-computed 20% peso amount --}}
                  <div id="discAutoRow" style="display:none; font-size:12px; color:#555; padding:4px 2px;">
                    <span id="discAutoLabel">PWD</span> — 20% of subtotal
                    = <strong id="discAutoComputed" style="color:#c0392b;">₱ 0.00</strong>
                  </div>

                  {{-- Others: custom label + mode toggle + value input --}}
                  <div id="discOthersRow" style="display:none; flex-direction:column; gap:6px; margin-top:2px;">
                    <input type="text" id="discOthersName" class="charge-input"
                           placeholder="Discount label (e.g. Scholar)" style="width:100%; box-sizing:border-box;">
                    <div style="display:flex; gap:6px; align-items:center;">
                      <div class="disc-mode-toggle">
                        <button type="button" id="discModePct"  class="disc-mode-btn active" data-mode="pct">%</button>
                        <button type="button" id="discModeFlat" class="disc-mode-btn"         data-mode="flat">₱</button>
                      </div>
                      <input type="number" id="discOthersValue" class="charge-input"
                             placeholder="0" min="0" step="0.01" style="flex:1; box-sizing:border-box;">
                    </div>
                  </div>

                  {{-- Hidden field: always stores the final peso discount amount --}}
                  <input type="hidden" id="discount" name="discount" value="0">
                </div>
              </div>

              <div id="exportSection">
                <a href="#" id="soaLink" class="export-btn">
                  Generate Statement of Accounts
                </a>
              </div>
              <input type="hidden" id="userId" value="">
            </div>
          </form>
        </div>

        <div class="modal-right-column">
          <div style="display:flex; justify-content:center; width:100%; margin:15px 0px;">
            <h3>Summary</h3>
          </div>

          <div class="detail-section">

            <div class="detail-section-top">

              <div class="detail-section-left">

                <div class="summary-item">
                  <p class="summary-label">Name:</p>
                  <p class="summary-value" id="fullName_r"></p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Phone Number:</p>
                  <p class="summary-value" id="phoneNumber_r"></p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Email:</p>
                  <p class="summary-value" id="email_r"></p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Affiliation:</p>
                  <p class="summary-value" id="affiliation_r"></p>
                </div>
                <div class="summary-item">
                  <p class="summary-label">Purpose:</p>

                  <div style="width: 20vw;
                                height: 4vh;
                                padding: 6px; ">
                    <span id="purpose_r" style="font-size:10px; color:#4a4a4a; "></span>
                  </div>
                </div>

              </div>

              <div class="detail-section-right">

                <div class="summary-item">
                  <p class="summary-label">Room/Venue:</p>
                  <p class="summary-value" id="modalName"></p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Number of Pax:</p>
                  <p class="summary-value" id="modalPax">1</p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Check-in Date:</p>
                  <p class="summary-value" id="modalCheckIn"> </p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Check-out Date:</p>
                  <p class="summary-value" id="modalCheckOut"></p>
                </div>
              </div>

            </div>

            <div class="detail-section-bottom">
              <div class="summary-divider"></div>

              <h4 class="total-label">Price Breakdown</h4>

              <div id="modalFoodList" class="price-breakdown">

                {{-- Accommodation row: shows name + formula sub-line --}}
                <div class="price-item" style="align-items: flex-start;">
                  <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span style="font-weight: 600;" id="accomodation-type"></span>
                    <span style="font-size: 0.78em; color: #888;">
                      <span id="unit-price" style="display: inline;">₱ 0</span>
                      &times;
                      <span id="modalNights">1</span>
                      <span id="nightsLabel">Nights</span>
                    </span>
                  </div>
                  <span style="font-weight: 600;" id="night-price">₱ 0</span>
                </div>

                {{-- Food --}}
                <div class="price-item">
                  <span>Food</span>
                  <span id="summaryFood">₱ 0</span>
                </div>

                {{-- Additional Fees --}}
                <div class="price-item">
                  <span>Additional Fees</span>
                  <span id="summaryExtra">₱ 0</span>
                </div>

                {{-- Discount --}}
                <div class="price-item" style="color: #e53e3e;">
                  <span>Discount</span>
                  <span id="summaryDiscount">₱ 0</span>
                </div>

              </div>
            </div>
            <div class="summary-divider"></div>

            <div class="price-total">
              <span class="total-text">Total</span>
              <span class="total-amount" id="totalAmount"></span>
            </div>
          </div>

          {{-- ── Request for Changes banner ── --}}
          <div id="empChangeRequestBanner" style="display:none; padding: 10px 16px 0;">
            <div class="emp-cancel-banner emp-change-banner">
              <div class="emp-cancel-banner-top">
                <span class="emp-cancel-banner-label emp-change-banner-label">🔄 Request for Changes</span>
                <span class="emp-cancel-banner-date" id="empChangeReqDate"></span>
              </div>
              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                <span class="emp-change-type-pill" id="empChangeReqTypePill"></span>
              </div>
              <p class="emp-cancel-banner-reason" id="empChangeReqReason" style="display:none;"></p>
              <div id="empChangeDetailsBox" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px; font-size:12px; color:#374151; line-height:1.6;"></div>
              <div class="emp-cancel-banner-actions">
                <button type="button" class="emp-cancel-reject-btn" id="empChangeRejectBtn">Reject Request</button>
                <button type="button" class="emp-cancel-approve-btn" id="empChangeApproveBtn" style="background:#1e40af;">Approve Changes</button>
              </div>
              <input type="text" id="empChangeAdminNote" class="emp-cancel-note-input"
                     placeholder="Note to client (optional)…" style="display:none;">
              <p id="empChangeBannerMsg" class="emp-cancel-banner-msg" style="display:none;"></p>
            </div>
          </div>

          {{-- ── Cancellation request banner (shown when a client has a pending request) ── --}}
          <div id="empCancelRequestBanner" style="display:none; padding: 10px 16px 0;">
            <div class="emp-cancel-banner">
              <div class="emp-cancel-banner-top">
                <span class="emp-cancel-banner-label">⚠ Request for Cancellation</span>
                <span class="emp-cancel-banner-date" id="empCancelReqDate"></span>
              </div>
              <p class="emp-cancel-banner-reason" id="empCancelReqReason"></p>
              <div class="emp-cancel-banner-actions">
                <button type="button" class="emp-cancel-reject-btn" id="empCancelRejectBtn">Reject Request</button>
                <button type="button" class="emp-cancel-approve-btn" id="empCancelApproveBtn">Approve &amp; Cancel Reservation</button>
              </div>
              <input type="text" id="empCancelAdminNote" class="emp-cancel-note-input"
                     placeholder="Optional note to client…" style="display:none;">
              <p id="empCancelBannerMsg" class="emp-cancel-banner-msg" style="display:none;"></p>
            </div>
          </div>

          <div class="modal-footer" style="height: fit-content;">
            <form id="statusForm" action="" method="POST">
              @csrf
              <input type="hidden" name="status" id="statusInput" value="">

              <div id="pendingActions" class="modal-actions" style="display: none; gap: 10px;">
                <button type="button" onclick="submitStatus('rejected')" class="reject-btn">Reject</button>
                <button type="button" onclick="submitStatus('confirmed')" class="accept-btn">Accept Reservation</button>
              </div>

              <div id="confirmedActions" class="modal-actions" style="display: none; gap: 10px;">
                <button type="button" onclick="submitStatus('cancelled')" class="reject-btn">Cancel Reservation</button>
                <button type="button" onclick="submitStatus('checked-in')" class="check-in-btn">CHECK-IN</button>
              </div>

              <div id="checkedInActions" class="modal-actions" style="display: none; gap: 10px;">
                <button type="button" class="check-in-btn" onclick="saveModificationsAndSubmit(event)">
                  SAVE MODIFICATIONS
                </button>
                <button type="button" onclick="submitStatus('checked-out')" class="check-out-btn">CHECK-OUT</button>
              </div>

              {{-- Checked-out + UNPAID: anyone can mark as paid --}}
              <div id="checkedOutUnpaidActions" class="modal-actions" style="display: none; gap: 10px; align-items: center;">
                <span class="unpaid-badge">⚠ UNPAID</span>
                <button type="button" class="accept-btn" onclick="doMarkAsPaid()">
                  MARK AS PAID
                </button>
              </div>

              {{-- Checked-out + PAID: show badge; admin can revert to unpaid --}}
              <div id="checkedOutPaidActions" class="modal-actions" style="display: none; gap: 10px; align-items: center;">
                <span class="paid-badge">✓ PAID</span>
                @if(auth()->user()->Account_Role === 'admin')
                  <button type="button" class="reject-btn" onclick="doMarkAsUnpaid()"
                          title="Revert payment status to unpaid">
                    REVERT TO UNPAID
                  </button>
                @endif
              </div>

          </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<script>
  function updateSoaLink(clientId) {
    const soaLink = document.getElementById('soaLink');
    const userIdInput = document.getElementById('userId');

    if (!soaLink || !userIdInput) return;
    userIdInput.value = clientId;
    soaLink.href = `/employee/SOA/${clientId}`;

    console.log('SOA LINK SET TO:', soaLink.href);
  }

  /* ── Employee: cancellation request banner ── */
  // _empCancelReqId is now the reservation ID (no separate cancellation_requests table)
  let _empCancelReqId   = null;  // reservation id (used as route param for process endpoint)
  let _empCancelResId   = null;  // reservation id (same value, kept for clarity)
  let _empCancelResType = null;  // 'room' | 'venue'

  /**
   * Call this whenever the employee modal opens.
   * resId   — the Room_Reservation_ID / Venue_Reservation_ID
   * resType — 'room' | 'venue'
   * status  — current reservation status string
   */
  function loadCancellationBanner(resId, resType, status) {
    _empCancelResId   = resId;
    _empCancelResType = resType;

    const banner    = document.getElementById('empCancelRequestBanner');
    const noteInput = document.getElementById('empCancelAdminNote');
    const msgEl     = document.getElementById('empCancelBannerMsg');

    if (!banner) return;

    // Only relevant when reservation is still pending or confirmed
    if (!['pending', 'confirmed'].includes((status || '').toLowerCase())) {
      banner.style.display = 'none';
      return;
    }

    // Reset banner state
    if (noteInput) { noteInput.style.display = 'none'; noteInput.value = ''; }
    if (msgEl)     { msgEl.style.display = 'none'; msgEl.textContent = ''; msgEl.className = 'emp-cancel-banner-msg'; }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(`/employee/reservations/${resId}/cancellation-request?type=${resType}`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    })
      .then(r => r.json())
      .then(data => {
        const req = data.request;
        if (!req || req.status !== 'pending') {
          banner.style.display = 'none';
          return;
        }
        _empCancelReqId = req.id;
        document.getElementById('empCancelReqDate').textContent   = req.created_at || '';
        document.getElementById('empCancelReqReason').textContent = req.reason || '';
        banner.style.display = '';
      })
      .catch(() => { banner.style.display = 'none'; });
  }

  /* ── Employee: Request for Changes banner ── */
  let _empChangeReqId   = null;
  let _empChangeResId   = null;
  let _empChangeResType = null;

  function loadChangeRequestBanner(resId, resType, status) {
    _empChangeResId   = resId;
    _empChangeResType = resType;

    const banner    = document.getElementById('empChangeRequestBanner');
    const noteInput = document.getElementById('empChangeAdminNote');
    const msgEl     = document.getElementById('empChangeBannerMsg');

    if (!banner) return;

    if (!['pending', 'confirmed'].includes((status || '').toLowerCase())) {
      banner.style.display = 'none';
      return;
    }

    if (noteInput) { noteInput.style.display = 'none'; noteInput.value = ''; }
    if (msgEl)     { msgEl.style.display = 'none'; msgEl.textContent = ''; msgEl.className = 'emp-cancel-banner-msg'; }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(`/employee/reservations/${resId}/change-request?type=${resType}`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    })
      .then(r => r.json())
      .then(data => {
        const req = data.request;
        if (!req || req.status !== 'pending') {
          banner.style.display = 'none';
          return;
        }
        _empChangeReqId = req.id;

        document.getElementById('empChangeReqDate').textContent = req.created_at || '';

        // Type pill
        const typePill = document.getElementById('empChangeReqTypePill');
        const typeLabels = {
          reschedule:            '📅 Reschedule',
          food_modification:     '🍽 Food Modification',
          reschedule_and_food:   '📅🍽 Reschedule + Food',
        };
        if (typePill) typePill.textContent = typeLabels[req.request_type] || req.request_type || '—';

        // Reason
        const reasonEl = document.getElementById('empChangeReqReason');
        if (reasonEl) {
          if (req.reason) {
            reasonEl.textContent   = req.reason;
            reasonEl.style.display = '';
          } else {
            reasonEl.style.display = 'none';
          }
        }

        // Details box — show new dates (reschedule) and/or new notes
        const detailsBox = document.getElementById('empChangeDetailsBox');
        if (detailsBox) {
          const isReschedule = req.request_type === 'reschedule' || req.request_type === 'reschedule_and_food';
          let html = '';
          if (req.details) {
            if (isReschedule) {
              if (req.details.check_in)  html += `<div><strong>New Check-in:</strong> ${req.details.check_in}</div>`;
              if (req.details.check_out) html += `<div><strong>New Check-out:</strong> ${req.details.check_out}</div>`;
            }
            if (req.details.notes !== undefined && req.details.notes !== null && String(req.details.notes).trim() !== '') {
              html += `<div style="margin-top:4px;"><strong>Updated Notes:</strong><br><span style="white-space:pre-wrap;">${String(req.details.notes).replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span></div>`;
            }
          }
          if (html) {
            detailsBox.innerHTML     = html;
            detailsBox.style.display = '';
          } else {
            detailsBox.style.display = 'none';
          }
        }

        banner.style.display = '';
      })
      .catch(() => { banner.style.display = 'none'; });
  }

  /* Change request: Reject / Approve */
  (function () {
    const rejectBtn  = document.getElementById('empChangeRejectBtn');
    const approveBtn = document.getElementById('empChangeApproveBtn');
    const noteInput  = document.getElementById('empChangeAdminNote');
    const msgEl      = document.getElementById('empChangeBannerMsg');

    if (!rejectBtn || !approveBtn) return;

    let rejectStep = 0;

    rejectBtn.addEventListener('click', () => {
      if (!_empChangeReqId) return;
      if (rejectStep === 0) {
        noteInput.style.display = '';
        noteInput.placeholder   = 'Reason for rejection (optional)…';
        rejectBtn.textContent   = 'Confirm Rejection';
        rejectStep = 1;
      } else {
        processChangeReq('rejected', noteInput.value, rejectBtn, approveBtn, msgEl);
      }
    });

    approveBtn.addEventListener('click', () => {
      if (!_empChangeReqId) return;
      approveBtn.disabled = true;
      processChangeReq('approved', '', rejectBtn, approveBtn, msgEl);
    });
  })();

  function processChangeReq(decision, adminNote, rejectBtn, approveBtn, msgEl) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(`/employee/change-requests/${_empChangeReqId}/process`, {
      method : 'POST',
      headers: {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'X-CSRF-TOKEN' : csrf,
      },
      body: JSON.stringify({ decision, admin_note: adminNote, res_type: _empChangeResType }),
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          msgEl.textContent   = data.message;
          msgEl.className     = 'emp-cancel-banner-msg success';
          msgEl.style.display = '';
          if (rejectBtn)  rejectBtn.style.display  = 'none';
          if (approveBtn) approveBtn.style.display = 'none';

          const label = decision === 'approved' ? 'Request for Changes approved.' : 'Request for Changes rejected.';
          if (typeof window.showToast === 'function') {
            window.showToast(label, decision === 'approved' ? 'success' : 'warning');
          }
          if (decision === 'approved') {
            setTimeout(() => window.location.reload(), 1800);
          }
        } else {
          msgEl.textContent   = data.message || 'Something went wrong.';
          msgEl.className     = 'emp-cancel-banner-msg error';
          msgEl.style.display = '';
          if (approveBtn) approveBtn.disabled = false;
          if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'Something went wrong.', 'error');
          }
        }
      })
      .catch(() => {
        msgEl.textContent   = 'Network error. Please try again.';
        msgEl.className     = 'emp-cancel-banner-msg error';
        msgEl.style.display = '';
        if (approveBtn) approveBtn.disabled = false;
        if (typeof window.showToast === 'function') {
          window.showToast('Network error. Please try again.', 'error');
        }
      });
  }

  /* Reject button — show note input then confirm */
  (function () {
    const rejectBtn  = document.getElementById('empCancelRejectBtn');
    const approveBtn = document.getElementById('empCancelApproveBtn');
    const noteInput  = document.getElementById('empCancelAdminNote');
    const msgEl      = document.getElementById('empCancelBannerMsg');

    if (!rejectBtn || !approveBtn) return;

    let rejectStep = 0; // 0=idle, 1=note shown

    rejectBtn.addEventListener('click', () => {
      if (!_empCancelReqId) return;
      if (rejectStep === 0) {
        noteInput.style.display = '';
        noteInput.placeholder   = 'Reason for rejection (optional)…';
        rejectBtn.textContent   = 'Confirm Rejection';
        rejectStep = 1;
      } else {
        processRequest('rejected', noteInput.value, rejectBtn, approveBtn, msgEl);
      }
    });

    approveBtn.addEventListener('click', () => {
      if (!_empCancelReqId) return;
      approveBtn.disabled = true;
      processRequest('approved', '', rejectBtn, approveBtn, msgEl);
    });
  })();

  function processRequest(decision, adminNote, rejectBtn, approveBtn, msgEl) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(`/employee/cancellation-requests/${_empCancelReqId}/process`, {
      method : 'POST',
      headers: {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'X-CSRF-TOKEN' : csrf,
      },
      body: JSON.stringify({ decision, admin_note: adminNote, res_type: _empCancelResType }),
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          msgEl.textContent  = data.message;
          msgEl.className    = 'emp-cancel-banner-msg success';
          msgEl.style.display = '';
          // Hide action buttons
          if (rejectBtn)  rejectBtn.style.display  = 'none';
          if (approveBtn) approveBtn.style.display = 'none';
          // Toast notification
          if (decision === 'approved') {
            if (typeof window.showToast === 'function') {
              window.showToast('Cancellation approved. Confirmation email sent to client.', 'success');
            }
            setTimeout(() => window.location.reload(), 1800);
          } else {
            if (typeof window.showToast === 'function') {
              window.showToast('Cancellation request rejected.', 'warning');
            }
          }
        } else {
          msgEl.textContent   = data.message || 'Something went wrong.';
          msgEl.className     = 'emp-cancel-banner-msg error';
          msgEl.style.display = '';
          if (approveBtn) approveBtn.disabled = false;
          if (typeof window.showToast === 'function') {
            window.showToast(data.message || 'Something went wrong.', 'error');
          }
        }
      })
      .catch(() => {
        msgEl.textContent   = 'Network error. Please try again.';
        msgEl.className     = 'emp-cancel-banner-msg error';
        msgEl.style.display = '';
        if (approveBtn) approveBtn.disabled = false;
        if (typeof window.showToast === 'function') {
          window.showToast('Network error. Please try again.', 'error');
        }
      });
  }
</script>

<style>
  /* ── Employee cancellation request banner ── */
  .emp-cancel-banner {
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 4px;
  }
  .emp-cancel-banner-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .emp-cancel-banner-label {
    font-size: 12px;
    font-weight: 700;
    color: #92400e;
    text-transform: uppercase;
    letter-spacing: .4px;
  }
  .emp-cancel-banner-date {
    font-size: 11px;
    color: #9ca3af;
  }
  .emp-cancel-banner-reason {
    font-size: 12px;
    color: #374151;
    margin: 0;
    line-height: 1.5;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 7px 10px;
    max-height: 72px;
    overflow-y: auto;
    white-space: pre-wrap;
  }
  .emp-cancel-banner-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  }
  .emp-cancel-reject-btn {
    background: none;
    border: 1.5px solid #d1d5db;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    padding: 6px 14px;
    cursor: pointer;
    transition: background .15s;
  }
  .emp-cancel-reject-btn:hover { background: #f3f4f6; }
  .emp-cancel-approve-btn {
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
  .emp-cancel-approve-btn:hover    { background: #b91c1c; }
  .emp-cancel-approve-btn:disabled { background: #fca5a5; cursor: not-allowed; }
  .emp-cancel-note-input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    padding: 7px 10px;
    font-family: inherit;
    color: #374151;
  }
  .emp-cancel-note-input:focus { outline: none; border-color: #3b82f6; }
  .emp-cancel-banner-msg {
    font-size: 12px;
    font-weight: 600;
    margin: 0;
    padding: 6px 10px;
    border-radius: 6px;
  }
  .emp-cancel-banner-msg.success { background: #d1fae5; color: #065f46; }
  .emp-cancel-banner-msg.error   { background: #fee2e2; color: #991b1b; }

  /* ── Request for Changes banner variant ── */
  .emp-change-banner {
    background: #eff6ff;
    border-color: #bfdbfe;
  }
  .emp-change-banner-label {
    color: #1e40af;
  }
  .emp-change-type-pill {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
    background: #dbeafe;
    color: #1e40af;
    letter-spacing: .3px;
  }

  /* ── Payment status badges (shown after checkout) ── */
  .unpaid-badge,
  .paid-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
  }
  .unpaid-badge {
    background: #fff7ed;
    color: #c2410c;
    border: 1.5px solid #fb923c;
  }
  .paid-badge {
    background: #f0fdf4;
    color: #15803d;
    border: 1.5px solid #86efac;
  }

  .food-date {
    justify-content: flex-end;
    margin: 5px 0px;
  }

  .card-title-wrap {
    display: flex;
    width: 100%;
    flex-direction: column;
    gap: 2px;
    align-items: flex-start;
  }

  .reservation-date-text {
    font-size: 14px;
    color: #666;
  }

  .food-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    table-layout: fixed;
    margin-top: 5px;
    background: #fff;
  }

  .food-table th,
  .food-table td {
    border: 1px solid #d9d9d9;
    padding: 10px;
    vertical-align: middle;
  }

  .food-table th {
    background: #f5f5f5;
    font-weight: 700;
    font-size: 14px;
    text-align: center;
  }

  .meal-column {
    width: 180px;
    min-width: 180px;
  }

  .meal-label-cell {
    background: #fafafa;
    width: 180px;
  }

  .meal-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .meal-name {
    font-weight: 700;
    font-size: 12px;
    color: #222;
  }

  .meal-toggle-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #666;
    cursor: pointer;
    width: fit-content;
  }

  .meal-toggle-wrap input {
    cursor: pointer;
  }

  .food-cell {
    min-width: 150px;
    background: #fff;
  }

  .food-select {
    width: 100%;
    min-width: 120px;
    padding: 10px 12px;
    border: 1px solid #d6d6d6;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    color: #333;
    outline: none;
  }

  .food-select:focus {
    border-color: #7aa7e0;
    box-shadow: 0 0 0 3px rgba(122, 167, 224, 0.12);
  }

  .cell-disabled {
    background: #f2f2f2 !important;
  }

  .cell-disabled .food-select {
    background: #ebebeb;
    color: #999;
    cursor: not-allowed;
    border-color: #dddddd;
  }

  .meal-row.row-disabled td {
    background: #efefef !important;
  }

  .meal-row.row-disabled .meal-name,
  .meal-row.row-disabled .meal-toggle-text {
    color: #9a9a9a;
  }

  .meal-row.row-disabled .food-select {
    background: #e5e5e5;
    color: #9c9c9c;
    border-color: #d0d0d0;
    cursor: not-allowed;
  }

  .reservation-card.food-disabled-card {
    opacity: 0.85;
  }

  .reservation-card.food-disabled-card .food-table,
  .reservation-card.food-disabled-card .meal-label-cell,
  .reservation-card.food-disabled-card .food-cell {
    background: #f1f1f1;
  }

  .meals-container {
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    height: 100%;
    width: 100%;
    padding: 0px 5px;
    max-height: 50vh;
  }

  @media (max-width: 1024px) {
    .food-table {
      min-width: 1400px;
    }
  }
</style>

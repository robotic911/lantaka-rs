<div class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle"></h2>
        <button class="close-btn">&times;</button>
      </div>

      <div class="modal-body modal-body-grid">
        <!-- LEFT COLUMN: FORM FIELDS -->
        <div class="modal-left-column">
          <form class="modal-form">
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName">
            </div>

            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName">
            </div>

            <div class="form-group">
              <label for="phoneNumber">Phone Number</label>
              <input type="text" id="phoneNumber">
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="text" id="email">
            </div>

            <div class="form-group">
              <label for="affiliation">Affiliation</label>
              <select id="affiliation">
                <option>External</option>
                <option>Internal</option>
              </select>
            </div>

            <!-- Additional checkout-only fields -->
            <div class="form-group" id="discountGroup" style="display: none;">
              <label for="discount">Discount</label>
              <input type="text" id="discount" placeholder="Enter Discount">
            </div>

            <div id="additionalChargesSection" style="display: none;">
              <div class="additional-charges-header">
                <label>Additional Charges:</label>
                <button type="button" class="add-btn">+</button>
              </div>
              <div id="chargesContainer" class="charges-container">
                <input type="text" placeholder="" class="charge-input">
                <input type="text" placeholder="" class="charge-input">
                <input type="text" placeholder="" class="charge-input">
              </div>
            </div>

            <div id="exportSection" style="display: none;">
              <label>Generate Statement of Accounts:</label>
              <button type="button" class="export-btn">EXPORT</button>
            </div>
          </form>
        </div>

        <!-- RIGHT COLUMN: RESERVATION SUMMARY -->
        <div class="modal-right-column">
          <div class="detail-section">
            <h3 class="summary-title" id="summaryTitle">Room / Venue Details</h3>
            
            <div class="summary-item">
              <p class="summary-label">Room/Venue:</p>
              <p class="summary-value" id="modalName">Room 102 (Single bed)</p>
            </div>

            <div class="summary-item">
              <p class="summary-label">Number of Pax:</p>
              <p class="summary-value" id="modalLastName">1</p>
            </div>

            <div class="summary-item">
              <p class="summary-label">Check-in Date:</p>
              <p class="summary-value" id="modalCheckIn">September 25, 2025</p>
            </div>

            <div class="summary-item">
              <p class="summary-label">Check-out Date:</p>
              <p class="summary-value" id="modalCheckOut">September 26, 2025</p>
            </div>

            <div class="summary-divider"></div>

            <h4 class="total-label">Total</h4>

            <div id="modalFoodList" class="price-breakdown">
              <div class="price-item">
                <span id="accomodation-type"></span>
                <span id="unit-price"></span>
              </div>
              <div class="price-item">
                <span>Food</span>
                <span>₱ 0</span>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="price-total">
              <span class="total-text">Total</span>
              <span class="total-amount" id="totalAmount"></span>
            </div>
          </div>

          <div class="modal-footer">
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
                <button type="button" class="cancel-btn">CANCEL</button>
                <button type="button" onclick="submitStatus('checked-out')" class="check-out-btn">CHECK-OUT</button>
              </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

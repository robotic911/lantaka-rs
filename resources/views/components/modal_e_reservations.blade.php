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
          <form class="modal-form" method="POST" action="{{ route('employee.updateGuests') }}">
            @csrf
            @method('PUT')
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="firstName" readonly>
            </div>

            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="lastName" readonly>
            </div>

            <div class="form-group">
              <label for="phoneNumber" >Phone Number</label>
              <input type="text" id="phoneNumber" name="phone" readonly>
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="text" id="email" name="email" readonly>
            </div>

            <div class="form-group" id="affiliationAndDiscount" >
              <div class="form-group-mini">
                <label for="affiliation">Affiliation</label>
                <input type="text" id="affiliation" name="affiliation" readonly>
              </div>
              <div class="form-group-mini none" id="discountSection">
                <label for="discount">Discount</label>
                <input type="text" id="discount" placeholder="Enter Discount"  name="discount">
              </div>
            </div>

            <div id="additionalChargesSection">
              <div class="additional-charges-header">
                <label>Additional Charges:</label>
                <button type="button" class="add-btn" id="addAdditionalCharges">+</button>
              </div>
                <div id="chargesContainer" class="charges-container">
                  <div class="charges-container-sub">
                    <input id="addChargesDes" type="text" placeholder="Description" class="charge-input" name="additionalFeesDesc">
                    <input id="addChargesQty" type="number" placeholder="Qty" class="charge-input">
                    <input id="addChargesAmount" type="number" placeholder="₱" class="charge-input" name="additionalFees">
                  </div>
              </div>
            </div>

            <div id="exportSection">
              <label>Generate Statement of Accounts:</label>
              <a href="{{ route('employee.SOA') }}" class="export-btn">
                ADD TO SOA
              </a>            
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
              <div class="price-item">
                <span>Discount</span>
                <span>₱ 0</span>
              </div>
              <div class="price-item">
                <span>Additional Fees</span>
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
                <button type="button" class="check-in-btn">SAVE MODIFICATIONS</button>
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

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
              <div id="foodTablesContainer">
  <table class="food-table">
    <thead>
      <tr>
        <th colspan="8">
          <p class="food-date"></p>
        </th>
      </tr>
      <tr>
        <th class="meal-column">Meal Time</th>
        <th>Rice</th>
        <th>Set Viand</th>
        <th>Sidedish</th>
        <th>Drinks</th>
        <th>Desserts</th>
        <th>Other Viand</th>
        <th>Snack</th>
      </tr>
    </thead>

    <tbody>
      <tr class="meal-row">
        <td class="meal-label-cell"><div class="meal-header"><span class="meal-name">Breakfast</span></div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
      </tr>

      <tr class="meal-row">
        <td class="meal-label-cell"><div class="meal-header"><span class="meal-name">AM Snack</span></div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
      </tr>

      <tr class="meal-row">
        <td class="meal-label-cell"><div class="meal-header"><span class="meal-name">Lunch</span></div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
      </tr>

      <tr class="meal-row">
        <td class="meal-label-cell"><div class="meal-header"><span class="meal-name">PM Snack</span></div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
      </tr>

      <tr class="meal-row">
        <td class="meal-label-cell"><div class="meal-header"><span class="meal-name">Dinner</span></div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
        <td><div class="food-display">None</div></td>
      </tr>
    </tbody>
  </table>
</div>
            </div>





            <input type="hidden" name="reservation_id" id="modalResId">
            <input type="hidden" name="res_type" id="modalResType">

            <input type="text" id="firstName" name="firstName" hidden>
            <input type="text" id="lastName" name="lastName" hidden>
            <input type="text" id="phoneNumber" name="phone" hidden>
            <input type="text" id="email" name="email" hidden>
            <input type="text" id="affiliation" name="affiliation" hidden>


            <div id="editSection">
                <button type="button" id="editLink" class="check-out-btn" style="
                width: fit-content;
                align-self: flex-end;
                font-size: 12px;
                appearance: none;">
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

                <hr style="
                height: 40px;
                width: 7.5px;
                background-color: #222;
                margin-left: -10px;
                margin-right: 7px;"/>
                <div class="form-group-mini none" id="discountSection">
                  <label for="discount">Discount:</label>
                  <input class="charge-input" type="text" id="discount" placeholder="Enter Discount" name="discount">
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
                    <span id="purpose_r" style="font-size:10px; color:#4a4a4a; ">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. </span>
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
                  <p class="summary-value" id="modalLastName">1</p>
                </div>

                <div class="summary-item">
                  <p class="summary-label">Check-in Date:</p>
                  <p class="summary-value" id="modalCheckIn">/p>
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
                <button type="button" class="check-in-btn" onclick="saveModificationsAndSubmit(event)">
                  SAVE MODIFICATIONS
                </button>
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
<script>
  function updateSoaLink(clientId) {
    const soaLink = document.getElementById('soaLink');
    const userIdInput = document.getElementById('userId');

    if (!soaLink || !userIdInput) return;

    userIdInput.value = clientId;
    soaLink.href = `/employee/SOA/${clientId}`;

    console.log('SOA LINK SET TO:', soaLink.href);
  }
</script>

<style>
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
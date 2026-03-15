<div class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reservation Details</h2>
        <button class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="detail-section">
          <p class="detail-label">Room/Venue:</p>
          <p class="detail-value" id="modalAccommodation"></p>
        </div>

        <div class="detail-section">
          <p class="detail-label">Number of Pax:</p>
          <p class="detail-value" id="modalPax"></p>
        </div>    

        <div class="detail-section">
          <p class="detail-label">Check-in Date:</p>
          <p class="detail-value" id="modalCheckIn"></p>
        </div>

        <div class="detail-section">
          <p class="detail-label">Check-out Date:</p>
          <p class="detail-value" id="modalCheckOut"></p>
        </div>

        <div class="detail-section">
          <p class="detail-label" id="modalFoodIdLabel">Food:</p>
          
          <div id="modalFoodList"></div>
          <input type="hidden" id="cancelReservationId">
          
        </div>
          <div class="detail-section-cancel">
            <button class="cancel-reservation" onclick="confirmCancellation()">CANCEL RESERVATION</button>
          </div>
        
      </div>
    </div>
  </div>
</div>

<style>
  .modal-food-table{
  width:100%;
  border-collapse:collapse;
  font-size:0.85rem;
}

.modal-food-table th,
.modal-food-table td{
  border:1px solid #ddd;
  padding:8px;
  text-align:left;
}

.modal-food-table th{
  background:#f5f5f5;
  font-weight:600;
}
</style>
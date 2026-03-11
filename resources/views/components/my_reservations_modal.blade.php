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
          <p class="detail-label" id="modalFoodIdLabel">Food ID:</p>
          
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
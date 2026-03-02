<div class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reservation Details</h2>
        <button class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="detail-section">
          <p class="detail-label">Name:</p>
          <p class="detail-value" id="modalName"></p>
        </div>

        <div class="detail-section">
          <p class="detail-label">Last Name:</p>
          <p class="detail-value" id="modalLastName"></p>
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

          <div class="modal-footer">
                <form id="statusForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="status" id="statusInput" value="">
                    
                    <div id="pendingActions" class="modal-actions" style="display: none; gap: 10px;">
                        <button type="button" onclick="submitStatus('confirmed')" class="accept-btn" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Accept Reservation</button>
                        <button type="button" onclick="submitStatus('cancelled')" class="reject-btn" style="background: #d9534f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Reject</button>
                    </div>

                    <div id="confirmedActions" class="modal-actions" style="display: none; gap: 10px;">
                        <button type="button" onclick="submitStatus('checked-in')" class="accept-btn" style="background: #22c55e; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Check-in Guest</button>
                        <button type="button" onclick="submitStatus('cancelled')" class="reject-btn" style="background: #d9534f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Cancel Reservation</button>
                    </div>

                    <div id="checkedInActions" class="modal-actions" style="display: none; gap: 10px;">
                        <button type="button" onclick="submitStatus('checked-out')" class="accept-btn" style="background: #ef9744; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Check-out / Complete</button>
                    </div>
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
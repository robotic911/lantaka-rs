
  <link rel="stylesheet" href="{{ asset('css/approve_account_modal.css')}}">
  
  <div id="approvalOverlay" class="approval-overlay">
    <div class="approval-modal">
      <button class="approval-close">&times;</button>
      <h2>Pending Client Details</h2>
      
      <form class="approval-form" onsubmit="return false;">
        <div class="approval-row">
          <div class="approval-field">
            <label>Username</label>
            <input type="text" id="approve_username" readonly>
          </div>
          <div class="approval-field">
            <label>Password</label>
            <div class="approval-password">
              <input type="password" id="approve_password_input" value="********" readonly>
              <span class="approval-eye" id="approvePasswordEye" title="Show / hide password">
                <svg id="approveEyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </span>
            </div>
          </div>
        </div>
        <div class="approval-row">
          <div class="approval-field">
            <label>First Name</label>
            <input type="text" id="approve_fname" readonly>
          </div>
          <div class="approval-field">
            <label>Last Name</label>
            <input type="text" id="approve_lname" readonly>
          </div>
        </div>

        <div class="approval-row">
          <div class="approval-field">
            <label>Phone Number</label>
            <input type="text" id="approve_phone" readonly>
          </div>
          <div class="approval-field">
            <label>Email</label>
            <input type="text" id="approve_email" readonly>
          </div>
        </div>

        <div class="approval-field full-width">
            <label>ID / Proof of Identity</label>
            <div class="id-preview-container">
                <img id="approve_id_image"
                     src="{{ asset('images/placeholder_id.svg') }}"
                     data-placeholder="{{ asset('images/placeholder_id.svg') }}"
                     alt="Valid ID">
            </div>
        </div>
        
        <div class="approval-buttons">
            <button type="button" class="approval-btn decline btn-decline">DECLINE</button>
            <button type="button" class="approval-btn accept btn-accept">ACCEPT</button>
        </div>
      </form>
    </div>
  </div>

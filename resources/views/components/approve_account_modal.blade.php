
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
              <input type="password" value="********" readonly>
              <span class="approval-eye"></span>
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

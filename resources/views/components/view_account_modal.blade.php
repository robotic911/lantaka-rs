
  <link rel="stylesheet" href="{{ asset('css/view_account_modal.css') }}">

  <div id="accountOverlay" class="account-overlay">
    <div class="account-modal">
      <button class="account-close">&times;</button>
      <h2>Account Details</h2>
      
     <form id="updateAccountForm" action="" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
        <div class="account-row">
          <div class="account-field">
            <label>Username</label>
            <input type="text" id="view_username" name="username">
          </div>
          <div class="account-field">
            <label>Password</label>
            <div class="account-password">
              <input type="password" id="view_password_input" name="password" placeholder="Leave blank to keep current">
              <span class="account-eye" id="viewPasswordEye" title="Show / hide password">
                <svg id="viewEyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </span>
            </div>
          </div>
        </div>

        <div class="account-row">
          <div class="account-field">
            <label>First Name</label>
            <input type="text" id="view_fname" name="first_name">
          </div>
          <div class="account-field">
            <label>Last Name</label>
            <input type="text" id="view_lname" name="last_name">
          </div>
        </div>

        <div class="account-row">
          <div class="account-field">
              <label>Phone Number</label>
              <input type="text" id="view_phone" name="phone_no"> 
          </div>
          <div class="account-field">
              <label>Email</label>
              <input type="text" id="view_email" name="email">
          </div>
        </div>

        <div class="account-field full-width">
          <label>Valid ID</label>
          <div style="margin-bottom:8px;">
            <img id="view_id_preview"
                 src="{{ asset('images/placeholder_id.svg') }}"
                 data-placeholder="{{ asset('images/placeholder_id.svg') }}"
                 alt="Valid ID"
                 style="max-width:100%; max-height:220px; border-radius:6px; border:1px solid #ddd; display:block; object-fit:contain;">
          </div>
          <input type="file" id="view_id_file" name="valid_id" accept="image/*" style="font-size:13px;">
        </div>

        <div class="approval-buttons">
            {{-- Shown for active accounts --}}
            <button type="submit" name="action" value="deactivate" id="btn-deactivate" class="approval-btn deactivate btn-decline">DEACTIVATE</button>
            {{-- Shown only for deactivated accounts --}}
            <button type="submit" name="action" value="reactivate" id="btn-reactivate" class="approval-btn accept btn-accept" style="display:none;" data-sends-email="true">REACTIVATE</button>
            <button type="submit" name="action" value="save" id="btn-save" class="approval-btn accept btn-accept" data-sends-email="true">SAVE</button>
        </div>
      </form>
    </div>
  </div>


  <link rel="stylesheet" href="{{ asset('css/view_account_modal.css') }}">

  <div id="accountOverlay" class="account-overlay">
    <div class="account-modal">
      <button class="account-close">&times;</button>
      <h2>Account Details</h2>
      
     <form id="updateAccountForm" action="" method="POST">
      @csrf
        <div class="account-row">
          <div class="account-field">
            <label>Username</label>
            <input type="text" id="view_username" name="username">
          </div>
          <div class="account-field">
            <label>Password</label>
            <div class="account-password">
              <input type="password" name="password" placeholder="Leave blank to keep current">
              <span class="account-eye">👁‍🗨</span>
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
          <label>ID Info</label>
          <textarea id="view_id_info" name="id_info"></textarea>
        </div>

        <div class="approval-buttons">
            <button type="submit" name="action" value="deactivate" class="approval-btn deactivate btn-decline">DEACTIVATE</button>
            <button type="submit" name="action" value="save" class="approval-btn accept btn-accept">SAVE</button>
        </div>
      </form>
    </div>
  </div>

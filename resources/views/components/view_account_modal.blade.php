
  <link rel="stylesheet" href="{{ asset('css/view_account_modal.css') }}">

  <div id="accountOverlay" class="account-overlay">
    <div class="account-modal">
      <button class="account-close">&times;</button>
      <h2>Account Details</h2>
      
      <form class="account-form">
        <div class="account-row">
          <div class="account-field">
            <label>Username</label>
            <input type="text" value="LRS-Suzie">
          </div>
          <div class="account-field">
            <label>Password</label>
            <div class="account-password">
              <input type="password" value="password123">
              <span class="account-eye">👁‍🗨</span>
            </div>
          </div>
        </div>

        <div class="account-row">
          <div class="account-field">
            <label>First Name</label>
            <input type="text" value="Suzie">
          </div>
          <div class="account-field">
            <label>Last Name</label>
            <input type="text" value="Ko">
          </div>
        </div>

        <div class="account-row">
          <div class="account-field">
            <label>Phone Number</label>
            <input type="text" value="09972221124">
          </div>
          <div class="account-field">
            <label>Email</label>
            <input type="text" value="anditooh22@gmail.com">
          </div>
        </div>

        <div class="account-field full-width">
          <label>ID</label>
          <textarea></textarea>
        </div>

        <div class="account-buttons">
          <button type="button" class="account-btn deactivate" onclick="closeAccountModal()">DEACTIVATE</button>
          <button type="submit" class="account-btn save">SAVE</button>
        </div>
      </form>
    </div>
  </div>

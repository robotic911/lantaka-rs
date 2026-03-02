
  <link rel="stylesheet" href="{{ asset('css/approve_account_modal.css')}}">
  
  <div id="approvalOverlay" class="approval-overlay">
    <div class="approval-modal">
      <button class="approval-close">&times;</button>
      <h2>Pending Client Details</h2>
      
      <form class="approval-form">
        <div class="approval-row">
          <div class="approval-field">
            <label>Username</label>
            <input type="text" value="LRS-Suzie" readonly>
          </div>
          <div class="approval-field">
            <label>Password</label>
            <div class="approval-password">
              <input type="password" value="password123" readonly>
              <span class="approval-eye">👁‍🗨</span>
            </div>
          </div>
        </div>

        <div class="approval-row">
          <div class="approval-field">
            <label>First Name</label>
            <input type="text" value="Suzie" readonly>
          </div>
          <div class="approval-field">
            <label>Last Name</label>
            <input type="text" value="Ko" readonly>
          </div>
        </div>

        <div class="approval-row">
          <div class="approval-field">
            <label>Phone Number</label>
            <input type="text" value="09972221124" readonly>
          </div>
          <div class="approval-field">
            <label>Email</label>
            <input type="text" value="anditooh22@gmail.com" readonly>
          </div>
        </div>

        <div class="approval-field full-width">
          <label>ID</label>
          <textarea readonly></textarea>
        </div>

        <div class="approval-buttons">
          <button type="button" class="approval-btn decline">DECLINE</button>
          <button type="button" class="approval-btn accept">ACCEPT</button>
        </div>
      </form>
    </div>
  </div>  

{{-- Email Toaster --}}
<div id="emailToaster">
  <span id="toasterIcon"></span>
  <span id="toasterText"></span>
</div>

<style>
  #emailToaster {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    min-width: 240px;
    max-width: 320px;
    background: #1e3a5f;
    color: #fff;
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.25);
    font-size: 14px;
    font-weight: 500;
    transform: translateY(80px);
    opacity: 0;
    transition: transform 0.3s ease, opacity 0.3s ease;
    pointer-events: none;
  }
  #emailToaster.show {
    transform: translateY(0);
    opacity: 1;
  }
  #emailToaster.state-sent    { background: #166534; }
  #emailToaster.state-error   { background: #991b1b; }
  #emailToaster.state-warning { background: #92400e; }

  .toaster-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: toastSpin 0.7s linear infinite;
    flex-shrink: 0;
  }
  @keyframes toastSpin { to { transform: rotate(360deg); } }
</style>

<script>
  window._emailSent = @json(session('email_sent', false));

  (function () {
    let _dismissTimer = null;

    // Generic toast: showToast(message, type) where type = 'warning'|'error'|'success'
    window.showToast = function (message, type) {
      const toast = document.getElementById('emailToaster');
      const icon  = document.getElementById('toasterIcon');
      const text  = document.getElementById('toasterText');
      if (!toast) return;

      clearTimeout(_dismissTimer);
      toast.classList.remove('show', 'state-sent', 'state-error', 'state-warning');

      const stateClass = type === 'error' ? 'state-error' : type === 'success' ? 'state-sent' : 'state-warning';
      icon.innerHTML   = type === 'success' ? '&#10003;' : '&#9888;';
      text.textContent = message;
      toast.classList.add('show', stateClass);
      _dismissTimer = setTimeout(() => toast.classList.remove('show'), 4000);
    };

    window.showEmailToast = function (state) {
      const toast = document.getElementById('emailToaster');
      const icon  = document.getElementById('toasterIcon');
      const text  = document.getElementById('toasterText');
      if (!toast) return;

      clearTimeout(_dismissTimer);

      // Reset classes
      toast.classList.remove('show', 'state-sent', 'state-error');

      if (state === 'sending') {
        icon.innerHTML  = '<div class="toaster-spinner"></div>';
        text.textContent = 'Sending email\u2026';
        toast.classList.add('show');

      } else if (state === 'sent') {
        icon.innerHTML  = '&#10003;';
        text.textContent = 'Email sent successfully!';
        toast.classList.add('show', 'state-sent');
        _dismissTimer = setTimeout(() => toast.classList.remove('show'), 3000);

      } else if (state === 'error') {
        icon.innerHTML  = '&#10007;';
        text.textContent = 'Email failed to send.';
        toast.classList.add('show', 'state-error');
        _dismissTimer = setTimeout(() => toast.classList.remove('show'), 4000);
      }
    };

    document.addEventListener('DOMContentLoaded', function () {
      // On page load — show "sent" if server flagged an email was sent
      if (window._emailSent) {
        window.showEmailToast('sent');
      }

      // Show "sending" when any button/element with data-sends-email is clicked
      document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sends-email]')) {
          window.showEmailToast('sending');
        }
      });
    });
  })();
</script>

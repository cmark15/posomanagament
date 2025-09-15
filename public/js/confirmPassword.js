document.addEventListener('DOMContentLoaded', () => {
    const form       = document.getElementById('changePasswordForm');
    const pw         = document.getElementById('new_password');
    const confirmPw  = document.getElementById('password_confirmation');
    const feedback   = document.getElementById('confirmFeedback');
    const submitBtn  = document.getElementById('submitBtn');

    function validateMatch() {
      const match = pw.value && (pw.value === confirmPw.value);
      if (!match) {
        confirmPw.classList.add('is-invalid');
        submitBtn.disabled = true;
      } else {
        confirmPw.classList.remove('is-invalid');
        submitBtn.disabled = false;
      }
    }

    pw.addEventListener('input', validateMatch);
    confirmPw.addEventListener('input', validateMatch);
  });
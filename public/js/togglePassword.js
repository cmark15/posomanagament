
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('togglePassword');
  const pwd    = document.getElementById('password');

  toggle.addEventListener('click', () => {
    // flip input type
    const isPwd = pwd.getAttribute('type') === 'password';
    pwd.setAttribute('type', isPwd ? 'text' : 'password');

    // swap icon
    const icon = toggle.querySelector('i');
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
  });
});

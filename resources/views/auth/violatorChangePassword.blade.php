<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover"
  >
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Reset Violator Password</title>

  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --bg: #f6fff9;
      --card-shadow: 0 10px 30px rgba(0,0,0,.06);
      --radius-xl: 18px;
      --accent: #00c853; /* POSO green */
    }
    html, body { height: 100%; background: var(--bg); }

    .pw-page {
      min-height: 100vh;
      padding: 16px;
      background:
        radial-gradient(1200px 800px at 120% -10%, rgba(0,200,83,0.08), transparent 60%),
        radial-gradient(1000px 700px at -20% 120%, rgba(0,200,83,0.06), transparent 60%),
        var(--bg);
    }
    .pw-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border: 1px solid #eef3ee;
      border-radius: var(--radius-xl);
      padding: 20px 18px;
      box-shadow: var(--card-shadow);
      animation: riseIn .45s ease-out both;
    }
    @keyframes riseIn {
      from { opacity: 0; transform: translateY(14px) scale(.98); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .icon-bounce { animation: bounce 1.8s ease-in-out infinite; }
    @keyframes bounce {
      0%,20%,50%,80%,100% { transform: translateY(0); }
      40% { transform: translateY(-8px); }
      60% { transform: translateY(-4px); }
    }

    .input-group-lg .form-control,
    .input-group-lg .input-group-text,
    .input-group-lg .btn { border-radius: 12px; }

    .form-control:focus {
      border-color: var(--accent) !important;
      box-shadow: 0 0 0 .2rem rgba(0,200,83,.15) !important;
    }

    .btn-success {
      background-color: var(--accent);
      border-color: var(--accent);
      border-radius: 12px;
    }
    .btn-success:hover { filter: brightness(.95); }

    .progress { height: 8px; border-radius: 999px; background: #eef3ee; }
    .progress-bar { border-radius: 999px; transition: width .25s ease; }

    @media (max-width: 420px){ .pw-card { padding: 18px 14px; } }
  </style>
</head>
<body>

  <main class="pw-page d-flex align-items-center justify-content-center">
    <div class="pw-card shadow-lg">
      <div class="text-center mb-2">
        <i class="bi bi-key-fill display-5 text-success icon-bounce"></i>
      </div>

      @if(session('status'))
        <div class="alert alert-success text-center py-2 mb-3">{{ session('status') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger text-center py-2 mb-3">{{ $errors->first() }}</div>
      @endif

      <h1 class="h5 text-center fw-bold mb-3 text-success">Reset Password</h1>
      <p class="text-center text-muted small mb-4">
        Set a new password to continue using the Violator portal.
      </p>

      <form method="POST" action="{{ route('violator.password.update') }}" novalidate>
        @csrf

        <!-- New Password -->
        <div class="mb-3">
          <label for="password" class="form-label">New Password</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control @error('password') is-invalid @enderror"
              placeholder="At least 8 characters"
              minlength="8"
              required
            >
            <button type="button" class="btn btn-outline-secondary" onclick="toggleField('password','toggleIcon1')" tabindex="-1">
              <i class="bi bi-eye-slash" id="toggleIcon1"></i>
            </button>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="progress mt-2" role="progressbar" aria-label="Password strength">
            <div id="pwStrengthBar" class="progress-bar" style="width: 0%"></div>
          </div>
          <div id="pwHint" class="form-text small">Use a mix of letters, numbers, and symbols.</div>
        </div>

        <!-- Confirm -->
        <div class="mb-4">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
            <input
              type="password"
              name="password_confirmation"
              id="password_confirmation"
              class="form-control @error('password_confirmation') is-invalid @enderror"
              placeholder="Re-enter new password"
              minlength="8"
              required
            >
            <button type="button" class="btn btn-outline-secondary" onclick="toggleField('password_confirmation','toggleIcon2')" tabindex="-1">
              <i class="bi bi-eye-slash" id="toggleIcon2"></i>
            </button>
            @error('password_confirmation')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div id="matchHint" class="form-text small"></div>
        </div>

        <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold" id="submitBtn">
          <i class="bi bi-box-arrow-in-right me-2"></i>Update Password
        </button>
      </form>

      <p class="text-center mt-3 mb-0">
        <small class="text-muted">You’ll be asked to log in again after updating.</small>
      </p>
      <p class="text-center mt-2 mb-0">
        <a href="{{ route('violator.showLogin') }}" class="small text-decoration-none">
          <i class="bi bi-arrow-left-circle me-1"></i>Back to Login
        </a>
      </p>
    </div>
  </main>

  <!-- SweetAlert (used when forced) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Show only when forced by session (first-time / default password)
    @if(session('must_change_password') || session('force_pwd_change'))
      document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
          title: 'Default Password Detected',
          text:  'Please set a new password to continue.',
          icon:  'warning',
          confirmButtonText: 'Okay',
          allowOutsideClick: false,
          allowEscapeKey: false
        });
      });
    @endif

    function toggleField(fieldId, iconId) {
      const input = document.getElementById(fieldId);
      const icon  = document.getElementById(iconId);
      if (!input || !icon) return;
      if (input.type === 'password') { input.type = 'text'; icon.classList.replace('bi-eye-slash','bi-eye'); }
      else { input.type = 'password'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    }

    // Live hints: strength + match
    const pw = document.getElementById('password');
    const pc = document.getElementById('password_confirmation');
    const bar = document.getElementById('pwStrengthBar');
    const matchHint = document.getElementById('matchHint');

    function score(s) {
      let n = 0;
      if (!s) return 0;
      if (s.length >= 8) n++;
      if (/[A-Z]/.test(s)) n++;
      if (/[a-z]/.test(s)) n++;
      if (/\d/.test(s)) n++;
      if (/[^A-Za-z0-9]/.test(s)) n++;
      return Math.min(n, 5);
    }
    function paint() {
      const val = pw.value || '';
      const sc  = score(val);
      const pct = [0, 20, 40, 60, 80, 100][sc];
      bar.style.width = pct + '%';
      bar.classList.remove('bg-danger','bg-warning','bg-success');
      if (pct < 40) bar.classList.add('bg-danger');
      else if (pct < 80) bar.classList.add('bg-warning');
      else bar.classList.add('bg-success');

      if (pc.value.length) {
        if (pc.value === val) { matchHint.textContent = 'Passwords match.'; matchHint.className = 'form-text small text-success'; }
        else { matchHint.textContent = 'Passwords do not match.'; matchHint.className = 'form-text small text-danger'; }
      } else {
        matchHint.textContent = ''; matchHint.className = 'form-text small';
      }
    }
    ['input','change'].forEach(evt => {
      pw.addEventListener(evt, paint);
      pc.addEventListener(evt, paint);
    });

    // Remove invalid on typing
    document.querySelectorAll('#password, #password_confirmation').forEach(el=>{
      el.addEventListener('input',()=> el.classList.remove('is-invalid'));
    });

    // Disable submit while posting
    const form = document.querySelector('form');
    const btn  = document.getElementById('submitBtn');
    form.addEventListener('submit', () => {
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating…'; }
    });

    // Autofocus
    pw?.focus();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

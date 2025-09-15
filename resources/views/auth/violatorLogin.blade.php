@extends('components.violator')

@section('title', 'POSO Digital Ticket')

@section('violator')
<div class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light login-container">
  <div class="card login-card shadow-lg" style="width: 90%; max-width: 400px; border-radius: 12px;">
    <div class="card-body p-4">
      
      {{-- Header Icon + Title --}}
      <div class="text-center mb-4">
        <i class="bi bi-ticket-perforated display-1 text-success mb-3 icon-bounce"></i>
        <h3 class="text-center fw-bold mb-4 text-success login-title">Violators Login</h3>
      </div>

      {{-- Top-of-form alerts --}}
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ $errors->first() }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @php
        $remaining = session('lockout_remaining'); // seconds
      @endphp

      @if ($remaining)
        <div class="alert alert-warning" role="alert">
          Too many failed attempts. You can try again in
          <span id="lockout-timer" class="fw-bold"></span>.
        </div>
      @endif

      

      {{-- Login Form --}}
      <form method="POST" action="{{ route('violator.login') }}">
        @csrf

        {{-- Username --}}
        <div class="mb-3 input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-person-fill"></i>
          </span>
          <input
            type="text"
            name="username"
            id="username"
            class="form-control border-start-0"
            placeholder="Username"
            autocomplete="username"
            required
          >
        </div>

        {{-- Password + Toggle --}}
        <div class="mb-4 input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-lock-fill"></i>
          </span>
          <input
            type="password"
            name="password"
            id="password"
            class="form-control border-start-0"
            placeholder="Password"
            autocomplete="current-password"
            required
          >
          <button
            type="button"
            class="btn btn-outline-secondary"
            onclick="togglePassword()"
            tabindex="-1"
          >
            <i class="bi bi-eye-slash" id="toggleIcon"></i>
          </button>
        </div>

        {{-- Submit --}}
        <button id="loginBtn" type="submit" class="btn btn-success w-100 py-2 fw-semibold mb-3" style="border-radius: 12px;"  >
          Log In
        </button>
      </form>

    </div>
  </div>
</div>
@endsection

<style>
  /* Fade-in slide-up */
  .login-card {
    opacity: 0;
    animation: fadeInUp 0.8s ease-out forwards;
  }
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Bounce icon */
  @keyframes bounce {
    0%,20%,50%,80%,100% { transform: translateY(0); }
    40% { transform: translateY(-15px); }
    60% { transform: translateY(-7px); }
  }
  .icon-bounce {
    animation: bounce 2s infinite;
  }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // one-time notice
    if (!localStorage.getItem('violatorLoginNoticeShown')) {
      Swal.fire({
        icon: 'info',
        title: 'Notice',
        text: 'Please use the login credentials provided by POSO Officer.',
        confirmButtonColor: '#00c853'
      }).then(() => localStorage.setItem('violatorLoginNoticeShown', 'true'));
    }

    // lockout countdown (if any)
    @if (session('lockout_remaining'))
      let remaining = {{ (int) session('lockout_remaining') }};
      const btn = document.getElementById('loginBtn');
      const timerEl = document.getElementById('lockout-timer');

      if (btn) btn.disabled = true;

      function fmt(sec){
        const m = String(Math.floor(sec / 60)).padStart(2,'0');
        const s = String(sec % 60).padStart(2,'0');
        return `${m}:${s}`;
      }
      (function tick(){
        if (timerEl) timerEl.textContent = fmt(remaining);
        if (remaining <= 0) { if (btn) btn.disabled = false; return; }
        remaining--; setTimeout(tick, 1000);
      })();
    @endif
  });

  function togglePassword() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text'; icon.classList.replace('bi-eye-slash','bi-eye');
    } else {
      pwd.type = 'password'; icon.classList.replace('bi-eye','bi-eye-slash');
    }
  }
</script>
@endpush

@extends('components.app')
@section('title', 'POSO Digital Ticket')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/enforcer-login.css')}}">
@endpush

@section('body')

{{-- Include the stylesheet --}}
{{-- @vite('resources/css/enforcer-login.css') --}}
{{-- Or, if you placed it in /public/css --}}
{{-- <link rel="stylesheet" href="{{ asset('css/enforcer-login.css') }}"> --}}

@php
  $isError   = session('error') || $errors->any();
  $remaining = session('lockout_remaining'); // seconds
@endphp

<div class="auth-page">
  {{-- Login Card --}}
  <div class="card auth-card shadow {{ $isError ? 'is-error' : '' }} {{ $remaining ? 'lockout' : '' }}">
    <div class="card-body px-4 py-5">

      <div class="badge-icon mb-2">
        <i class="bi bi-receipt-cutoff display-1 text-success icon-bounce"></i>
      </div>

      {{-- Error Message --}}
      @if(session('error'))
        <div class="alert alert-danger text-center mb-3">{{ session('error') }}</div>
      @endif

      {{-- Top-of-form validation --}}
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
          {{ $errors->first() }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      {{-- Lockout notice --}}
      @if ($remaining)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
          <i class="bi bi-hourglass-split"></i>
          <div>
            Too many failed attempts. You can try again in
            <span id="lockout-timer" class="fw-bold"></span>.
          </div>
        </div>
      @endif

      {{-- Title --}}
      <h3 class="auth-title">Enforcers Login</h3>

      {{-- Form --}}
      <form method="POST" action="{{ route('enforcer.login') }}">
        @csrf

        {{-- Badge Number --}}
        <div class="mb-3">
          <label for="badge_num" class="form-label">Badge No.</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-badge text-success"></i></span>
            <input
              type="text"
              name="badge_num"
              id="badge_num"
              value="{{ old('badge_num') }}"
              class="form-control @error('badge_num') is-invalid @enderror"
              placeholder="Enter your badge number"
              required
            />
          </div>
        </div>

        {{-- Password --}}
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock text-success"></i></span>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control @error('password') is-invalid @enderror"
              placeholder="Enter your password"
              required
            />
            <button
              type="button"
              class="btn btn-outline-secondary"
              onclick="togglePassword()"
              tabindex="-1"
              aria-label="Toggle password visibility"
            >
              <i class="bi bi-eye-slash" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        {{-- Submit --}}
        <button id="loginBtn" type="submit" class="btn btn-login w-100 py-2 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Log in
        </button>

        {{-- Full error list (optional) --}}
        {{-- @if($errors->any())
          <div class="mt-3 px-3 py-2 rounded border border-danger bg-light text-danger">
            <ul class="list-unstyled mb-0">
              @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif --}}

      </form>
    </div>
  </div>
</div>

{{-- Lockout countdown --}}
@if ($remaining)
<script>
(function(){
  let remaining = {{ (int) $remaining }};
  const btn = document.getElementById('loginBtn');
  const timerEl = document.getElementById('lockout-timer');
  if (btn) btn.disabled = true;
  function fmt(sec){ const m=String(Math.floor(sec/60)).padStart(2,'0'); const s=String(sec%60).padStart(2,'0'); return `${m}:${s}`; }
  (function tick(){
    if (timerEl) timerEl.textContent = fmt(remaining);
    if (remaining <= 0){ if (btn) btn.disabled = false; return; }
    remaining--; setTimeout(tick, 1000);
  })();
})();
</script>
@endif

@push('scripts')
<script>
  function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('bi-eye-slash', 'bi-eye'); }
    else { pwd.type = 'password'; icon.classList.replace('bi-eye', 'bi-eye-slash'); }
  }
  // remove the red glow on typing
  document.querySelectorAll('#badge_num, #password').forEach(el=>{
    el.addEventListener('input',()=> el.classList.remove('is-invalid'));
  });
</script>
@endpush
@endsection

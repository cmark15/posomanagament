<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <!-- Mobile-first scaling -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- IE Compatibility Mode -->
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>@yield('title')</title>

  <!-- PWA manifest and service worker registration -->
  <link rel="manifest" href="{{ asset('pwa-manifest.json') }}" crossorigin="use-credentials">

  {{-- Match header color for Android status bar --}}
  <meta name="theme-color" content="#0b8a5d">

  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/serviceworker.js', { scope: '/' });
      });
    }
  </>



  {{-- Your global app styles/scripts --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Enforcer layout polish --}}
  {{-- @vite('resources/css/enforcer-layout.css') --}}
  {{-- Or if you placed it in /public/css --}}
  <link rel="stylesheet" href="{{ asset('css/enforcer-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('css/enforcer-issueTicket.css') }}">
  @stack('styles')
</head>
<body class="app-shell">

  <!-- Navbar -->
  <nav class="navbar navbar-dark enf-navbar px-2">
    <div class="container-fluid">
      @if(auth()->guard('enforcer')->check())
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#enforcerMenu" aria-controls="enforcerMenu" aria-label="Open menu">
          <span class="navbar-toggler-icon"></span>
        </button>
      @endif

      <a class="navbar-brand ms-2 d-flex align-items-center" href="#">
        
        <span>POSO Digital Ticket</span>
      </a>
    </div>
  </nav>

  <!-- Offcanvas Menu -->
  @if(auth()->guard('enforcer')->check())
    <div class="offcanvas offcanvas-start enf-menu" tabindex="-1" id="enforcerMenu" aria-labelledby="enforcerMenuLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="enforcerMenuLabel">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>

      <div class="offcanvas-body">
        <div class="enf-profile">
          <div class="avatar">
            {{ strtoupper(Str::substr(auth()->guard('enforcer')->user()->fname,0,1)) }}
          </div>
          <div>
            <div class="fw-bold">
              {{ auth()->guard('enforcer')->user()->fname }}
              {{ auth()->guard('enforcer')->user()->lname }}
            </div>
            <div class="text-muted small">Badge No: {{ auth()->guard('enforcer')->user()->badge_num }}</div>
          </div>
        </div>

        <a href="#" class="btn btn-outline-success w-100 menu-btn">
          <i class="bi bi-person-badge me-1"></i> Profile
        </a>

        <form class="mt-2" method="POST" action="{{ route('enforcer.logout') }}">
          @csrf
          <button type="submit" class="btn btn-outline-danger w-100 menu-btn">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
          </button>
        </form>
      </div>
    </div>
  @endif

  <main id="app-body">
    @yield('body')
  </main>

  <!-- Libraries -->

  <script src="{{ asset('vendor/dexie/dexie.min.js') }}" defer></script>
  <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" defer></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}" defer></script>
  
  @stack('modals')
  @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title')</title>

  <!-- Bootstrap + Icons -->
  
  <script src="https://kit.fontawesome.com/7922e0fdab.js" crossorigin="anonymous"></script>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('POSO-Logo.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  {{-- Minimalist admin layout CSS --}}
  <link rel="stylesheet" href="{{ asset('css/admin-layout.css')}}">
  <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-ticketTable.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-violationTable.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-enforcerTable.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-violatorTable.css') }}">
  @stack('styles')
</head>
<body>

  <!-- Top Navigation -->
  <header class="top-nav">
    <div class="fw-bold fs-5">
      <a href="/admin/dashboard" class="text-white" style="text-decoration:none" data-ajax>POSO Admin Management</a>
    </div>

    @auth('admin')
      <div class="text-muted-white fw-medium"><span id="currentDateTime">—</span></div>

      <div class="d-flex gap-3 align-items-center">
        <div class="btn-group">
          <button type="button" class="btn btn-success " data-bs-toggle="dropdown" aria-expanded="false">
            Settings
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item text-black" data-ajax href="{{ route('admin.profile.edit') }}">Profile</a></li>
            <li><a class="dropdown-item text-black" data-ajax href="{{route('logs.activity')}}">Activity Log</a></li>
          </ul>
        </div>

        <form id="logoutForm" method="POST" action="{{ route('admin.logout') }}" data-no-ajax>
          @csrf
          <button type="submit" id="logoutBtn" data-no-ajax>
            <i class="fa-solid fa-right-from-bracket"></i>&nbsp;Log out
          </button>
        </form>
      </div>
    @endauth
  </header>

  <div class="half-bg-image">
    <img src="{{ asset('images/icons/POSO-Logo.png') }}" alt="POSO Logo">
  </div>

  <div class="d-flex">
    <!-- Sidebar -->
    @auth('admin')
      @unless(request()->routeIs('admin.profile.edit'))
      <nav class="sidebar d-none d-md-block">
        <img src="{{ asset('images/icons/POSO-Logo.png') }}" alt="POSO Logo">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a data-ajax href="/admin/dashboard" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-columns-gap me-3"></i><span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax href="/ticket" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-receipt-cutoff me-3"></i><span>Ticket</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax data-ajax href="/violation" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-list-check me-3"></i><span>Violation List</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax href="/enforcer" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-person-badge me-3"></i><span>Enforcer List</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax href="/violatorTable" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-person-vcard me-3"></i><span>Violator List</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax href="/dataAnalytics" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-graph-up me-3"></i><span>Data Analytics</span>
            </a>
          </li>
          <li class="nav-item">
            <a data-ajax href="/impoundedVehicle" class="nav-link d-flex align-items-center justify-content-start gap-2 w-100">
              <i class="bi bi-car-front me-3 fs-5"></i><span>Impounded Vehicle</span>
            </a>
          </li>
        </ul>
      </nav>
      @endunless

      {{-- Logout confirm + clock --}}
      <script>
        document.addEventListener('DOMContentLoaded', () => {
          const logoutBtn  = document.getElementById('logoutBtn');
          const logoutForm = document.getElementById('logoutForm');

          if (logoutBtn && logoutForm) {
            logoutBtn.addEventListener('click', function(e) {
              e.preventDefault();
              Swal.fire({
                title: 'Are you sure you want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Stay logged in',
                reverseButtons: true
              }).then((result) => { if (result.isConfirmed) logoutForm.submit(); });
            });
          }

          const fmtOpts = {
            weekday:'long', year:'numeric', month:'long', day:'numeric',
            hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false
          };
          function updateDateTime(){
            const el = document.getElementById('currentDateTime');
            if (!el) return;
            el.textContent = new Date().toLocaleString(undefined, fmtOpts);
          }
          setInterval(updateDateTime, 1000);
          updateDateTime();
        });
      </script>
    @endauth
    <div id="app-body">
        @yield('content')
    </div>

    <div id="ajaxLoading" class="loading-overlay d-none">
      <div class="spinner-border" role="status"></div>
    </div>
  </div>


  {{-- Vendor + app scripts --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
  <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" defer></script>
  <script src="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" defer></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}" defer></script>
  {{-- AJAX navigation (after vendor scripts) --}}
  <script src="{{ asset('js/ajax.js') }}"></script>

  <script src="{{ asset('js/analytics.js') }}"></script>
  <script src="{{ asset('js/enforcer.js') }}"></script>
  <script src="{{ asset('js/ticketTable.js') }}" defer></script>
  {{-- Page js (delegated handlers; pagination; resolve flow) --}}
  <script src="{{ asset('js/impoundedVehicle.js') }}"></script>
  <script src="{{ asset('js/violationTable.js') }}"></script>
  <script defer src="{{ asset('js/violatorPage.js') }}"></script>

  @stack('modals')
  @stack('scripts')
</body>
</html>

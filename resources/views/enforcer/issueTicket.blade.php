@extends('components.app')

@section('title', 'POSO Digital Ticket - Cite Ticket')

@push('head')
  {{-- CSRF meta for JS --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('body')

{{-- ✅ Include the stylesheet --}}
{{-- If you use Vite --}}

{{-- Or, if you placed it in public/css --}}
{{-- <link rel="stylesheet" href="{{ asset('css/issueTicket.css') }}"> --}}

@php
  $allFlags = \App\Models\Flag::whereIn('key',['is_resident','is_impounded'])->get();
  $old      = old('flags', []);
  $v        = $violator;
@endphp

<div class="issue-page">
  <div class="container py-3">
    {{-- Sticky page topbar --}}
    <div class="page-topbar rounded-3 px-2">
      <h1 class="page-title">Traffic Citation Ticket</h1>
      {{-- Queued badge (updates live) --}}
      <button type="button" class="btn btn-outline-secondary me-2" id="queueInfoBtn" title="Offline queue">
        <i class="bi bi-cloud-arrow-down"></i>
        <span class="ms-1">Queued:</span>
        <span class="badge bg-secondary" id="queueCount">0</span>
      </button>

      {{-- Sync Now --}}
      <button type="button" class="btn btn-outline-primary me-2" id="syncNowBtn">
       Sync now
      </button>

      <button type="button" class="btn btn-outline-success ms-auto" data-bs-toggle="modal" data-bs-target="#scanIdModal" id="openScanId">
         Scan ID
      </button>
    </div>

    {{-- Form Card --}}
    <div class="card ticket-card mx-auto mt-3">
      <div class="card-body">
        <form id="ticketForm" action="/enforcerTicket" method="POST" data-check-license-url="{{ route('violators.checkLicense') }}">
          @csrf
          @if(isset($v) && $v?->id)
            <input type="hidden" id="current_violator_id" value="{{ $v->id }}">
          @endif
          <div class="row g-3">
            <input type="hidden" id="client_uuid" name="client_uuid" value="{{ old('client_uuid') }}">
            <input type="hidden" id="enforcer_id" name="enforcer_id" value="{{ auth('enforcer')->id() }}">

            {{-- Name --}}
            <div class="col-6 col-md-6 form-floating">
              <input type="text" class="form-control" id="first_name" name="first_name"
                     placeholder="First Name" value="{{ $v->first_name ?? old('first_name') }}">
              <label for="first_name">First Name</label>
            </div>
            <div class="col-6 col-md-6 form-floating">
              <input type="text" class="form-control" id="middle_name" name="middle_name"
                     placeholder="Middle Name" value="{{ $v->middle_name ?? old('middle_name') }}">
              <label for="middle_name">Middle Name</label>
            </div>
            <div class="col-6 col-md-6 form-floating">
              <input type="text" class="form-control" id="last_name" name="last_name"
                     placeholder="Last Name" value="{{ $v->last_name ?? old('last_name') }}">
              <label for="last_name">Last Name</label>
            </div>

            {{-- License No. --}}
            <div class="col-6 col-md-6 form-floating">
              <input type="text" class="form-control" id="license_num" name="license_num"
                     placeholder="License number" autocomplete="off"
                     value="{{ $v->license_number ?? old('license_num') }}">
              <label for="license_num">License No.</label>
            </div>

            {{-- Address --}}
            <div class="col-12 col-md-6 form-floating">
              <textarea class="form-control" id="address" name="address" placeholder="Full address">{{ $v->address ?? old('address') }}</textarea>
              <label for="address">Address</label>
            </div>

            {{-- Birthdate --}}
            <div class="col-6 col-md-3 form-floating">
              <input type="date" class="form-control" id="birthdate" name="birthdate"
                     placeholder="Birthdate" value="{{ $v->birthdate ?? old('birthdate') }}">
              <label for="birthdate">Birthdate</label>
            </div>

            {{-- Plate No. --}}
            <div class="col-6 col-md-3 form-floating">
              <input type="text" class="form-control" id="plate_num" name="plate_num"
                     placeholder="Plate number" autocomplete="off" value="{{ old('plate_num') }}">
              <label for="plate_num">Plate No.</label>
            </div>

            {{-- Confiscated --}}
            <div class="col-12 col-md-6 form-floating">
              <select class="form-select" id="confiscation_type_id" name="confiscation_type_id">
                <option value="" disabled {{ old('confiscation_type_id')?'':'selected' }}>Choose…</option>
                @foreach(\App\Models\ConfiscationType::all() as $type)
                  <option value="{{ $type->id }}" {{ old('confiscation_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                  </option>
                @endforeach
              </select>
              <label for="confiscation_type_id">Confiscated</label>
            </div>

            {{-- Vehicle Type --}}
            <div class="col-12 col-md-6 form-floating">
              <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                <option value="" disabled selected>Choose…</option>
                @foreach(['Motorcycle','Tricycle','Truck','Sedan','Bus','Jeepney','Van','Closed Van','SUV','Pickup'] as $type)
                  <option>{{ $type }}</option>
                @endforeach
              </select>
              <label for="vehicle_type">Vehicle Type</label>
            </div>

            {{-- Owner & Flags --}}
            <div class="col-12 col-md-6 d-flex justify-content-between align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_owner" name="is_owner" value="1" checked>
                <label class="form-check-label" for="is_owner">Violator is owner</label>
              </div>
            </div>

            {{-- Owner Name --}}
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="owner_name" name="owner_name"
                     placeholder="Owner name" value="{{ old('owner_name') }}">
              <label for="owner_name">Owner Name</label>
            </div>
          </div>

          {{-- Violations --}}
          <h5 class="section-title mt-3">Select Violations</h5>
          <div class="border-0">
            <div class="form-floating mb-3">
              <select class="form-select" id="categorySelect" aria-label="Select violation category">
                <option value="" disabled selected>Choose category…</option>
                @foreach($violationGroups->keys() as $category)
                  <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
              </select>
              <label for="categorySelect">Category</label>
            </div>
            <div id="violationsContainer" class="violations-box"></div>
          </div>

          {{-- Flags & Location --}}
          <div class="row g-3 mt-2">
            <div class="col-12">
              <div class="flags-row">
                @foreach($allFlags as $flag)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="{{ $flag->key }}" name="flags[]" value="{{ $flag->id }}"
                           {{ in_array($flag->id, $old) ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $flag->key }}">{{ $flag->label }}</label>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Brgy, Sitio" required>
              <label for="location">Location of Apprehension</label>
            </div>
          </div>

          <input type="hidden" name="latitude" id="latitude">
          <input type="hidden" name="longitude" id="longitude">

          {{-- Sticky Submit --}}
          <div class="form-actions">
            <button type="submit" class="btn btn-success w-100 btn-lg">Save &amp; Print</button>
          </div>
        </form>

        {{-- JS Globals --}}
        <script>
          window.violationGroups = @json($violationGroups->toArray());
          window.flagsLookup = @json($allFlags->mapWithKeys(fn($f)=>[$f->id => ['key'=>$f->key,'label'=>$f->label]]));
        </script>
      </div>
    </div>
  </div>
</div>

{{-- Scan ID Modal (OCR-only) --}}
<div class="modal fade" id="scanIdModal" tabindex="-1" aria-labelledby="scanIdModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold" id="scanIdModalLabel">Scan Violator ID (OCR)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="scan-close"></button>
      </div>

      <div class="modal-body">
        <style>
          .camera-box{display:grid;place-items:center;width:100%}
          .camera-frame{width:min(92vw,520px);border-radius:14px;overflow:hidden;
            box-shadow:0 8px 24px rgba(0,0,0,.15);background:#000}
          .camera-frame video{width:100%;height:auto;object-fit:contain;background:#000}
          .overlay-guide{position:absolute;inset:0;pointer-events:none;border:2px dashed rgba(255,255,255,.45);
            border-radius:12px;margin:10%}
        </style>

        <div class="camera-box position-relative">
          <div class="camera-frame">
            <video id="ocr-video" playsinline autoplay muted></video>
          </div>
          <div class="overlay-guide d-none d-sm-block"></div>
          <canvas id="ocr-canvas" class="d-none"></canvas>
        </div>

        <div class="d-flex gap-2 mt-3 align-items-center">
          <button class="btn btn-success" id="ocr-capture">Capture & OCR</button>
          <button class="btn btn-outline-secondary" id="ocr-switch">Switch Camera</button>
          <div class="ms-auto" id="ocr-status" style="min-width:160px;"></div>
        </div>

        <!-- Optional debug:
        <div class="form-text mt-2">OCR raw text:</div>
        <pre id="ocr-debug" class="bg-light p-2 small" style="max-height:160px;overflow:auto"></pre> -->
      </div>

      <div class="modal-footer">
        <small class="text-muted">Works offline once cached. Best in bright, even light.</small>
      </div>
    </div>
  </div>
</div>



@if(session('duplicate_error'))
  <script>Swal.fire({
    icon: 'warning',
    title: 'Oops—Duplicate Entry!',
    text: "{{ session('duplicate_error') }}",
    confirmButtonText: 'Understood',
    confirmButtonColor: '#d33',
    background: '#fff5f5',
    color: '#611a15',
    width: 400,
    showCloseButton: true,
    timer: 4000,
    timerProgressBar: true,
  });</script>
@endif

@endsection

@push('scripts')
  <script src="{{ asset('js/issueTicket.js') }}?v={{ filemtime(public_path('js/issueTicket.js')) }}"></script>
  <script src="{{ asset('js/id-scan.js')}}?v={{ filemtime(public_path('js/id-scan.js')) }}"></script>
@endpush
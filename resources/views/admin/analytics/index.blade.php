{{-- resources/views/admin/analytics/index.blade.php --}}
@extends('components.layout')
@section('title', 'POSO Admin Management')

@section('content')
@php
  $latestTicket = \App\Models\Ticket::latest()->first();
  $defaultLat = $latestTicket->latitude ?? 15.9285;
  $defaultLng = $latestTicket->longitude ?? 120.3487;

  // Violation options for the filter (limit to 12 for UI)
  $violationOptions = \App\Models\Violation::select('id','violation_name')
      ->orderBy('violation_name')->limit(12)->get();
@endphp

<script>
  window.DEFAULT_LAT = {{ $defaultLat }};
  window.DEFAULT_LNG = {{ $defaultLng }};
  window.VIOLATION_OPTIONS = @json($violationOptions);
</script>

<style>
  .analytics-toolbar .btn-check:checked + .btn { box-shadow: inset 0 0 0 2px rgba(25,135,84,.35); }
  .heat-legend { font-size:.9rem }
  .heat-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
  .heat-low { background:#a1d99b; }
  .heat-mid { background:#feb24c; }
  .heat-high{ background:#f03b20; }
  #loadingIndicator{
    display:none; position:fixed; top:80px; right:20px; z-index:1040;
    background:#fff; border:1px solid #dee2e6; border-radius:.5rem;
    padding:.5rem .75rem; box-shadow:0 2px 8px rgba(0,0,0,.08);
  }
</style>

<div id="loadingIndicator">
  <div class="d-flex align-items-center gap-2">
    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
    <span>Loading statistics…</span>
  </div>
</div>

<div class="container-fluid mt-4">

  <div class="d-flex flex-wrap align-items-end justify-content-between analytics-toolbar gap-2 mb-3">
    <div class="d-flex flex-wrap align-items-end gap-2">
      <div>
        <label class="form-label small mb-1">From (month)</label>
        <input type="month" id="fromMonth" class="form-control form-control-sm" />
      </div>
      <div>
        <label class="form-label small mb-1">To (month)</label>
        <input type="month" id="toMonth" class="form-control form-control-sm" />
      </div>

      <div class="dropdown">
        <label class="form-label small mb-1 d-block">Violations</label>
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          Select Violations
        </button>
        <div class="dropdown-menu p-2" style="max-height: 260px; overflow:auto; min-width: 260px;">
          <div id="violationFilterList" class="small"></div>
        </div>
      </div>

      <div class="ms-1">
        <label class="form-label small mb-1 d-block">Status</label>
        <input class="btn-check" type="radio" name="statusFilter" id="status_all" value="" checked>
        <label class="btn btn-sm btn-outline-success me-1" for="status_all">All</label>
        <input class="btn-check" type="radio" name="statusFilter" id="status_paid" value="paid">
        <label class="btn btn-sm btn-outline-success me-1" for="status_paid">Paid</label>
        <input class="btn-check" type="radio" name="statusFilter" id="status_unpaid" value="unpaid">
        <label class="btn btn-sm btn-outline-success" for="status_unpaid">Unpaid</label>
      </div>

      <div class="ms-1">
        <button id="applyFilters" class="btn btn-sm btn-primary">
          <i class="bi bi-funnel"></i> Apply
        </button>
        <button id="resetFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
      </div>
    </div>

    <div class="heat-legend text-muted">
      <span class="me-2"><span class="heat-dot heat-low"></span>Low</span>
      <span class="me-2"><span class="heat-dot heat-mid"></span>Medium</span>
      <span><span class="heat-dot heat-high"></span>High</span>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Paid vs Unpaid</div>
        <div class="card-body"><canvas id="pieChart" height="220"></canvas></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Tickets by Month</div>
        <div class="card-body"><canvas id="barChart" height="220"></canvas></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span>Hotspot Map</span>
          <small class="text-muted">Click dots to drill-down</small>
        </div>
        <div class="card-body"><div id="map" style="height:320px;"></div></div>
      </div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('reports.download','xlsx') }}" class="btn btn-success">
      <i class="bi bi-file-earmark-spreadsheet"></i> Monthly Report Excel
    </a>
    <a href="{{ route('reports.download','docx') }}" class="btn btn-primary">
      <i class="bi bi-file-earmark-word"></i> Download Word
    </a>
  </div>
</div>

{{-- Drill-down Modal --}}
<div class="modal fade" id="hotspotModal" tabindex="-1" aria-labelledby="hotspotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="hotspotModalLabel">Hotspot Tickets</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="hotspotModalBody">
          <div class="text-center py-4 text-muted">Loading…</div>
        </div>
      </div>
    </div>
  </div>  
</div>
@endsection

@push('script')
    <script src="{{ asset('js/analytics.js') }}"></script>
@endpush
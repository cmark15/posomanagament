@extends('components.layout')
@section('title', 'POSO Admin Management')

@push('styles')
  <link href="{{ asset('css/admin-impound.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid mt-4" 
     id="impound-page"
     data-resolve-url="{{ route('impound.resolve') }}"
     data-index-url="{{ route('impoundedVehicle.index') }}">
  
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Temporary Impounded Vehicle</h2>
    <div class="text-muted small">
      <i class="bi bi-info-circle me-1"></i>
      Resolve will require an 8-digit reference number.
    </div>
  </div>

  {{-- Impounded table (AJAX-paginates in-place) --}}
  <div id="impoundedTableWrap">
    @include('admin.partials.impoundTable')
  </div>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4 mb-2">
    <h2 class="mb-0">Released Vehicle</h2>
    <span class="badge text-bg-light">
      <i class="bi bi-clock-history me-1"></i> Latest on top
    </span>
  </div>

  {{-- Released table --}}
  <div id="releasedTableWrap">
    @include('admin.partials.releasedVehicle')
  </div>
</div>
@endsection


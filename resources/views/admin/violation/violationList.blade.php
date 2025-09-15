@extends('components.layout')
@section('title', 'POSO Admin Management - Violation List')

@section('content')
<div
  data-page="violation"
  class="container-fluid mt-4"
  id="violations-page"
  data-partial-url="{{ route('violation.partial') }}"
>
  {{-- local loader for partial refreshes --}}
  <div id="vioLoading" class="loading-overlay" style="display:none;">
    <div class="spinner-border" role="status" aria-hidden="true"></div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="m-0">Violation List</h2>
    <a href="{{ route('violation.create') }}" class="btn btn-success" data-ajax>
      <i class="bi bi-list-check"></i> Add Violation
    </a>
  </div>

  {{-- Toolbar --}}
  <form method="GET" id="filterForm" onsubmit="return false;" class="vio-toolbar mb-3">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <label class="col-form-label fw-semibold">Category:</label>
      </div>
      <div class="col-auto">
        <select name="category" class="form-select" id="category_filter">
          <option value="all" {{ $categoryFilter==='all'?'selected':'' }}>All</option>
          @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ $categoryFilter===$cat?'selected':'' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-auto ms-auto">
        <label for="search_input" class="col-form-label fw-semibold">Search:</label>
      </div>
      <div class="col-auto">
        <input type="text" id="search_input" name="search" class="form-control"
               placeholder="Code or name…" value="{{ $search ?? '' }}">
      </div>
      <div class="col-auto">
        <button type="button" id="search_btn" class="btn btn-primary">Go</button>
      </div>
    </div>
  </form>

  {{-- Table Card --}}
  <div class="vio-card">
    <div id="violationContainer">
      @include('admin.partials.violationTable')
    </div>
  </div>

  {{-- IMPORTANT: move the modal inline (not @push) so it exists after SPA swaps --}}
  {{-- @include('admin.modals.editViolation') --}}
  

</div>
@endsection

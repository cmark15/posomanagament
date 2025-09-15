@extends('components.layout')
@section('title', 'POSO Admin Management')

@section('content')
<div class="container-fluid mt-4" id="enforcerContainer"
  data-partial-url="{{ route('enforcer.partial') }}"
  data-page="enforcers">
  <div class="mb-3 d-flex align-items-center justify-content-between">
    <h2 class="m-0">Enforcer List</h2>
    @if($show==='inactive')
      <a href="{{ url('/enforcer?show=active') }}&sort_option={{$sortOption}}&search={{$search}}" class="btn btn-success" data-ajax>
        View Active Enforcers
      </a>
    @else
      <a href="{{ url('/enforcer?show=inactive') }}&sort_option={{$sortOption}}&search={{$search}}" class="btn btn-warning" data-ajax>
        View Inactive Enforcers
      </a>
    @endif
  </div>

  <a href="{{ url('/enforcer/create') }}" id="add-btn" class="btn btn-success mb-3" data-ajax>
    <i class="bi bi-person-plus-fill"></i> Add Enforcer
  </a>

  <form method="GET" id="filterForm" onsubmit="return false;" class="mb-3 enforcer-toolbar">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <label for="sort_table" class="col-form-label fw-semibold">Sort by:</label>
      </div>
      <div class="col-auto">
        <select name="sort" id="sort_table" class="form-select">
          <option value="date_desc" {{ $sortOption==='date_desc'?'selected':'' }}>Date Modified (Newest First)</option>
          <option value="date_asc"  {{ $sortOption==='date_asc'?'selected':''  }}>Date Modified (Oldest First)</option>
          <option value="name_asc"  {{ $sortOption==='name_asc'?'selected':''  }}>Name A → Z</option>
          <option value="name_desc" {{ $sortOption==='name_desc'?'selected':'' }}>Name Z → A</option>
        </select>
      </div>

      <div class="col-auto ms-auto">
        <label for="search_input" class="col-form-label fw-semibold">Search:</label>
      </div>
      <div class="col-auto">
        <input type="text" id="search_input" name="search" class="form-control" placeholder="Code or name…" value="{{ $search ?? '' }}">
      </div>
      <div class="col-auto">
        <button type="button" id="search_btn" class="btn btn-primary">Go</button>
      </div>
    </div>
  </form>

  <div id="enfo-card">
    @include('admin.partials.enforcerTable')
  </div>
</div>
@endsection
@extends('components.layout')
@section('title', 'POSO Admin Management - Issued Tickets')


@section('content')

<div class="container-fluid mt-4" id="ticketContainer"
  data-page="ticket"
  data-paid-status-id="{{ \App\Models\TicketStatus::where('name','paid')->value('id') }}"
  data-status-update-url="{{ url('ticket') }}"
  data-ticket-partial-url="{{ route('ticket.partial') }}">

  {{-- local loader used by ticketTable.js during partial refreshes --}}
  <div class="loading-overlay" id="ticketLoading" style="display:none;">
    <div class="spinner-border" role="status" aria-hidden="true"></div>
  </div>

  {{-- Header / Toolbar --}}
  <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h2 class="mb-1 d-flex align-items-center gap-2">
        Recently Issued Tickets
      </h2>

    </div>

    <div class="d-flex align-items-center gap-2">
      <a href="/ticket/create" class="btn btn-light text-success fw-semibold" data-ajax>
        <i class="bi bi-receipt-cutoff me-2"></i>Cite A Ticket
      </a>
    </div>
  </div>

  {{-- Sort row --}}
  <div id="ticket-sort-form" class="row g-2 mb-3">
    <div class="col-auto">
      <label for="ticket-sort" class="col-form-label fw-semibold">Sort by</label>
    </div>
    <div class="col-auto">
      <select id="ticket-sort" class="form-select">
        <option value="date_desc" {{ $sortOption==='date_desc'?'selected':'' }}>Date (Newest First)</option>
        <option value="date_asc"  {{ $sortOption==='date_asc'?'selected':'' }}>Date (Oldest First)</option>
        <option value="name_asc"  {{ $sortOption==='name_asc'?'selected':'' }}>Violator A → Z</option>
        <option value="name_desc" {{ $sortOption==='name_desc'?'selected':'' }}>Violator Z → A</option>
      </select>
    </div>
  </div>

  {{-- Table card --}}
  <div class="card-shell bg-white">
    <div class="table-responsive" id="ticket-table">
      @include('admin.partials.ticketTable')
    </div>
  </div>

  @php
    $paidStatusId = \App\Models\TicketStatus::where('name','paid')->value('id');
  @endphp

</div>
@endsection

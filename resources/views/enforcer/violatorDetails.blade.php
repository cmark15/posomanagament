@extends('components.app')
@section('title', 'POSO Enforcer Management')

@section('body')
<style>
  /* Fade-in animation */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fade-in {
    animation: fadeInUp 0.7s ease-out;
  }
</style>

{{-- Ensure scrollable container on mobile --}}
<div class="container-fluid my-3 fade-in" style="overflow-y: auto; max-height: calc(100vh - 56px);">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('enforcerTicket.create', ['violator_id' => $violators->id]) }}" class="btn btn-success btn-sm">
      <i class="bi bi-file-earmark-plus-fill me-1"></i>Cite Ticket
    </a>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h2 class="fs-4 mb-4 text-center">Violator Information</h2>
      <div class="row gx-2 gy-2">
        <div class="col-12 d-flex align-items-center">
          <i class="bi bi-person-circle fs-4 me-2"></i>
          <span class="fw-semibold">{{ $violators->first_name }} {{ $violators->middle_name }} {{ $violators->last_name }}</span>
        </div>
        <div class="col-12 d-flex align-items-center">
          <i class="bi bi-credit-card-2-front fs-4 me-2"></i>
          <span class="fw-semibold">License No:</span>&nbsp;<span>{{ $violators->license_number }}</span>
        </div>
        <div class="col-12 d-flex align-items-center">
          <i class="bi bi-geo-alt-fill fs-4 me-2"></i>
          <span>{{ $violators->address }}</span>
        </div>
      </div>
    </div>
  </div>

  <h2 class="fs-4 text-center mb-3"><i class="bi bi-receipt-cutoff-fill me-2"></i>Ticket History</h2>
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Issued</th>
          <th>Location</th>
          <th>Plate #</th>
          <th>Vehicle</th>
          <th>Violation</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($violators->tickets as $ticket)
        <tr>
          <td>{{ $ticket->ticket_number }}</td>
          <td>{{ $ticket->issued_at->format('d M Y, h:i A') }}</td>
          <td>{{ $ticket->location }}</td>
          <td>{{ $ticket->vehicle->plate_number }}</td>
          <td>{{ $ticket->vehicle->vehicle_type }}</td>
          <td>
            @foreach(json_decode($ticket->violation_codes) as $code)
              {{ \App\Models\Violation::where('violation_code', $code)->value('violation_name') }}<br>
            @endforeach
          </td>
          <td>{{ ucfirst($ticket->status->name) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @if($violators->tickets->isEmpty())
      <div class="alert alert-info text-center small mt-3">
        <i class="bi bi-info-circle me-1"></i>No ticket history found.
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  // additional JS can go here
</script>
@endpush

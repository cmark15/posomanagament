@php use App\Models\TicketStatus; @endphp

<div 
  class="container-fluid my-3" 
  id="violatorDetail"
  data-url="{{ route('violatorTable.show', $violator->id) }}"
>
  <a 
    href="{{ route('ticket.create', ['violator_id' => $violator->id]) }}" 
    class="btn btn-success mb-3">
    Add Violation
  </a>

  <h2 class="text-center">Violator’s Information</h2>
  <dl class="row p-3">
    <dt class="col-sm-3">Name</dt>
    <dd class="col-sm-9">{{ $violator->first_name }} {{ $violator->middle_name }} {{ $violator->last_name }}</dd>

    <dt class="col-sm-3">License Number</dt>
    <dd class="col-sm-9">{{ $violator->license_number }}</dd>

    <dt class="col-sm-3">Address</dt>
    <dd class="col-sm-9">{{ $violator->address }}</dd>
  </dl>

  <h3 class="mt-4">Ticket History</h3>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Issued At</th>
          <th>Location</th>
          <th>Violation(s)</th>
          <th>Vehicle Type</th>
          <th>Plate Number</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($violator->tickets as $ticket)
          <tr>
            <td>{{ $ticket->ticket_number }}</td>
            <td>{{ $ticket->issued_at->format('d M Y, H:i') }}</td>
            <td>{{ $ticket->location }}</td>
            <td>
                @foreach(json_decode($ticket->violation_codes) as $code)
                {{ \App\Models\Violation::where('violation_code', $code)
                    ->value('violation_name') }}<br>
              @endforeach
            </td>
            <td>{{ $ticket->vehicle?->vehicle_type ?? 'N/A' }}</td>
            <td>{{ $ticket->vehicle?->plate_number ?? 'N/A' }}</td>
            <td style="min-width:140px;">
              <select
                class="form-select form-select-sm status-select"
                data-ticket-id="{{ $ticket->id }}"
                data-current-status-id="{{ $ticket->status_id }}"
              >
                @foreach(TicketStatus::all() as $status)
                  <option
                    value="{{ $status->id }}"
                    {{ $ticket->status_id == $status->id ? 'selected' : '' }}
                  >
                    {{ ucfirst($status->name) }}
                  </option>
                @endforeach
              </select>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center">No ticket history found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

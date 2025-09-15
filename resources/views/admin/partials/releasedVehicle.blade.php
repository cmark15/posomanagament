<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="releasedTable">
        <thead class="table-light sticky-top">
          <tr class="text-center">
            <th style="width: 120px;">Ticket No.</th>
            <th style="width: 140px;">Reference No.</th>
            <th style="width: 180px;">Released At</th>
            <th>Violator</th>
            <th>Vehicle</th>
            <th style="width: 130px;">Plate No.</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody>
          @forelse($releasedTickets as $t)
            <tr>
              <td class="text-center fw-semibold">{{ $t->ticket_number }}</td>
              <td class="text-center">
                <span class="badge text-bg-success">{{ $t->releasedVehicle->reference_number }}</span>
              </td>
              <td class="text-center">
                {{ optional($t->releasedVehicle->released_at)->format('d M Y, H:i') }}
              </td>
              <td>{{ trim(($t->violator->first_name ?? '').' '.($t->violator->middle_name ?? '').' '.($t->violator->last_name ?? '')) }}</td>
              <td>
                <span class="badge text-bg-secondary">{{ $t->vehicle->vehicle_type ?? ''}}</span>
              </td>
              <td class="text-center">
                <span class="badge text-bg-dark">{{ $t->vehicle->plate_number ?? ''}}</span>
              </td>
              <td class="text-truncate" style="max-width: 240px;" title="{{ $t->location }}">
                {{ $t->location }}
              </td>
            </tr>
          @empty
            <tr class="released-empty">
              <td colspan="7" class="text-center text-muted py-4">No released vehicle(s) yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

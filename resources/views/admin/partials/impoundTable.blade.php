<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0" id="impoundedTable">
        <thead class="table-light sticky-top">
          <tr class="text-center">
            <th style="width: 120px;">Ticket No.</th>
            <th style="width: 160px;">Issued At</th>
            <th>Violator</th>
            <th>Vehicle</th>
            <th style="width: 130px;">Plate No.</th>
            <th>Location</th>
            <th style="width: 120px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tickets as $t)
            <tr class="row-hover"
                data-ticket-id="{{ $t->id }}"
                data-ticket-number="{{ $t->ticket_number }}"
                data-violator="{{ trim(($t->violator->first_name ?? '').' '.($t->violator->middle_name ?? '').' '.($t->violator->last_name ?? '')) }}"
                data-vehicle="{{ $t->vehicle->vehicle_type }}"
                data-plate="{{ $t->vehicle->plate_number }}"
                data-location="{{ $t->location }}"
                data-issued-at="{{ optional($t->issued_at)->format('d M Y, H:i') }}">
              <td class="text-center fw-semibold">{{ $t->ticket_number }}</td>
              <td class="text-center">{{ optional($t->issued_at)->format('d M Y, H:i') }}</td>
              <td>
                <span class="fw-semibold">{{ $t->violator->first_name }} {{ $t->violator->middle_name }} {{ $t->violator->last_name }}</span>
              </td>
              <td>
                <span class="badge text-bg-secondary">{{ $t->vehicle->vehicle_type }}</span>
              </td>
              <td class="text-center">
                <span class="badge text-bg-dark">{{ $t->vehicle->plate_number }}</span>
              </td>
              <td class="text-truncate" style="max-width: 240px;" title="{{ $t->location }}">
                {{ $t->location }}
              </td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm btn-resolve" type="button" title="Resolve & Release">
                  <i class="bi bi-unlock"></i> Resolve
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No impounded vehicle(s) found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-footer bg-white">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="small text-muted">
        Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}
      </div>
      <nav class="pagination-wrapper">
        {{-- We will AJAX these links in-place --}}
        {{ $tickets->onEachSide(1)->links('pagination::bootstrap-5') }}
      </nav>
    </div>
  </div>
</div>

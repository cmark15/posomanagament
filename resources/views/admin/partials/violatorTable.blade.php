<div id="vtr-table-wrap" class="table-responsive">
<table class="table table-bordered table-hover vtr-table">
  <thead>
    <tr>
      <th class="text-center col-ticket">Ticket #</th>
      <th class="col-name">Name</th>
      <th class="col-address col-lg-only">Address</th>
      <th class="text-center col-license col-md-only">License #</th>
      <th class="text-center col-vehicle">Vehicle Type</th>
      <th class="text-center col-plate col-md-only">Plate #</th>
      <th class="col-violate">Recent Violation</th>
      <th class="text-center col-actions">Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse($tickets as $t)
        <tr>
          <td class="text-center text-nowrap">{{ $t->ticket_number }}</td>
          {{-- Name (fix middle/last duplication; show M. if present) --}}
          @php
            $mi = trim($t->violator->middle_name ?? '');
            $mi = $mi ? mb_substr($mi,0,1).'.' : '';
          @endphp
          <td class="col-name">
            {{ trim(($t->violator->first_name ?? '').' '.$mi.' '.($t->violator->last_name ?? '')) }}
          </td>
           <td class="col-address col-lg-only">
            <span class="text-break">{{ $t->violator->address }}</span>
          </td>
          <td class="text-center col-license col-md-only">
            <span class="badge-soft">{{ $t->violator->license_number }}</span>
          </td>

          <td class="text-center col-vehicle">
            <span class="chip">{{ $t->vehicle?->vehicle_type ?? '—' }}</span>
          </td>
          <td class="text-center col-plate col-md-only">
            <span class="badge-soft">{{ $t->vehicle?->plate_number ?? '—' }}</span>
          </td>

          <td class="col-violate">
            <span class="truncate-2">{{ $t->violation_names }}</span>
          </td>

          {{-- Actions --}}
          <td class="text-center col-actions">
            <a href="#"
               class="btn btn-info btn-sm text-white view-tickets-btn"
               data-violator-id="{{ $t->violator->id }}"
               data-violator-name="{{ $t->violator->name ?? '' }}"
               data-url="{{ route('violatorTable.show', $t->violator->id) }}">
               View More
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center">No violator(s) found.</td>
        </tr>
      @endforelse
  </tbody>
</table>

{{-- Pagination --}}
  <div class="vtr-pager d-flex justify-content-between align-items-center mt-3">
    <div class="d-none d-md-block small text-muted">
      Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} of {{ $tickets->total() }} results
    </div>
    <div class="w-100 d-flex justify-content-center">
      {{ $tickets->links() }}
    </div>
  </div>
</div>
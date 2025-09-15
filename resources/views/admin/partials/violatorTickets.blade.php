<div id="violator-table">
<div class="table-responsive">
    @if($active->isEmpty())
        <p>No active tickets.</p>
    @else
    <table class="table table-sm table-bordered table-hover">
      <thead>
        <tr>
          <th class="text-center text-sm">#</th>
          <th class="text-center text-sm">Issued</th>
          <th class="text-center text-sm">Plate</th>
          <th class="text-center text-sm">Vehicle</th>
          <th class="text-center text-sm">Violation</th>
          <th class="text-center text-sm">Status</th>
        </tr>
      </thead>
      <tbody class="text-sm">
        @foreach($active as $t)
          <tr>
            <td>{{ $t->id }}</td>
            <td>{{ $t->issued_at->format('d M Y') }}</td>
            <td>{{ optional($t->vehicle)->plate_number ?? '—' }}</td>
            <td>{{ optional($t->vehicle)->vehicle_type ?? '—' }} </td>
            <td>
              {{ $t->violation_names }}
            </td>
            <td>
              <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                {{ optional($t->status)->name ?? 'Unknown' }}
            </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
</div>
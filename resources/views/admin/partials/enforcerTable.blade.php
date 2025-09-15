<div class="table-responsive">
  <table class="table table-hover align-middle enforcer-table">
    <thead class="table-light">
      <tr>
        <th class="text-center">Badge</th>
        <th class="text-center">Full Name</th>
        <th class="text-center col-md-only">Phone</th>
        <th class="text-center col-md-only">Ticket Range</th>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($enforcer as $e)
        <tr class="{{ $e->trashed() ? 'table-secondary' : '' }}">
          <td class="text-center">{{ $e->badge_num }}</td>
          <td class="text-center">{{ $e->lname }}, {{ $e->fname }} {{ $e->mname }}</td>
          <td class="text-center col-md-only">{{ $e->phone }}</td>
          <td class="text-center col-md-only">{{ $e->ticket_start }} - {{ $e->ticket_end }}</td>
          <td class="text-center table-actions">
            <a href="#"
               class="btn btn-warning btn-sm editBtn"
               data-bs-toggle="modal"
               data-id="{{ $e->id }}"
               data-url="{{ route('enforcer.update', $e) }}"
               data-badge="{{ $e->badge_num }}"
               data-fname="{{ $e->fname }}"
               data-mname="{{ $e->mname }}"
               data-lname="{{ $e->lname }}"
               data-phone="{{ $e->phone }}"
               data-ticket-start="{{ $e->ticket_start }}"
               data-ticket-end="{{ $e->ticket_end }}"
            >Edit</a>

            @if(!$e->trashed())
              <form action="{{ route('enforcer.destroy',$e) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button type="button"
                        class="btn btn-danger btn-sm status-btn"
                        data-action="{{ route('enforcer.destroy',$e) }}"
                        data-method="DELETE">
                  Inactivate
                </button>
              </form>
            @else
              <form action="{{ route('enforcer.restore',$e) }}" method="POST" class="d-inline">
                @csrf
                <button type="button"
                        class="btn btn-success btn-sm status-btn"
                        data-action="{{ route('enforcer.restore',$e) }}"
                        data-method="POST">
                  Activate
                </button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center">No enforcers found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="d-flex justify-content-center mt-3">
    {{ $enforcer->appends(['show'=>$show,'sort_option'=>$sortOption,'search'=>$search])->links() }}
  </div>
</div>

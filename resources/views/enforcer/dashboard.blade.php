@extends('components.app')

@section('title', 'POSO Digital Ticket - Dashboard')

@section('body')
<div class="container-fluid bg-light min-vh-100 p-3">
  <!-- Enforcer's Information Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row gx-2 gy-2">
        <div class="col-12 d-flex align-items-center">
          <i class="bi bi-person-circle fs-4 me-2"></i>
          {{-- Enforcer's Full Name --}}
          <span class="fw-semibold">Name: {{ auth()->guard('enforcer')->user()->fname }} {{ auth()->guard('enforcer')->user()->lname }} </span> 
        </div>
        <div class="col-12 d-flex align-items-center">
          <i class="bi-ticket fs-4 me-2"></i>
          {{-- Enforcer's Ticket Range (But include the used to ticket number) --}}
          <span class="fw-semibold">Badge No.: {{ auth()->guard('enforcer')->user()->badge_num }}</span>
        </div>
        <div class="col-12 d-flex align-items-center">
          {{-- Enforcer's Address--}}
          <i class="bi bi-geo-alt-fill fs-4 me-2"></i>
          <span class="fw-semibold">Address:</span>
        </div>
      </div>
    </div>
  </div>
  <!-- Search Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-white border-0">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input
          id="violator-search"
          type="text"
          class="form-control border-0 rounded-end-pill"
          placeholder="Search violators..."
          autocomplete="off"
          aria-label="Search violators"
        >
        <button
          id="search-clear"
          class="btn btn-outline-secondary ms-1 rounded-pill"
          type="button"
          disabled
          aria-label="Clear search"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <small class="text-muted d-block text-center mt-3">
        Search by name · plate number · license number
      </small>
      <div
        id="suggestions"
        class="list-group position-relative mt-2"
        style="z-index:1000; max-height:250px; overflow-y:auto;"
      ></div>
    </div>
  </div>

  <!-- Issue Ticket Button -->
  <div class="text-center mb-5">
    <a
      href="{{ url('/enforcerTicket/create') }}"
      class="btn btn-success btn-lg shadow-sm rounded-pill"
    >
      <i class="bi bi-ticket-perforated-fill me-2"></i>
      Issue a Ticket
    </a>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input       = document.getElementById('violator-search');
  const suggestions = document.getElementById('suggestions');
  const clearBtn    = document.getElementById('search-clear');
  const url         = "{{ route('enforcer.violators.suggestions') }}";
  let timer;

  clearBtn.addEventListener('click', () => {
    input.value = '';
    clearBtn.disabled = true;
    suggestions.innerHTML = '';
    input.focus();
  });

  input.addEventListener('input', () => {
    const term = input.value.trim();
    clearBtn.disabled = (term === '');
    clearTimeout(timer);

    if (term.length < 2) {
      suggestions.innerHTML = '';
      return;
    }

    timer = setTimeout(() => {
      fetch(`${url}?q=${encodeURIComponent(term)}`, { credentials: 'same-origin' })
        .then(res => {
          if (!res.ok) throw new Error(res.status);
          return res.json();
        })
        .then(data => {
          suggestions.innerHTML = '';

          if (data.length === 0) {
            const noItem = document.createElement('div');
            noItem.className = 'list-group-item list-group-item-warning text-center';
            noItem.textContent = `No "${term}" violator found`;
            suggestions.appendChild(noItem);
            return;
          }

          data.forEach(v => {
            // build the full name, skipping empty middle
            const fullName = [v.first_name, v.middle_name, v.last_name]
                               .filter(n => n && n.trim())
                               .join(' ');

            const a = document.createElement('a');
            a.href      = `/violators/${v.id}`;
            a.className = 'list-group-item list-group-item-action';
            a.innerHTML = `
              <strong>${fullName}</strong><br>
              <small class="text-muted">
                ${v.license_number} &middot; ${v.plate_number || '—'}
              </small>
            `;
            suggestions.appendChild(a);
          });
        })
        .catch(err => {
          suggestions.innerHTML = `
            <div class="list-group-item text-danger text-center">
              Error fetching results (${err.message || err})
            </div>
          `;
        });
    }, 300);
  });

  // close suggestions when clicking elsewhere
  document.addEventListener('click', e => {
    if (!suggestions.contains(e.target) && e.target !== input) {
      suggestions.innerHTML = '';
    }
  });
});
</script>
@endpush
@endsection

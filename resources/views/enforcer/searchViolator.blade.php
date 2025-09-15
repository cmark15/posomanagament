@extends('components.app')
@section('title', 'POSO Digital Ticket - Search Violator')
@section('body')
<div class="container-fluid p-3">
    <form method="GET" action="{{ route('enforcerTicket.index') }}" class="mb-3">
      <div class="input-group">
        <input type="search"
               name="q"
               value="{{ $q }}"
               class="form-control"
               placeholder="Search by name, license or plate...">
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>
  
    @if($q === '')
      <p class="text-muted">Enter a name, license number, or plate to search.</p>
    @elseif($violators->isEmpty())
      <div class="alert alert-warning">No violators found for "{{ $q }}".</div>
    @else
      <div class="list-group mb-3">
        @foreach($violators as $v)
          <a href="{{ route('enforcerTicket.show', $v) }}" class="list-group-item list-group-item-action">
            <h5 class="mb-1">{{ $v->name }} <small class="text-muted">({{ $v->license_number }})</small></h5>
            <p class="mb-1">
              @foreach($v->vehicles as $veh)
                <span class="badge bg-secondary">Plate: {{ $veh->plate_number }}</span>
              @endforeach
            </p>
          </a>
        @endforeach
      </div>
      {{ $violators->links() }}
    @endif
  </div>
@endsection
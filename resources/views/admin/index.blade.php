@extends('components.layout')

@section('title', 'POSO Admin Management')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
@endpush

@section('content')
<div id="admin-dashboard" class="container-fluid py-4">
    <!-- Summary Cards -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <a href="{{ url('/ticket') }}" class="text-decoration-none" data-ajax>
          <div class="card text-white bg-primary h-100">
            <div class="card-body d-flex align-items-center">
              <i class="bi bi-receipt-cutoff display-4 me-3"></i>
              <div>
                <h6 class="card-title">Total Issued Ticket</h6>
                <h2>{{ $ticketCount }}</h2>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-4">
        <a href="{{ url('/violatorTable') }}" class="text-decoration-none" data-ajax>
        <div class="card text-white bg-warning h-100">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-person-vcard display-4 me-3"></i>
            <div>
              <h6 class="card-title">Total Violator(s)</h6>
              <h2>{{ $violatorCount }}</h2>
            </div>
          </div>
        </div>
        </a>
      </div>
    </div>

    {{-- <div class="row mt-3 ">
        <div class="col-md-4">
        <a href="{{ url('/violatorTable') }}" class="text-decoration-none" data-ajax>
        <div class="card text-white bg-info h-100">
          <div class="card-body d-flex align-items-center">
            <i class="bi bi-person-vcard display-4 me-3"></i>
            <div>
              <h6 class="card-title">Total Impounded Vehicle</h6>
              <h2>{{ $violatorCount }}</h2>
            </div>
          </div>
        </div>
        </a>
      </div>
    </div>
   --}}
    <!-- Recent Violators & Tickets -->
    <div class="row">
      <!-- Recent Violators -->
      <div class="col-lg-6 mb-4 mt-3">
        <div class="card h-100 shadow-sm">
          <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0">
                <i class="bi bi-person-lines-fill me-2"></i>
                Recent Violators
            </h5>
            <a href="{{ url('/violatorTable') }}" class="small text-decoration-none text-left" data-ajax>
                View more
              </a>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>License #</th>
                  <th>Issued</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentViolators as $v)
                  <tr>
                    <td>{{ $v->first_name }} {{ $v->middle_name }} {{ $v->last_name }}</td>
                    <td>{{ $v->license_number }}</td>
                    <td>{{ $v->created_at->format('d M Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center py-3">No violators yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
  
      <!-- Recent Tickets -->
      <div class="col-lg-6 mb-4 mt-3">
        <div class="card h-100 shadow-sm">
          <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0">
                <i class="bi bi-receipt-cutoff me-2"></i>
                Recent Ticket
            </h5>
            <a href="{{ url('/ticket') }}" class="small text-decoration-none text-left" data-ajax>
                View more
              </a>
          </div>
          <div class="card-body p-0">
            <table class="table table-striped mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Violator</th>
                  <th>Enforcer</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentTickets as $t)
                  <tr>
                    <td>{{ $t->ticket_number }}</td>
                    <td>{{ $t->violator->first_name }} {{ $t->violator->middle_name }} {{ $t->violator->last_name }}</td>
                    <td>{{ $t->enforcer->fname.' '.$t->enforcer->lname }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->issued_at)->format('d M Y, g:i A') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center py-3">No tickets issued.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
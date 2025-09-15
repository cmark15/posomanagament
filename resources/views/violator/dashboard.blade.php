@extends('components.violator')
@section('title', 'POSO Digital Ticket')

@section('violator')
@php $ticketOverdue = $ticket_overdue ?? false; @endphp

<div id="violator-dashboard"
     class="container-fluid"
     data-login-success="{{ $loginSuccess ? '1' : '0' }}"
     data-ticket-overdue="{{ $ticketOverdue ? '1' : '0' }}">

  {{-- Profile / Greeting --}}
  <div class="violator-card mt-3">
    <h4 id="greeting-text" class="mb-2 fw-bold"></h4>
    <p class="mb-0">
      <i class="bi bi-person-circle me-2"></i>
      {{ $violator->first_name }} {{ $violator->middle_name }} {{ $violator->last_name }}<br>
      <i class="bi bi-card-heading me-2"></i>
      License #: {{ $violator->license_number }} <br>
      <i class="bi bi-geo-alt me-2"></i>
      Address: {{ $violator->address }}
    </p>
  </div>

  {{-- Unsettled --}}
  <div class="dashboard-header mt-3">
    <i class="bi bi-ticket-perforated"></i>
    <h2 class="mb-0 fs-5">Unsettled Ticket(s)</h2>
  </div>

  {{-- Replace your partial with this improved version if you want consistent styling --}}
  @include('admin.partials.violatorTickets') {{-- keep if you already styled it similarly --}}

  {{-- Paid --}}
  <div class="dashboard-header mt-4">
    <i class="bi bi-check-circle"></i>
    <h2 class="mb-0 fs-5">Paid Ticket(s)</h2>
  </div>

  <div class="table-toolbar mt-2">
    <div class="flex-grow-1">
      <input id="paidSearch" type="search" class="form-control" placeholder="Search paid tickets…">
    </div>
    <div>
      <select id="paidShow" class="form-select">
        <option value="5">Show 5</option>
        <option value="10" selected>Show 10</option>
        <option value="25">Show 25</option>
      </select>
    </div>
  </div>

  <div class="table-wrap">
    @if($completed->isEmpty())
      <p class="text-muted mt-3">No paid tickets yet.</p>
    @else
      <div class="table-responsive">
        <table id="paidTable" class="table table-sm table-hover table-bordered smart-table align-middle">
          <thead class="table-light">
            <tr>
              <th class="text-center">#</th>
              <th class="text-center">Issued</th>
              <th class="text-center">Plate</th>
              <th class="text-center">Vehicle</th>
              <th class="text-center">Violation</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($completed as $t)
              <tr>
                <td data-label="#" class="text-center">{{ $t->id }}</td>
                <td data-label="Issued">{{ $t->issued_at->format('d M Y') }}</td>
                <td data-label="Plate">{{ optional($t->vehicle)->plate_number ?? '—' }}</td>
                <td data-label="Vehicle">{{ optional($t->vehicle)->vehicle_type ?? '—' }}</td>
                <td data-label="Violation">{{ $t->violation_names }}</td>
                <td data-label="Status" class="text-center">
                  <span class="status-chip status-paid">
                    <i class="bi bi-check2-circle"></i>
                    {{ optional($t->status)->name ?? 'Paid' }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

{{-- Floating Help Button + Modal (unchanged markup) --}}
<button id="helpFab" class="help-fab btn btn-success" type="button"
        data-bs-toggle="modal" data-bs-target="#helpModal" aria-label="Open Help and FAQs">
  <i class="bi bi-question-circle fs-6"></i>
  <span>Help</span>
  <span class="fab-pulse" aria-hidden="true"></span>
</button>

<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" data-bs-theme="light">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title d-flex align-items-center gap-2" id="helpModalLabel">
          <i class="bi bi-life-preserver"></i> Help & FAQs
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info d-flex align-items-start gap-3">
          <i class="bi bi-info-circle fs-4"></i>
          <div>
            <div class="fw-semibold">Need immediate assistance?</div>
            <div class="small">Visit the POSO Office or contact support.</div>
            <div class="mt-2 d-flex flex-wrap gap-2">
              <a href="tel:+63-961-281-2756" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-telephone"></i> 0961-281-2756
              </a>
              <a href="mailto:poso@gmail.com" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-envelope"></i> poso@gmail.com
              </a>
            </div>
          </div>
        </div>

        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="faq1h">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                      data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                Where can I pay my traffic ticket?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" aria-labelledby="faq1h" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Pay at the <strong>Cashier Office, Municipal Hall</strong>, San Carlos City, Pangasinan.
                Bring your ticket number and a valid ID. Office hours: <span id="officeHours">Mon–Fri, 8:00 AM–5:00 PM</span>.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="faq2h">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                What do I need to bring when paying?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faq2h" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Bring your <strong>ticket number</strong>, a <strong>valid ID</strong>, and exact amount.
                If your license was confiscated, bring the claim stub or any document provided by the enforcer.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="faq3h">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                Can I pay online?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faq3h" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Online payment is currently <strong>not available</strong>. Please pay at the Cashier Office.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header" id="faq4h">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                How do I view my violations and ticket status?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" aria-labelledby="faq4h" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Use the <strong>login credentials</strong> printed on your ticket to sign in to the Violator Portal.
                From your dashboard, you can see your tickets and status updates.
              </div>
            </div>
          </div>
        </div> {{-- /accordion --}}
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/violator.js') }}"></script>
@endpush
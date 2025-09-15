<div class="container-fluid py-4">
    <h2>Add Enforcer</h2>
    <a href="{{url('/enforcer')}}" data-ajax
      class="btn btn-outline-secondary confirm-back">
      <i class="bi bi-arrow-left"></i> Back
    </a>

  <div class="card mx-auto shadow">

    <form id="addEnforcerForm" action="{{ route('enforcer.store') }}" method="POST">
      @csrf
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-6">
            <label for="badge_num" class="form-label">Badge No.</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
              <input type="text" id="badge_num" name="badge_num" class="form-control"
                     maxlength="3" value="{{ old('badge_num', $nextBadgeNum ?? '') }}">
            </div>
          </div>

          <div class="col-md-6">
            <label for="fname" class="form-label">First Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="fname" name="fname" class="form-control"
                     value="{{ old('fname') }}" required>
            </div>
          </div>

          <div class="col-md-6">
            <label for="mname" class="form-label">Middle Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="mname" name="mname" class="form-control" value="{{ old('mname') }}">
            </div>
          </div>

          <div class="col-md-6">
            <label for="lname" class="form-label">Last Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="lname" name="lname" class="form-control"
                     value="{{ old('lname') }}" required>
            </div>
          </div>

          <div class="col-md-6">
            <label for="phone" class="form-label">Phone</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-phone"></i></span>
              <input type="tel" id="phone" name="phone" class="form-control"
                     inputmode="numeric" pattern="\d{11}"
                     value="{{ old('phone') }}" required>
            </div>
            <div class="form-text">11-digit number (e.g., 09xxxxxxxxx)</div>
          </div>

          <div class="col-md-3">
            <label for="ticket_start" class="form-label">Ticket Start</label>
            <input type="text" id="ticket_start" name="ticket_start" class="form-control"
                   value="{{ old('ticket_start', $nextStart ?? '') }}"
                   pattern="\d{3}" maxlength="3" placeholder="e.g. 001" readonly>
            <div class="form-text">3-digit only</div>
          </div>

          <div class="col-md-3">
            <label for="ticket_end" class="form-label">Ticket End</label>
            <input type="text" id="ticket_end" name="ticket_end" class="form-control"
                   value="{{ old('ticket_end', $nextEnd ?? '') }}"
                   pattern="\d{3}" maxlength="3" placeholder="e.g. 100" readonly>
            <div class="form-text">3-digit only, ≥ start</div>
          </div>

          <div class="col-md-6">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
              <input type="text" id="password" name="password" class="form-control"
                     minlength="8" maxlength="20"
                     value="{{ old('password') }}" required>
              <button type="button" class="btn btn-outline-secondary" id="btnGenPwd">Generate</button>
            </div>
            <div class="form-text">Min 8 characters.</div>
          </div>

        </div>
      </div>

      <div class="card-footer text-end">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-person-plus me-1"></i> Add Enforcer
        </button>
      </div>

    </form>
  </div>
</div>
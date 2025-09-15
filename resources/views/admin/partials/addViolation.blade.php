<div class="container-fluid py-4">
    <!-- Back Button -->
    <h2 class="mb-3">Add Violation</h2>
    <div class="mb-4">
        {{-- <button type="button"
                class="btn btn-outline-secondary"
                id="previousBtn"
                data-back="{{ url('/violation') }}">
                <i class="bi bi-arrow-left"></i> Back
        </button> --}}
        <a href="/violation"
          data-ajax
          class="btn btn-outline-secondary"
          id="previousBtn">
          <i class="bi bi-arrow-left"></i>Back
        </a>
    </div>
  
    <!-- Violation Form Card -->
    <div class="card mx-auto shadow" style="max-width: 800px;">
  
      <form action="{{ url('/violation') }}" method="POST" id="violationForm">
        @csrf
        <div class="card-body">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
  
          <div class="row g-3">
            <div class="col-md-6">
              <label for="violation_code" class="form-label">Violation Code</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                <input type="text" id="violation_code" name="violation_code" class="form-control" value="{{ old('violation_code', $nextViolation ?? '') }}">
              </div>
            </div>
  
            <div class="col-md-6">
              <label for="violation_name" class="form-label">Violation Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" id="violation_name" name="violation_name" class="form-control" value="{{ old('violation_name') }}">
              </div>
            </div>
  
            <div class="col-md-6">
              <label for="fine_amount" class="form-label">Fine Amount</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" id="fine_amount" name="fine_amount" step="0.01" class="form-control" value="{{ old('fine_amount') }}">
              </div>
            </div>
  
            <div class="col-md-6">
              <label for="category" class="form-label">Category</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-filter-square"></i></span>

                    <select class="form-select" name="category" id="category">
                        <option value="" disabled selected>Choose category…</option>
                        <option value="Moving Violations">Moving Violations</option>
                        <option value="Non-Moving Violations">Non-Moving Violations</option>
                        <option value="Safety Violations">Safety Violations</option>
                        <option value="Parking Violations">Parking Violations</option>
                    </select>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white text-end">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-person-plus me-1"></i> Add Violation
          </button>
        </div>
      </form>

    </div>
  </div>

  @extends('components.layout')
  @section('title', 'POSO Admin Management')

  @section('content')

  <style>
      .ticket-card {
        max-height: 70vh;       /* or however tall you want */
        overflow-y: auto;
      }
  </style>
  @php
    // flags via pivot (same as Enforcer)
    $allFlags = \App\Models\Flag::whereIn('key',['is_resident','is_impounded'])->get();
    $oldFlags = old('flags', []);
  @endphp

  <div class="container py-4">
    <h1 class="h4 text-center mb-4">Traffic Citation Ticket (Admin)</h1>

    <div class="card mx-auto shadow-sm rounded-3 ticket-card" style="max-width: 800px;">
      <div class="card-body p-3 p-sm-4">
        {{-- Pass groups to JS --}}
        <script> window.violationGroups = @json($violationGroups->toArray()); </script>

        <form id="ticketForm" action="{{ route('admin.tickets.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            {{-- First/Middle/Last name (match Violator schema used by Enforcer flow) --}}
            <div class="col-12 col-md-4 form-floating">
              <input type="text" class="form-control" id="first_name" name="first_name"
                    placeholder="First Name" value="{{ old('first_name') }}">
              <label for="first_name">First Name</label>
            </div>
            <div class="col-12 col-md-4 form-floating">
              <input type="text" class="form-control" id="middle_name" name="middle_name"
                    placeholder="Middle Name" value="{{ old('middle_name') }}">
              <label for="middle_name">Middle Name</label>
            </div>
            <div class="col-12 col-md-4 form-floating">
              <input type="text" class="form-control" id="last_name" name="last_name"
                    placeholder="Last Name" value="{{ old('last_name') }}">
              <label for="last_name">Last Name</label>
            </div>

            {{-- License No. --}}
            <div class="col-12 col-md-4 form-floating">
              <input type="text" class="form-control" id="license_num" name="license_num"
                    placeholder="License number" autocomplete="off" value="{{ old('license_num') }}">
              <label for="license_num">License No.</label>
            </div>

            {{-- Address --}}
            <div class="col-12 col-md-8 form-floating">
              <textarea class="form-control" id="address" name="address"
                        placeholder="Full address" style="height: 3rem">{{ old('address') }}</textarea>
              <label for="address">Address</label>
            </div>

            {{-- Birthdate --}}
            <div class="col-6 col-md-4 form-floating">
              <input type="date" class="form-control" id="birthdate" name="birthdate"
                    placeholder="Birthdate" value="{{ old('birthdate') }}">
              <label for="birthdate">Birthdate</label>
            </div>

            {{-- Plate No. --}}
            <div class="col-6 col-md-4 form-floating">
              <input type="text" class="form-control" id="plate_num" name="plate_num"
                    placeholder="Plate number" autocomplete="off" value="{{ old('plate_num') }}">
              <label for="plate_num">Plate No.</label>
            </div>

            {{-- Vehicle Type --}}
            <div class="col-12 col-md-4 form-floating">
              <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                <option value="" disabled selected>Choose…</option>
                @foreach(['Motorcycle','Tricycle','Truck','Sedan','Bus','Jeepney','Van','Closed Van','SUV','Pickup'] as $type)
                  <option>{{ $type }}</option>
                @endforeach
              </select>
              <label for="vehicle_type">Vehicle Type</label>
            </div>

            {{-- Confiscation Type (same model as Enforcer flow) --}}
            <div class="col-12 col-md-6 form-floating">
              <select class="form-select" id="confiscation_type_id" name="confiscation_type_id">
                <option value="" disabled {{ old('confiscation_type_id')?'':'selected' }}>Choose…</option>
                @foreach(\App\Models\ConfiscationType::all() as $type)
                  <option value="{{ $type->id }}" {{ old('confiscation_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                  </option>
                @endforeach
              </select>
              <label for="confiscation_type_id">Confiscated</label>
            </div>

            {{-- Owner Name --}}
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="owner_name" name="owner_name"
                    placeholder="Owner name" value="{{ old('owner_name') }}">
              <label for="owner_name">Owner Name</label>
            </div>
          </div>

            {{-- Owner / Resident flags (Resident now via flags[] pivot) --}}
            <div class="col-12 col-md-6 form-floating">
              <div class="form-check">
                
              </div>
            </div>

          {{-- Flags (Resident / Impounded) using the same Flags table --}}
          <div class="mt-3 d-flex flex-wrap align-items-center">
            @foreach($allFlags as $flag)
              <div class="form-check form-check-inline">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="{{ $flag->key }}"
                  name="flags[]"
                  value="{{ $flag->id }}"
                  {{ in_array($flag->id, $oldFlags) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="{{ $flag->key }}">{{ $flag->label }}</label>
              </div>
            @endforeach
            <input class="form-check-input" type="checkbox" id="is_owner" name="is_owner" value="1" checked>
            <label class="form-check-label" for="is_owner">Violator is owner</label>
          </div>

          {{-- Violations --}}
          <div class="mt-4">
            <h5 class="mb-2">Select Violations</h5>
            <div class="border rounded p-3">
              <div class="form-floating mb-3">
                <select class="form-select" id="categorySelect" aria-label="Select violation category">
                  <option value="" disabled selected>Choose category…</option>
                  @foreach($violationGroups->keys() as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                  @endforeach
                </select>
                <label for="categorySelect">Category</label>
              </div>
              <div id="violationsContainer" class="px-1 overflow-auto" style="max-height:250px;"></div>
            </div>
          </div>

          {{-- Location + hidden geo (optional on desktop) --}}
          <div class="row g-3 mt-3">
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="location" name="location"
                    placeholder="e.g. Brgy, Sitio" required>
              <label for="location">Location of Apprehension</label>
              
            </div>

            {{-- Select which Enforcer this ticket will be issued under (consumes their range) --}}
              <div class="col-12 col-md-6 form-floating">
                <select class="form-select" id="apprehending_enforcer_id" name="apprehending_enforcer_id" required>
                  <option value="" disabled selected>Choose…</option>
                  @foreach($enforcers as $e)
                    <option value="{{ $e->id }}">
                      {{ $e->badge_num }} — {{ $e->lname }}, {{ $e->fname }}
                    </option>
                  @endforeach
                </select>
                <label for="apprehending_enforcer_id">Apprehending Enforcer</label>
              </div>
          </div>
          <input type="hidden" name="latitude" id="latitude">
          <input type="hidden" name="longitude" id="longitude">

          <div class="mt-4">
            <button type="submit" class="btn btn-primary w-100 btn-lg">Save &amp; Print</button>
          </div>
        </form>

        @if(session('duplicate_error'))
          <script>Swal.fire({
            icon: 'warning',
            title: 'Oops—Duplicate Entry!',
            text: "{{ session('duplicate_error') }}",
            confirmButtonText: 'Understood',
            confirmButtonColor: '#d33',
            background: '#fff5f5',
            color: '#611a15',
            width: 400,
            showCloseButton: true,  
            timer: 4000,
            timerProgressBar: true,
          });</script> 
        @endif

      </div>
    </div>
  </div>
  @endsection

  {{-- Scripts --}}
  @push('scripts')
    <script src="{{ asset('js/adminIssueTicket.js') }}"></script>
  @endpush
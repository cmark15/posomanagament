 <style>
         .ticket-card {
            max-height: 80vh;       /* or however tall you want */
            overflow-y: auto;
        }
    </style>
@php $v = $violator; @endphp
  
  <div class="container py-4">
    {{-- Page Title --}}
    <h1 class="h4 text-center mb-4">Traffic Citation Ticket</h1>

    {{-- Form Card --}}
    <div class="card mx-auto shadow-sm rounded-3 ticket-card" style="max-width: 800px;">
      <div class="card-body p-3 p-sm-4">
        <script> const violationGroups = @json($violationGroups->toArray()); </script>
        <form id="ticketForm" action="{{ route('ticket.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            {{-- Name --}}
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="name" name="name"
                    placeholder="Full name" value="{{ $v->name ?? old('name') }}">
              <label for="name">To: Full name</label>
            </div>

            {{-- Address --}}
            <div class="col-12 col-md-6 form-floating">
              <textarea class="form-control" id="address" name="address"
                        placeholder="Full address" style="height: 3rem">{{ $v->address ?? old('address') }}</textarea>
              <label for="address">Address</label>
            </div>


            {{-- Birthdate --}}
            <div class="col-6 col-md-3 form-floating">
              <input type="date" class="form-control" id="birthdate" name="birthdate"
                    placeholder="Birthdate" value="{{ $v->birthdate ?? old('birthdate') }}">
              <label for="birthdate">Birthdate</label>
            </div>

            {{-- License No. --}}
            <div class="col-6 col-md-3 form-floating">
              <input type="text" class="form-control" id="license_num" name="license_num"
                    placeholder="License number" autocomplete="off" value="{{ $v->license_number ?? old('license_num') }}">
              <label for="license_num">License No.</label>
            </div>  
            {{-- Plate No. --}}
            <div class="col-6 col-md-3 form-floating">
              <input type="text" class="form-control" id="plate_num" name="plate_num"
                    placeholder="Plate number" autocomplete="off" value="{{ old('plate_num') }}">
              <label for="plate_num">Plate No.</label>
            </div>

            {{-- Confiscated --}}
            <div class="col-12 col-md-6 form-floating">
              <select class="form-select" id="confiscation_type_id" name="confiscation_type_id">
                <option value="" disabled {{ old('confiscation_type_id')?'':'selected' }}>
                  Choose…
                </option>
                @foreach(\App\Models\ConfiscationType::all() as $type)
                  <option value="{{ $type->id }}"
                    {{ old('confiscation_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}   
                  </option>
                @endforeach
              </select>
              <label for="confiscation_type_id">Confiscated</label>
            </div>
            
            
            {{-- Vehicle Type --}}
            <div class="col-12 col-md-6 form-floating">
              <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                <option value="" disabled selected>Choose…</option>
                @foreach(['Motorcycle','Tricycle','Truck','Sedan','Bus','Jeepney','Van','Closed Van','SUV','Pickup'] as $type)
                  <option>{{ $type }}</option>
                @endforeach
              </select>
              <label for="vehicle_type">Vehicle Type</label>
            </div>

            {{-- Owner Checkbox --}}
            <div class="col-12 col-md-6 d-flex justify-content-evenly align-items-center">
              <div class="form-check">
                
                <input class="form-check-input" type="checkbox" id="is_owner"
                      name="is_owner" value="1" checked>
                <label class="form-check-label" for="is_owner">Violator is owner</label>
                
              </div>
              <div class="form-check">
                <input type="hidden" name="is_resident" value="0">
                <input class="form-check-input" type="checkbox" id="is_resident" name="is_resident" value="1" 
                {{ old('is_resident', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_resident">
                  Resident
                </label>
              </div>
            </div>

            {{-- Owner Name --}}
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="owner_name" name="owner_name"
                    placeholder="Owner name" {{ old('owner_name') }}>
              <label for="owner_name">Owner Name</label>
            </div>
          </div>
          
          {{-- Violations Section --}}
          <div class="mt-4">
            <h5 class="mb-2">Select Violations</h5>
            <div class="border rounded p-3">
              {{-- Category selector --}}
              <div class="form-floating mb-3">
                <select
                  class="form-select" 
                  id="categorySelect"
                  aria-label="Select violation category"
                >
                  <option value="" disabled selected>Choose category…</option>
                  @foreach($violationGroups->keys() as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                  @endforeach
                </select>
                <label for="categorySelect">Category</label>
              </div>

              {{-- Scrollable checklist container --}}
              <div
                id="violationsContainer"
                style="max-height: 250px; "
                class="px-1 overflow-auto"
              >
                {{-- JS will inject <div class="form-check">…</div> here --}}
              </div>
            </div>
          </div>

          {{-- Impounded & Location --}}
          <div class="row g-3 mt-3">
            <div class="col-12 col-md-6 d-flex align-items-center">
              <div class="form-check">
                <input type="hidden" name="is_impounded" value="0">
                <input class="form-check-input" type="checkbox" id="is_impounded"
                      name="is_impounded" value="1"
                      {{ old('is_impounded', false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_impounded">
                  Vehicle Impounded
                </label>
              </div>
            </div>
            <div class="col-12 col-md-6 form-floating">
              <input type="text" class="form-control" id="location" name="location"
                    placeholder="e.g. Brgy, Street" required>
              <label for="location">Location of Apprehension</label>
            </div>
          </div>

          {{-- Submit --}}
          <div class="mt-4">
            <button type="submit" class="btn btn-primary w-100 btn-lg">
              Save &amp; Print
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

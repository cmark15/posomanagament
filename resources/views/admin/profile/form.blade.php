@extends('components.layout')
@section('title', 'POSO Admin Management')
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin-profile.css')}}">
@endpush

@section('content')
<div id="admin-profile" data-hide-sidebar class="container d-flex justify-content-center align-items-center"
     style="min-height: calc(100vh - 70px);">
  <div class="card shadow-sm w-100" style="max-width: 500px;">
    <div class="card-header bg-success text-white text-center">
      <h5 class="mb-0">Update Details</h5>
    </div>
    <div class="card-body">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input  type="text"
                  id="name"
                  name="name"
                  class="form-control @error('name') is-invalid @enderror"
                  value="{{ old('name', $admin->name) }}"
                  required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input  type="text"
                  id="username"
                  name="username"
                  class="form-control @error('username') is-invalid @enderror"
                  value="{{ old('username', $admin->username) }}"
                  required>
          @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3 position-relative">
          <label for="password" class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
          <div class="input-group">
            <input  type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="••••••••">
            <button type="button"
                    class="btn btn-outline-secondary"
                    onclick="togglePassword()">
              <i class="fa fa-eye"></i>
            </button>
          </div>
          @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <input  type="password"
                  id="password_confirmation"
                  name="password_confirmation"
                  class="form-control"
                  placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-success w-100">
          <i class="fa fa-save"></i> Save Changes
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function togglePassword() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
  }
</script>
@endpush
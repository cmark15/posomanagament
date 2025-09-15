@extends('components.layout')
@section('title', 'POSO Admin Management')
@section('content')
<div class="container-fluid mt-4">
    <h2 class="mb-3">Violator Details</h2>

    <a href="{{ url('/ticket/create') }}"
       class="btn btn-success mb-3" data-ajax>
      <i class="bi bi-list-check"></i> Add Violation
    </a>


    <div id="viewMoreTable">
        @include('admin.partials.violatorView')
    </div>

</div>
@endsection
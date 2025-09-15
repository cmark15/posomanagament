@extends('components.layout')
@section('title', 'POSO Admin Management - Activity Logs')

@section('content')
<div class="container py-4" data-page="logs-activity">
  <h1 class="h4 mb-3">Activity Logs</h1>

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-2">
      <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Search...">
    </div>
    <div class="col-md-2">
      <select name="action" class="form-select">
        <option value="">All actions</option>
        <option value="ticket.issued" @selected($filters['action']==='ticket.issued')>ticket.issued</option>
        <option value="ticket.updated" @selected($filters['action']==='ticket.updated')>ticket.updated</option>
        <option value="user.login" @selected($filters['action']==='user.login')>user.login</option>
        <option value="user.logout" @selected($filters['action']==='user.logout')>user.logout</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="actor_type" class="form-select">
        <option value="">All actors</option>
        <option value="admin" @selected($filters['actor_type']==='admin')>Admin</option>
        <option value="enforcer" @selected($filters['actor_type']==='enforcer')>Enforcer</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control" placeholder="From">
    </div>
    <div class="col-md-2">
      <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control" placeholder="To">
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-success"><i class="bi bi-search me-1"></i>Filter</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Date/Time</th>
              <th>Action</th>
              <th>Actor</th>
              <th>Subject</th>
              <th>Description</th>
              <th style="width: 220px;">Meta</th>
            </tr>
          </thead>
          <tbody>
          @forelse($logs as $log)
            @php
              $actor = $log->actor;
              $subject = $log->subject;
              $actorLabel = class_basename($log->actor_type); // Admin/Enforcer
              $subjectLabel = class_basename($log->subject_type);
              $created = $log->created_at->timezone('Asia/Manila')->format('d M Y, H:i');
              $actorName = '';
              if ($actor) {
                if ($actorLabel === 'Enforcer') {
                  $actorName = trim(($actor->fname ?? '').' '.($actor->lname ?? '')).' ('.$actor->badge_num.')';
                } else {
                  $actorName = $actor->name ?? trim(($actor->fname ?? '').' '.($actor->lname ?? ''));
                }
              }
            @endphp
            <tr>
              <td><span>{{ $created }}</span></td>
              <td><span class="badge text-bg-success">{{ $log->action }}</span></td>
              <td>
                <div class="small">
                  <div class="fw-semibold">{{ $actorLabel }}</div>
                  <div class="text-muted">{{ $actorName ?: '-' }}</div>
                </div>
              </td>
              <td>
                <div class="small">
                  <div class="fw-semibold">{{ $subjectLabel }}</div>
                  <div class="text-muted">ID: {{ $log->subject_id }}</div>
                </div>
              </td>
              <td class="small">{{ $log->description }}</td>
              <td class="small">
                <div>IP: {{ $log->ip ?: '—' }}</div>
                <div title="{{ $log->user_agent }}">UA: {{ \Illuminate\Support\Str::limit($log->user_agent, 28) }}</div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No logs found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer py-2">
      {{ $logs->links() }}
    </div>
  </div>
</div>
@endsection

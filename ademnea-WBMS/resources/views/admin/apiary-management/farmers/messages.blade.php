@extends('layouts.app')

@section('title', 'Farmer Messages')
@section('page-title', 'Farmer Messages')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Messages</li>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:0.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.farmers.messages') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $messages->total() }} message(s) found.</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>From</th><th>Subject</th><th>Hive</th><th>Status</th><th>Received</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($messages as $msg)
                    <tr>
                        <td>
                            <strong>{{ $msg->farmer->full_name ?? 'Unknown' }}</strong><br>
                            <small class="text-muted">{{ $msg->farmer->email ?? '' }}</small>
                        </td>
                        <td>{{ $msg->subject }}</td>
                        <td>{{ $msg->hive?->display_name ?? '—' }}</td>
                        <td>
                            @if($msg->status === 'sent')
                                <span class="badge bg-warning text-dark">New</span>
                            @elseif($msg->status === 'read')
                                <span class="badge bg-info">Read</span>
                            @else
                                <span class="badge bg-success">Resolved</span>
                            @endif
                        </td>
                        <td>{{ $msg->created_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.farmers.messages.show', $msg) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No messages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $messages->links() }}
@endsection

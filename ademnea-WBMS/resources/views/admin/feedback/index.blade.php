@extends('layouts.app')

@section('title', 'Feedback Management')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Feedback Management</h3>
            <p class="text-muted mb-0">Monitor submissions, review details, and update processing status.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-chat-dots"></i></div>
                <div>
                    <div class="stat-value">{{ $feedbackList->total() }}</div>
                    <div class="stat-label">Total feedback</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon honey"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <div class="stat-value">{{ $feedbackList->where('status', 'new')->count() }}</div>
                    <div class="stat-label">New</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value">{{ $feedbackList->where('status', 'in_progress')->count() }}</div>
                    <div class="stat-label">In progress</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="stat-value">{{ $feedbackList->whereIn('status', ['resolved', 'closed'])->count() }}</div>
                    <div class="stat-label">Resolved / closed</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Subject, name, email, message or organization">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In progress</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select name="feedback_category_id" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ request('feedback_category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Apply filters</button>
                    <a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Submitted</th>
                        <th>Name / Email</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedbackList as $fb)
                        <tr>
                            <td>{{ $fb->id }}</td>
                            <td>{{ $fb->submitted_at?->toDateTimeString() ?? $fb->created_at->toDateTimeString() }}</td>
                            <td>
                                <div>{{ $fb->full_name }}</div>
                                <div class="text-muted small">{{ $fb->email }}</div>
                            </td>
                            <td>{{ optional($fb->category)->name ?? 'General' }}</td>
                            <td>{{ $fb->subject }}</td>
                            <td>
                                <span class="badge {{ $fb->status === 'new' ? 'bg-primary' : ($fb->status === 'in_progress' ? 'bg-warning text-dark' : ($fb->status === 'resolved' ? 'bg-success' : 'bg-secondary')) }}">
                                    {{ ucfirst(str_replace('_',' ', $fb->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.feedback.show', $fb) }}" class="btn btn-sm btn-outline-forest">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No feedback records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $feedbackList->links() }}</div>
</div>
@endsection

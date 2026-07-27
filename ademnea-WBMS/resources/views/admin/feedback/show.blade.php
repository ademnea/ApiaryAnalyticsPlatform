@extends('layouts.app')

@section('title', 'Feedback Details')

@section('content')
<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="mb-2">{{ $feedback->subject }}</h4>
                    <div class="text-muted">From: {{ $feedback->full_name }} &lt;{{ $feedback->email }}&gt;</div>
                    <div class="text-muted">Submitted: {{ $feedback->submitted_at?->toDayDateTimeString() ?? $feedback->created_at->toDayDateTimeString() }}</div>
                    <div class="text-muted">Category: {{ optional($feedback->category)->name ?? 'General' }}</div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <form method="POST" action="{{ route('admin.feedback.update', $feedback) }}">
                        @csrf
                        @method('PUT')
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="new" {{ $feedback->status === 'new' ? 'selected' : '' }}>New</option>
                            <option value="in_progress" {{ $feedback->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $feedback->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $feedback->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </form>

                    <form method="POST" action="{{ route('admin.feedback.destroy', $feedback) }}" onsubmit="return confirm('Delete this feedback?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Phone:</strong> {{ $feedback->phone ?? '-' }}
                    </div>
                    <div class="mb-3">
                        <strong>Organization:</strong> {{ $feedback->organization ?? '-' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Current status:</strong>
                        <span class="badge {{ $feedback->status === 'new' ? 'bg-primary' : ($feedback->status === 'in_progress' ? 'bg-warning text-dark' : ($feedback->status === 'resolved' ? 'bg-success' : 'bg-secondary')) }} ms-2">
                            {{ ucfirst(str_replace('_',' ', $feedback->status)) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <strong>Message:</strong>
                <div class="border p-3 mt-2 rounded">{{ $feedback->message }}</div>
            </div>

            <div class="mb-3">
                <strong>Attachments</strong>
                @if($feedback->attachments->isEmpty())
                    <div class="text-muted mt-2">No attachments</div>
                @else
                    <ul class="mt-2">
                        @foreach($feedback->attachments as $att)
                            <li>
                                <a href="{{ Storage::disk($disk)->url($att->file_path) }}" target="_blank">{{ $att->file_name }}</a>
                                <span class="text-muted">({{ $att->file_type }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="mb-3">
                <strong>Administrator Response</strong>
                @if($feedback->admin_response)
                    <div class="border p-3 mt-2 rounded bg-light">
                        {{ $feedback->admin_response }}
                    </div>
                    <div class="text-muted small mt-2">
                        Responded by {{ optional($feedback->responder)->name ?? 'Administrator' }}
                        @if($feedback->responded_at)
                            on {{ $feedback->responded_at->toDayDateTimeString() }}
                        @endif
                    </div>
                @else
                    <div class="text-muted mt-2">No response yet.</div>
                @endif

                <form method="POST" action="{{ route('admin.feedback.update', $feedback) }}" class="mt-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $feedback->status }}">
                    <div class="mb-2">
                        <label class="form-label" for="admin_response">Reply to submitter</label>
                        <textarea id="admin_response" name="admin_response" class="form-control" rows="4" placeholder="Write a response to the submitter...">{{ old('admin_response', $feedback->admin_response) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Response</button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

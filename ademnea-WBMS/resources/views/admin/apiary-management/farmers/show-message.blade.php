@extends('layouts.app')

@section('title', $farmerMessage->subject)
@section('page-title', 'Farmer Message')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.messages') }}">Messages</a></li>
    <li class="breadcrumb-item active" aria-current="page">Message</li>
@endsection

@section('content')

<div class="row g-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope me-1"></i>{{ $farmerMessage->subject }}</span>
                @if($farmerMessage->status !== 'resolved')
                    <form method="POST" action="{{ route('admin.farmers.messages.resolve', $farmerMessage) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-circle me-1"></i>Mark Resolved</button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>From:</strong> {{ $farmerMessage->farmer->full_name ?? 'Unknown' }} ({{ $farmerMessage->farmer->email ?? '' }})
                </div>
                @if($farmerMessage->hive)
                    <div class="mb-3">
                        <strong>Hive:</strong> {{ $farmerMessage->hive->display_name }} ({{ $farmerMessage->hive->hybrid_identifier }})
                    </div>
                @endif
                <div class="mb-3">
                    <strong>Received:</strong> {{ $farmerMessage->created_at->format('d M Y H:i') }}
                </div>
                <hr>
                <div class="p-3 bg-light rounded" style="white-space: pre-wrap;">{{ $farmerMessage->message }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Status</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Current</dt>
                    <dd class="col-sm-8">
                        @if($farmerMessage->status === 'sent')
                            <span class="badge bg-warning text-dark">New</span>
                        @elseif($farmerMessage->status === 'read')
                            <span class="badge bg-info">Read</span>
                        @else
                            <span class="badge bg-success">Resolved</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4 text-muted">Farmer</dt>
                    <dd class="col-sm-8">{{ $farmerMessage->farmer->full_name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $farmerMessage->farmer->email ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">{{ $farmerMessage->farmer->phone ?? '—' }}</dd>
                    @if($farmerMessage->hive)
                        <dt class="col-sm-4 text-muted">Hive</dt>
                        <dd class="col-sm-8">{{ $farmerMessage->hive->display_name }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

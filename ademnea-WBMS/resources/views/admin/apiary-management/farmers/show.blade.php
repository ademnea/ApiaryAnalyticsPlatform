@extends('layouts.app')

@section('title', $farmer->full_name)
@section('page-title', $farmer->full_name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $farmer->full_name }}</li>
@endsection

@section('content')

@if ($farmer->trashed())
    <div class="alert alert-warning">This farmer record has been removed (soft-deleted).</div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person me-1"></i>Profile</span>
                @if ($farmer->trashed())
                    <form method="POST" action="{{ route('admin.farmers.restore', $farmer) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success">Restore</button>
                    </form>
                @else
                    <a href="{{ route('admin.farmers.edit', $farmer) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                @endif
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $farmer->email ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Phone</dt>
                    <dd class="col-sm-8">{{ $farmer->phone ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Secondary</dt>
                    <dd class="col-sm-8">{{ $farmer->phone_secondary ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">{{ $farmer->country_name }} / {{ $farmer->region ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Village</dt>
                    <dd class="col-sm-8">{{ $farmer->village ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">National ID</dt>
                    <dd class="col-sm-8">{{ $farmer->national_id ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8"><span class="badge bg-{{ $farmer->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($farmer->status) }}</span></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-geo-alt me-1"></i>Apiaries</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Name</th><th>Country</th><th>Hives</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($farmer->apiaries as $apiary)
                            <tr>
                                <td><a href="{{ route('admin.apiaries.show', $apiary) }}">{{ $apiary->name }}</a></td>
                                <td>{{ $apiary->country_name }}</td>
                                <td>{{ $apiary->hives_count ?? $apiary->hives->count() }}</td>
                                <td>{{ ucfirst($apiary->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No apiaries linked yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Inspection #' . $inspection->id)
@section('page-title', 'Inspection Record')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.inspections.index') }}">Inspections</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $inspection->id }}</li>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Inspection Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Hive</dt>
                    <dd class="col-sm-8">{{ $inspection->hive->display_name ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Date</dt>
                    <dd class="col-sm-8">{{ $inspection->inspected_at?->format('d M Y') }}</dd>

                    <dt class="col-sm-4 text-muted">Strength</dt>
                    <dd class="col-sm-8">{{ $inspection->strength_rating ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Disease</dt>
                    <dd class="col-sm-8">{{ $inspection->disease_events ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Queen</dt>
                    <dd class="col-sm-8">{{ $inspection->queen_status_notes ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $inspection->general_notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Harvest #' . $harvest->id)
@section('page-title', 'Harvest Record')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.harvests.index') }}">Harvests</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $harvest->id }}</li>
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
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Harvest Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Hive</dt>
                    <dd class="col-sm-8">{{ $harvest->hive->display_name ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Date</dt>
                    <dd class="col-sm-8">{{ $harvest->harvest_date?->format('d M Y') }}</dd>

                    <dt class="col-sm-4 text-muted">Honey</dt>
                    <dd class="col-sm-8">{{ $harvest->honey_yield_kg ?? '—' }} kg</dd>

                    <dt class="col-sm-4 text-muted">Beeswax</dt>
                    <dd class="col-sm-8">{{ $harvest->beeswax_yield_kg ?? '—' }} kg</dd>

                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $harvest->notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $apiary->name)
@section('page-title', $apiary->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.apiaries.index') }}">Apiaries</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $apiary->name }}</li>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Details</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Country</dt>
                    <dd class="col-sm-8">{{ $apiary->country_name }}</dd>
                    <dt class="col-sm-4 text-muted">Region</dt>
                    <dd class="col-sm-8">{{ $apiary->region ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">District</dt>
                    <dd class="col-sm-8">{{ $apiary->district ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">{{ ucfirst($apiary->status) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-1"></i>Managing Farmer</span>
            </div>
            <div class="card-body">
                @if($apiary->farmer)
                    <a href="{{ route('admin.farmers.show', $apiary->farmer) }}">{{ $apiary->farmer->full_name }}</a>
                @else
                    <span class="text-muted">Unassigned</span>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

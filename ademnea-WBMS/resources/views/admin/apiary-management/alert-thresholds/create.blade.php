@extends('layouts.app')

@section('title', 'Add Alert Threshold')
@section('page-title', 'Add Alert Threshold')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.alert-thresholds.index') }}">Alert Thresholds</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Threshold Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.alert-thresholds.store') }}">
            @csrf
            @include('admin.apiary-management.alert-thresholds._form', ['hives' => $hives])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Create Threshold</button>
                <a href="{{ route('admin.alert-thresholds.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

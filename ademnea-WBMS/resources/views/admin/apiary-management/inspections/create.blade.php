@extends('layouts.app')

@section('title', 'Add Inspection')
@section('page-title', 'Add Inspection')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.inspections.index') }}">Inspections</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Inspection Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inspections.store') }}">
            @csrf
            @include('admin.apiary-management.inspections._form', ['inspection' => null, 'hives' => $hives])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Inspection</button>
                <a href="{{ route('admin.inspections.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

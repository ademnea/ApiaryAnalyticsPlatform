@extends('layouts.app')

@section('title', 'Edit Inspection')
@section('page-title', 'Edit Inspection')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.inspections.index') }}">Inspections</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inspections.show', $inspection) }}">Record #{{ $inspection->id }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Inspection Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inspections.update', $inspection) }}">
            @csrf
            @method('PUT')
            @include('admin.apiary-management.inspections._form', ['inspection' => $inspection, 'hives' => $hives])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                <a href="{{ route('admin.inspections.show', $inspection) }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Farmer — ' . $farmer->full_name)
@section('page-title', 'Edit Farmer')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.show', $farmer) }}">{{ $farmer->full_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">Farmer Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.farmers.update', $farmer) }}">
            @csrf
            @method('PUT')
            @include('admin.apiary-management.farmers._form', ['statuses' => $statuses ?? ['Active', 'Inactive', 'Suspended']])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                <a href="{{ route('admin.farmers.show', $farmer) }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

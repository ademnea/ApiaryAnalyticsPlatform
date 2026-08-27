@extends('layouts.app')

@section('title', 'Edit Harvest')
@section('page-title', 'Edit Harvest')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.harvests.index') }}">Harvests</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.harvests.show', $harvest) }}">Record #{{ $harvest->id }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Harvest Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.harvests.update', $harvest) }}">
            @csrf
            @method('PUT')
            @include('admin.apiary-management.harvests._form', ['harvest' => $harvest, 'hives' => $hives])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                <a href="{{ route('admin.harvests.show', $harvest) }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

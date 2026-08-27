@extends('layouts.app')

@section('title', 'Add Harvest')
@section('page-title', 'Add Harvest')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.harvests.index') }}">Harvests</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Harvest Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.harvests.store') }}">
            @csrf
            @include('admin.apiary-management.harvests._form', ['harvest' => null, 'hives' => $hives])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Harvest</button>
                <a href="{{ route('admin.harvests.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

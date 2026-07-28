@extends('layouts.app')

@section('title', 'Edit Hive — ' . $hive->display_name)
@section('page-title', 'Edit Hive')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.index') }}">Hives</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.show', $hive) }}">{{ $hive->hybrid_identifier }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Hive Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hives.update', $hive) }}">
            @csrf
            @method('PUT')
            @include('admin.apiary-management.hives._form', ['hive' => $hive, 'apiary' => $hive->apiary])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                <a href="{{ route('admin.hives.show', $hive) }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

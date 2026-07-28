@extends('layouts.app')

@section('title', 'Register Hive')
@section('page-title', 'Register Hive')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.index') }}">Hives</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">Hive Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hives.store') }}">
            @csrf
            @include('admin.apiary-management.hives._form', ['apiary' => null, 'hive' => null])
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Register Hive</button>
                <a href="{{ route('admin.hives.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Register Farmer')
@section('page-title', 'Register Farmer')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">Farmer Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.farmers.store') }}">
            @csrf
            @include('admin.apiary-management.farmers._form')
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Register Farmer</button>
                <a href="{{ route('admin.farmers.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

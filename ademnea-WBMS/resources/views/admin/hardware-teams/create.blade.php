@extends('layouts.app')

@section('title', 'Register Hardware Team')
@section('page-title', 'Register Hardware Team')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
@include('admin.iot-devices._styles')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Team Details</div>
            <div class="card-body">
                <form action="{{ route('admin.hardware-teams.store') }}" method="POST">
                    @csrf
                    @include('admin.hardware-teams._form')
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Register Team</button>
                        <a href="{{ route('admin.hardware-teams.index') }}" class="btn btn-outline-forest">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sticky-side">
            @include('admin.hardware-teams._form-guide')
        </div>
    </div>
</div>
@endsection

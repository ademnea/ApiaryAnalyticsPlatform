@extends('layouts.app')

@section('title', 'Register IoT Device')
@section('page-title', 'Register IoT Device')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.iot-devices.index') }}">IoT Devices</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">Device Details</div>
    <div class="card-body">
        <form action="{{ route('admin.iot-devices.store') }}" method="POST">
            @csrf
            @include('admin.iot-devices._form')
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Provision Device</button>
                <a href="{{ route('admin.iot-devices.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
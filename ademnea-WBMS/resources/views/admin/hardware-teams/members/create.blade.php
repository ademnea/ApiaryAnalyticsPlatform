@extends('layouts.app')

@section('title', 'Add Team Member')
@section('page-title', 'Add Team Member — ' . $hardwareTeam->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.index') }}">Hardware Teams</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.hardware-teams.show', $hardwareTeam) }}">{{ $hardwareTeam->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add Member</li>
@endsection

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">Member Details</div>
    <div class="card-body">
        <form action="{{ route('admin.hardware-teams.members.store', $hardwareTeam) }}" method="POST">
            @csrf
            @include('admin.hardware-teams.members._form')
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Add Member</button>
                <a href="{{ route('admin.hardware-teams.show', $hardwareTeam) }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
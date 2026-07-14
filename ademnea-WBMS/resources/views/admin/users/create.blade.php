@extends('layouts.app')

@section('title', 'Create User')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i>Create User</h1>
    <p class="breadcrumb">
        <a href="{{ route('admin.users.index') }}">User Management</a>
        &rsaquo; New User
    </p>
</div>

<div class="page-content">
    <div class="row" style="max-width:720px;">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i>Account Details
                </div>
                <div class="card-body p-4">

                    {{-- Validation errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                <div class="fw-semibold mb-1">Please fix the following errors:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
                        @csrf

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-sm-6">
                                <label for="name" class="form-label fw-semibold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="255"
                                    autocomplete="name"
                                    placeholder="e.g. Jane Doe"
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-sm-6">
                                <label for="email" class="form-label fw-semibold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                    placeholder="user@example.com"
                                >
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="col-sm-6">
                                <label for="password" class="form-label fw-semibold">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 characters"
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Will be sent to the user via welcome email.
                                </div>
                            </div>

                            {{-- Roles --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Roles <span class="text-danger">*</span>
                                </label>
                                @error('roles')
                                    <div class="text-danger small mb-1">{{ $message }}</div>
                                @enderror
                                @error('roles.*')
                                    <div class="text-danger small mb-1">{{ $message }}</div>
                                @enderror
                                <div class="d-flex flex-wrap gap-2 p-3 border rounded"
                                     style="background:#f8faf7;min-height:52px;">
                                    @foreach ($roles as $role)
                                        <div class="form-check form-check-inline m-0">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="role_{{ $role->id }}"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                {{ ucfirst($role->name) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">Select one or more roles for this user.</div>
                            </div>

                        </div>{{-- .row --}}

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-check me-1"></i>Create User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>{{-- .page-content --}}

@endsection

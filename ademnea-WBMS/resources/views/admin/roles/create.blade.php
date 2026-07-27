@extends('layouts.app')

@section('title', 'Create Role')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-shield-plus me-2"></i>Create Role</h1>
    <p class="breadcrumb">
        <a href="{{ route('admin.roles.index') }}">Role Management</a>
        <span class="mx-1 text-muted">/</span>Create Role
    </p>
</div>

<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shield-plus me-2"></i>New Role
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ route('admin.roles.store') }}" novalidate>
                        @csrf

                        {{-- Role Name --}}
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                maxlength="255"
                                required
                                autofocus
                                placeholder="e.g. content-editor"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Use lowercase letters and hyphens. The <code>super-admin</code> role name is reserved.
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-honey">
                                <i class="bi bi-check-lg me-1"></i>Create Role
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>{{-- .page-content --}}

@endsection

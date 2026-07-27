@extends('layouts.app')

@section('title', 'Rename Role')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Rename Role</h1>
    <p class="breadcrumb">
        <a href="{{ route('admin.roles.index') }}">Role Management</a>
        <span class="mx-1 text-muted">/</span>Rename
    </p>
</div>

<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil me-2"></i>Rename <strong>{{ $role->name }}</strong>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ route('admin.roles.rename', $role->id) }}" novalidate>
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
                                value="{{ old('name', $role->name) }}"
                                maxlength="255"
                                required
                                autofocus
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Role names must be unique. The <code>super-admin</code> name is reserved.
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-honey">
                                <i class="bi bi-check-lg me-1"></i>Save Changes
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

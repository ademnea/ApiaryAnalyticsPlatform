@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

@php
    $isSelf = $user->id === auth()->id();
@endphp

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Edit User</h1>
    <p class="breadcrumb">
        <a href="{{ route('admin.users.index') }}">User Management</a>
        &rsaquo; {{ $user->name }}
    </p>
</div>

<div class="page-content">
    <div class="row" style="max-width:720px;">
        <div class="col-12">

            {{-- Self-edit advisory banner --}}
            @if ($isSelf)
                <div class="alert alert-ademnea d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                    <span>
                        You are editing your own account. Role and status fields are disabled —
                        use the super-admin panel to modify your own privileges.
                    </span>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-gear me-2"></i>Account Details
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

                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}" novalidate>
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
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    maxlength="255"
                                    autocomplete="name"
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
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                >
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password (optional) --}}
                            <div class="col-sm-6">
                                <label for="password" class="form-label fw-semibold">
                                    New Password
                                    <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    minlength="8"
                                    autocomplete="new-password"
                                    placeholder="Leave blank to keep current password"
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Minimum 8 characters. Leave blank to keep unchanged.</div>
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
                                @php
                                    $currentRoles = old('roles', $user->getRoleNames()->toArray());
                                @endphp
                                @if ($isSelf)
                                    {{-- Re-submit existing roles as hidden fields when editing own account --}}
                                    @foreach ($user->getRoleNames() as $rName)
                                        <input type="hidden" name="roles[]" value="{{ $rName }}">
                                    @endforeach
                                @endif
                                <div class="d-flex flex-wrap gap-2 p-3 border rounded {{ $isSelf ? 'opacity-50' : '' }}"
                                     style="background:#f8faf7;min-height:52px;">
                                    @foreach ($roles as $role)
                                        <div class="form-check form-check-inline m-0">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="role_{{ $role->id }}"
                                                name="{{ $isSelf ? '' : 'roles[]' }}"
                                                value="{{ $role->name }}"
                                                {{ in_array($role->name, $currentRoles) ? 'checked' : '' }}
                                                {{ $isSelf ? 'disabled' : '' }}
                                            >
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                {{ ucfirst($role->name) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($isSelf)
                                    <div class="form-text text-warning">
                                        <i class="bi bi-lock me-1"></i>Roles are locked when editing your own account.
                                    </div>
                                @else
                                    <div class="form-text">Select one or more roles for this user.</div>
                                @endif
                            </div>

                            {{-- Status (display only) --}}
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Current Status</label>
                                @php
                                    $badgeMap = [
                                        'active'    => 'badge-active',
                                        'pending'   => 'badge-pending',
                                        'suspended' => 'badge-offline',
                                        'rejected'  => 'badge-offline',
                                    ];
                                    $cls = $badgeMap[$user->status] ?? 'badge-pending';
                                @endphp
                                <div class="mt-1">
                                    <span class="badge rounded-pill {{ $cls }} px-3 py-2" style="font-size:0.82rem;">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                    @if ($isSelf)
                                        <div class="form-text text-warning mt-1">
                                            <i class="bi bi-lock me-1"></i>Status cannot be changed via this form for your own account.
                                        </div>
                                    @else
                                        <div class="form-text">
                                            Use Activate / Suspend buttons on the
                                            <a href="{{ route('admin.users.index') }}">user list</a> to change status.
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>{{-- .row --}}

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Save Changes
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

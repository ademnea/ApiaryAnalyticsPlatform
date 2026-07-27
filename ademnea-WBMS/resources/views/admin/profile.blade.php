@extends('layouts.app')

@section('title', 'My Profile')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-person-circle me-2"></i>My Profile</h1>
    <p class="breadcrumb">Manage your account details and password.</p>
</div>

<div class="page-content">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4" style="max-width:860px;">

        {{-- ---- Profile Information ---- --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person me-2"></i>Profile Information
                </div>
                <div class="card-body p-4">

                    @if ($errors->has('name') || $errors->has('email'))
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                            <div>
                                @foreach (['name', 'email'] as $field)
                                    @error($field) <div>{{ $message }}</div> @enderror
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    maxlength="255"
                                />
                            </div>
                            <div class="col-sm-6">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    maxlength="255"
                                />
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ---- Change Password ---- --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shield-lock me-2"></i>Change Password
                </div>
                <div class="card-body p-4">

                    @if ($errors->has('current_password') || $errors->has('password'))
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                            <div>
                                @foreach (['current_password', 'password'] as $field)
                                    @error($field) <div>{{ $message }}</div> @enderror
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.profile.change-password') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    autocomplete="current-password"
                                    required
                                />
                            </div>
                            <div class="col-sm-4">
                                <label for="password" class="form-label fw-semibold">New Password</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="new-password"
                                    required
                                    minlength="8"
                                />
                            </div>
                            <div class="col-sm-4">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="form-control"
                                    autocomplete="new-password"
                                    required
                                    minlength="8"
                                />
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- end .row --}}
</div>{{-- end .page-content --}}

@endsection

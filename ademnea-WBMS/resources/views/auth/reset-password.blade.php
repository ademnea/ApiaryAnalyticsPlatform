@extends('layouts.auth')

@section('title', 'AdEMNEA – Reset Password')

@section('content')

    <h2 class="mb-1">Set a new password</h2>
    <p class="text-muted mb-4" style="font-size:.87rem;">
        Choose a strong password of at least 8 characters.
    </p>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-bee-error d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password.update') }}" novalidate>
        @csrf

        {{-- Hidden fields --}}
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        {{-- New password --}}
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Min. 8 characters"
                    autocomplete="new-password"
                    required
                    minlength="8"
                />
            </div>
            @error('password')
                <div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Re-enter password"
                    autocomplete="new-password"
                    required
                    minlength="8"
                />
            </div>
        </div>

        <button type="submit" class="btn btn-bee-primary">
            <i class="bi bi-check-lg me-2"></i>Reset Password
        </button>
    </form>

@endsection

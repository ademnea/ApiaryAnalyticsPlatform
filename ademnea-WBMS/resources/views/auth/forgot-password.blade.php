@extends('layouts.auth')

@section('title', 'AdEMNEA – Forgot Password')

@section('content')

    <h2 class="mb-1">Forgot your password?</h2>
    <p class="text-muted mb-4" style="font-size:.87rem;">
        Enter your email address and we'll send you a reset link.
    </p>

    {{-- Success status --}}
    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('status') }}
        </div>
    @endif

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

    <form method="POST" action="{{ route('admin.password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="you@ademnea.org"
                    autocomplete="email"
                    autofocus
                    required
                />
            </div>
        </div>

        <button type="submit" class="btn btn-bee-primary">
            <i class="bi bi-send me-2"></i>Send Reset Link
        </button>
    </form>

    <div class="mt-3 text-center" style="font-size:.85rem;">
        <a href="{{ route('admin.login') }}" style="color:var(--bee-green);text-decoration:none;">
            <i class="bi bi-arrow-left me-1"></i>Back to Sign In
        </a>
    </div>

@endsection

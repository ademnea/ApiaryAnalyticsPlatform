@extends('layouts.app')

@section('title', '403 — Access Denied')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-shield-x me-2 text-danger"></i>Access Denied</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">403 Access Denied</li>
        </ol>
    </nav>
</div>

<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="card text-center py-5">
                <div class="card-body px-5">

                    {{-- Icon --}}
                    <div class="mb-4">
                        <span style="font-size: 4rem; line-height: 1;">🚫</span>
                    </div>

                    {{-- HTTP status badge --}}
                    <span class="badge rounded-pill mb-3"
                          style="background: #FFE0E0; color: #7F1D1D; font-size: 0.8rem; padding: 0.45em 1em; font-weight: 600; letter-spacing: 0.05em;">
                        HTTP 403
                    </span>

                    {{-- Heading --}}
                    <h2 class="fw-bold mb-3" style="font-family: var(--font-display); color: #1a2e1f;">
                        You don't have permission to access this page.
                    </h2>

                    {{-- Message --}}
                    <p class="text-muted mb-4" style="font-size: 0.92rem; line-height: 1.7;">
                        {{ $exception->getMessage() ?: 'Your account does not have the required role or permission to view this resource. If you believe this is a mistake, please contact your system administrator.' }}
                    </p>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}"
                           class="btn btn-outline-forest">
                            <i class="bi bi-arrow-left me-1"></i> Go Back
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-speedometer2 me-1"></i> Return to Dashboard
                        </a>
                    </div>

                </div>
            </div>

            {{-- Help note --}}
            <p class="text-center text-muted mt-3" style="font-size: 0.78rem;">
                Need access? Contact your administrator or
                <a href="{{ route('admin.profile') }}" style="color: var(--clr-forest-mid);">check your profile</a>
                to review your assigned role.
            </p>

        </div>
    </div>
</div>

@endsection

@extends('layouts.auth')

@section('title', 'AdEMNEA – Sign In')

@section('content')

    <h2 class="mb-4">Sign in to your account</h2>

    {{-- Session / validation errors --}}
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

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-3">
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

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                />
                {{-- Toggle visibility --}}
                <button
                    class="btn btn-outline-secondary"
                    type="button"
                    id="togglePwd"
                    title="Show / hide password"
                    onclick="
                        const p = document.getElementById('password');
                        const i = this.querySelector('i');
                        if(p.type==='password'){p.type='text'; i.className='bi bi-eye-slash';}
                        else{p.type='password'; i.className='bi bi-eye';}
                    "
                    style="border-color:#dee2e6;"
                >
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        {{-- Remember me + Forgot password --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                <label class="form-check-label" for="remember" style="font-size:.85rem;color:#374151;">
                    Remember me
                </label>
            </div>
            <a href="{{ route('admin.password.request') }}"
               style="font-size:.85rem;color:var(--bee-green);text-decoration:none;font-weight:500;">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="btn btn-bee-primary">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>

@endsection
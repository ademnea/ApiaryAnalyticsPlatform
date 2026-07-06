@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div class="card mx-auto" style="max-width:420px">
        <div class="card-body">
            <h5 class="card-title mb-3">Reset your password</h5>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.password.email') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" type="email" class="form-control" name="email" required autofocus>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.login') }}" class="text-muted">Back to login</a>
                    <button type="submit" class="btn btn-bee-primary">Send reset link</button>
                </div>
            </form>
        </div>
    </div>
@endsection

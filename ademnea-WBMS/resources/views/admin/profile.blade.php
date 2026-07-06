@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="page-header">
        <h1>My Profile</h1>
        <p class="breadcrumb">Manage your account details and preferences.</p>
    </div>

    <div class="page-content">
        <div class="card" style="max-width:720px">
            <div class="card-body">
                <h5 class="card-title">Account</h5>
                <p class="text-muted">Name: {{ auth()->user()->name ?? '—' }}</p>
                <p class="text-muted">Email: {{ auth()->user()->email ?? '—' }}</p>
            </div>
        </div>
    </div>
@endsection

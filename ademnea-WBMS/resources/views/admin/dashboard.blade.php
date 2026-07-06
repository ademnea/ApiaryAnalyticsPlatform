@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Dashboard</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light" style="background:var(--clr-forest-mid);">Logout</button>
        </form>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Users</h6>
                    <div class="display-6">{{ $stats['users'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Quick Links</h6>
                    <div class="list-group list-group-horizontal flex-wrap">
                        <a class="list-group-item list-group-item-action" href="#">Apiaries & Hives</a>
                        <a class="list-group-item list-group-item-action" href="#">IoT Devices</a>
                        <a class="list-group-item list-group-item-action" href="#">Monitoring</a>
                        <a class="list-group-item list-group-item-action" href="#">Newsletters</a>
                        <a class="list-group-item list-group-item-action" href="#">Publications</a>
                        <a class="list-group-item list-group-item-action" href="#">Events</a>
                        <a class="list-group-item list-group-item-action" href="#">Gallery</a>
                        <a class="list-group-item list-group-item-action" href="#">Scholarships</a>
                        <a class="list-group-item list-group-item-action" href="#">Feedback</a>
                        <a class="list-group-item list-group-item-action" href="#">Users & Roles</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

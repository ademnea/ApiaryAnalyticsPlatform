@extends('layouts.app')

@section('title', 'Farmers')
@section('page-title', 'Farmers')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Farmers</li>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Country</label>
                <select name="country" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All countries</option>
                    @foreach($countries as $code => $name)
                        <option value="{{ $code }}" @selected(request('country') == $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, email, phone...">
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('admin.farmers.index') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $farmers->total() }} farmer(s) found.</p>
    <a href="{{ route('admin.farmers.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Register Farmer</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Name</th><th>Phone</th><th>Country</th><th>Status</th><th>Apiaries</th></tr>
            </thead>
            <tbody>
                @foreach($farmers as $farmer)
                    <tr>
                        <td><a href="{{ route('admin.farmers.show', $farmer) }}">{{ $farmer->full_name }}</a></td>
                        <td>{{ $farmer->phone }}</td>
                        <td>{{ $farmer->country_name }}</td>
                        <td><span class="badge bg-{{ $farmer->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($farmer->status) }}</span></td>
                        <td>{{ $farmer->apiaries->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{ $farmers->links() }}
@endsection

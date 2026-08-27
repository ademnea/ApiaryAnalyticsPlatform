@extends('layouts.app')

@section('title', 'Apiaries')
@section('page-title', 'Apiaries')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Apiaries</li>
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
                    @foreach(['Active', 'Inactive', 'Under Maintenance'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('admin.apiaries.index') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $apiaries->total() }} apiary(ies) found.</p>
    <a href="{{ route('admin.apiaries.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Register Apiary</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Name</th><th>Country</th><th>Managing Farmer</th><th>Hives</th><th>Status</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($apiaries as $apiary)
                    <tr>
                        <td>{{ $apiary->name }}</td>
                        <td>{{ $apiary->country_name }}</td>
                        <td>
                            @if($apiary->farmer)
                                {{ $apiary->farmer->full_name }}
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td>{{ $apiary->hives_count ?? $apiary->hives->count() }}</td>
                        <td><span class="badge bg-{{ $apiary->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($apiary->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.apiaries.show', $apiary) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('admin.apiaries.edit', $apiary) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No apiaries registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $apiaries->links() }}
@endsection

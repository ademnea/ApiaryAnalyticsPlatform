@extends('layouts.app')

@section('title', 'Scholarships')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Scholarships</h1>
            <p class="text-muted mb-0">Manage scholarships, view status, and publish opportunities for public access.</p>
        </div>
        <a href="{{ route('admin.scholarship.create') }}" class="btn btn-primary px-4" style="background: #1B3022; border-color: #1B3022;">Create Scholarship</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm rounded-4 border-0 p-3">
                <div class="text-muted mb-2">Total Scholarships</div>
                <h3 class="mb-0">{{ $stats['totalScholarships'] }}</h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm rounded-4 border-0 p-3">
                <div class="text-muted mb-2">Active</div>
                <h3 class="mb-0">{{ $stats['activeScholarships'] }}</h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm rounded-4 border-0 p-3">
                <div class="text-muted mb-2">Expiring Soon</div>
                <h3 class="mb-0">{{ $stats['expiringSoon'] }}</h3>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm rounded-4 border-0 p-3">
                <div class="text-muted mb-2">Expired</div>
                <h3 class="mb-0">{{ $stats['expiredScholarships'] }}</h3>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.scholarship.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="search" name="search" class="form-control" placeholder="Search scholarships" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="country" class="form-select">
                        <option value="">All countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ request('country') === $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>

            @if($scholarships->isEmpty())
                <div class="text-center py-5">
                    <p class="mb-2 text-muted">No scholarships found.</p>
                    <a href="{{ route('admin.scholarship.create') }}" class="btn btn-primary">Create Scholarship</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Institution</th>
                                <th>Country</th>
                                <th>Funding</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scholarships as $scholarship)
                                <tr>
                                    <td>{{ $scholarship->title }}</td>
                                    <td>{{ $scholarship->institution }}</td>
                                    <td>{{ $scholarship->country }}</td>
                                    <td>{{ $scholarship->funding_type }} @if($scholarship->funding_amount) · {{ $scholarship->currency }} {{ number_format($scholarship->funding_amount, 2) }} @endif</td>
                                    <td>{{ $scholarship->application_deadline->format('M d, Y') }}</td>
                                    <td><span class="badge bg-{{ $scholarship->status === 'active' ? 'success' : ($scholarship->status === 'expired' ? 'danger' : 'secondary') }}">{{ ucfirst($scholarship->status) }}</span></td>
                                    <td>{!! $scholarship->is_featured ? '<span class="text-warning">★</span>' : '<span class="text-muted">—</span>' !!}</td>
                                    <td>
                                        <a href="{{ route('admin.scholarship.show', $scholarship) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('admin.scholarship.edit', $scholarship) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form action="{{ route('admin.scholarship.destroy', $scholarship) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this scholarship?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $scholarships->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

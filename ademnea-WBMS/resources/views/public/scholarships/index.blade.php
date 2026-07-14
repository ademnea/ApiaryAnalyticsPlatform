@extends('layouts.app')

@section('title', 'Scholarships')

@section('content')
<div class="container-fluid mt-4">
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="rounded-4 p-5 text-white" style="background: linear-gradient(135deg, #1B4332 0%, #40916C 100%);">
                <h1 class="h2 mb-2">Scholarship Opportunities</h1>
                <p class="mb-0 text-white-75">Explore published scholarships by country, category, and funding type.</p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('public.scholarships.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search</label>
                    <input type="search" name="search" class="form-control" placeholder="Search scholarships" value="{{ request('search') }}">
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Country</label>
                    <select name="country" class="form-select">
                        <option value="">All countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ request('country') === $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label">Funding Type</label>
                    <select name="funding_type" class="form-select">
                        <option value="">All funding types</option>
                        @foreach($fundingTypes as $fundingType)
                            <option value="{{ $fundingType }}" {{ request('funding_type') === $fundingType ? 'selected' : '' }}>{{ $fundingType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @forelse($scholarships as $scholarship)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm rounded-4 overflow-hidden border-0">
                    @if($scholarship->banner_image)
                        <img src="{{ $scholarship->banner_url }}" class="card-img-top" alt="{{ $scholarship->title }}" style="height:220px; object-fit:cover;">
                    @endif
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <span class="badge bg-{{ $scholarship->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($scholarship->status) }}</span>
                            @if($scholarship->is_featured)
                                <span class="badge bg-warning text-dark">Featured</span>
                            @endif
                        </div>
                        <h5 class="card-title">{{ $scholarship->title }}</h5>
                        <p class="text-muted mb-2">{{ $scholarship->institution }}</p>
                        <p class="text-muted small mb-3">{{ $scholarship->country }} · {{ $scholarship->category }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Deadline: {{ $scholarship->application_deadline->format('M d, Y') }}</span>
                            <a href="{{ route('public.scholarships.show', $scholarship) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <p class="mb-2 text-muted">No scholarships found.</p>
                <p class="text-muted">Try adjusting your search or filters.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $scholarships->links() }}
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Work Packages')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Work Packages</h1>
            <p class="text-muted mb-0">Explore the AdEMNEA project work packages.</p>
        </div>
    </div>

    <div class="row g-4">
        @forelse($workPackages as $package)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm rounded-4 overflow-hidden" style="border:1px solid rgba(27,48,34,0.08);">
                    @if($package->featured_image)
                        <img src="{{ $package->featured_image_url }}" class="card-img-top" alt="{{ $package->title }}" style="height:240px;object-fit:cover;" loading="lazy">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:240px;">
                            <i class="bi bi-image fs-1 text-muted"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        <span class="badge bg-primary mb-2">{{ $package->wp_number }}</span>
                        <h5 class="card-title">{{ $package->title }}</h5>
                        <p class="card-text text-muted">{{ Str::limit($package->summary, 120) }}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">{{ $package->lead }}</small>
                            <a href="{{ route('public.work-packages.show', $package) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-folder2-open fs-1 text-muted"></i>
                    <h5 class="mt-3">No Work Packages Available</h5>
                    <p class="text-muted">Published work packages will appear here.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($workPackages->hasPages())
        <div class="mt-4">
            {{ $workPackages->links() }}
        </div>
    @endif
</div>
@endsection
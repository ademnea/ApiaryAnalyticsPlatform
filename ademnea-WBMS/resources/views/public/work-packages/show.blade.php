@extends('layouts.app')

@section('title', $workPackage->title)

@section('content')
<div class="container-fluid mt-4">

    {{-- Banner --}}
    <div class="rounded-4 overflow-hidden shadow-sm mb-4">
        @if($workPackage->featured_image)
            <img src="{{ $workPackage->featured_image_url }}" class="img-fluid w-100" alt="{{ $workPackage->title }}" style="max-height: 420px; object-fit: cover;">
        @else
            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                <i class="bi bi-image fs-1 text-muted"></i>
            </div>
        @endif
        <div class="p-4 bg-white border-top rounded-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <span class="badge bg-primary mb-2">{{ $workPackage->wp_number }}</span>
                    <h1 class="h3 mb-2">{{ $workPackage->title }}</h1>
                    <p class="text-muted mb-1">{{ $workPackage->lead }}</p>
                    <span class="badge bg-success">Published</span>
                </div>
                <div class="text-lg-end">
                    <a href="{{ route('public.work-packages.index') }}" class="btn btn-outline-secondary">Back to Work Packages</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Content --}}
        <div class="col-lg-8">
            @if($workPackage->summary)
                <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                    <h5>Summary</h5>
                    <p class="text-muted">{{ $workPackage->summary }}</p>
                </div>
            @endif

            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Description</h5>
                <p style="white-space: pre-line">{{ $workPackage->description }}</p>
            </div>

            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Objectives</h5>
                <p style="white-space: pre-line">{{ $workPackage->objectives }}</p>
            </div>

            <div class="card shadow-sm rounded-4 border-0 p-4">
                <h5>Deliverables</h5>
                <p style="white-space: pre-line">{{ $workPackage->deliverables }}</p>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Work Package Details</h5>
                <p class="mb-2"><strong>Work Package Number:</strong> {{ $workPackage->wp_number }}</p>
                <p class="mb-2"><strong>Lead:</strong> {{ $workPackage->lead }}</p>
                @if($workPackage->partners)
                    <p class="mb-2"><strong>Partners:</strong></p>
                    <p style="white-space: pre-line">{{ $workPackage->partners }}</p>
                @endif
                <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Published</span></p>
            </div>
        </div>
    </div>
</div>
@endsection
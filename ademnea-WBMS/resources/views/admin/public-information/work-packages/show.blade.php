@extends('layouts.app')

@section('title', 'Work Package Details')

@section('content')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Work Package Details
            </h2>

            <p class="text-muted mb-0">
                View complete information about this work package.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="{{ route('admin.work-packages.index') }}"
                class="btn btn-outline-secondary rounded-pill">

                <i class="bi bi-arrow-left me-2"></i>

                Back

            </a>

            <a
                href="{{ route('admin.work-packages.edit',$workPackage) }}"
                class="btn btn-success rounded-pill">

                <i class="bi bi-pencil me-2"></i>

                Edit

            </a>

        </div>

    </div>



    {{-- Banner --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">

        @if($workPackage->featured_image)

            <img
                src="{{ $workPackage->featured_image_url }}"
                class="img-fluid w-100"
                style="height:350px; object-fit:cover;">

        @else

            <div
                class="d-flex justify-content-center align-items-center bg-light"
                style="height:350px;">

                <div class="text-center">

                    <i class="bi bi-image fs-1 text-muted"></i>

                    <p class="text-muted mt-3 mb-0">

                        No Featured Image

                    </p>

                </div>

            </div>

        @endif

    </div>



    <div class="row">

        {{-- LEFT --}}
        <div class="col-lg-8">

            <div class="card shadow-sm border-0 rounded-4 mb-4">

                <div class="card-body">

                    <span class="badge bg-primary mb-3">

                        {{ $workPackage->wp_number }}

                    </span>

                    <h2 class="fw-bold">

                        {{ $workPackage->title }}

                    </h2>

                    <hr>

                    <h5 class="fw-bold">

                        Summary

                    </h5>

                    <p class="text-muted">

                        {{ $workPackage->summary ?: 'No summary provided.' }}

                    </p>

                    <hr>

                    <h5 class="fw-bold">

                        Description

                    </h5>

                    <p style="white-space: pre-line">

                        {{ $workPackage->description }}

                    </p>

                    <hr>

                    <h5 class="fw-bold">

                        Objectives

                    </h5>

                    <p style="white-space: pre-line">

                        {{ $workPackage->objectives }}

                    </p>

                    <hr>

                    <h5 class="fw-bold">

                        Deliverables

                    </h5>

                    <p style="white-space: pre-line">

                        {{ $workPackage->deliverables }}

                    </p>

                </div>

            </div>

        </div>



        {{-- RIGHT --}}
        <div class="col-lg-4">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-header bg-white">

                    <h5 class="fw-bold mb-0">

                        Information

                    </h5>

                </div>

                <div class="card-body">

                    <div class="mb-4">

                        <small class="text-muted">

                            Lead Institution

                        </small>

                        <div class="fw-semibold">

                            {{ $workPackage->lead }}

                        </div>

                    </div>



                    <div class="mb-4">

                        <small class="text-muted">

                            Partners

                        </small>

                        <div style="white-space: pre-line">

                            {{ $workPackage->partners ?: '-' }}

                        </div>

                    </div>



                    <div class="mb-4">

                        <small class="text-muted">

                            Status

                        </small>

                        <div>

                            @if($workPackage->status=='Published')

                                <span class="badge bg-success">

                                    Published

                                </span>

                            @elseif($workPackage->status=='Draft')

                                <span class="badge bg-warning text-dark">

                                    Draft

                                </span>

                            @else

                                <span class="badge bg-danger">

                                    Archived

                                </span>

                            @endif

                        </div>

                    </div>



                    <div class="mb-4">

                        <small class="text-muted">

                            Display Order

                        </small>

                        <div class="fw-semibold">

                            {{ $workPackage->display_order }}

                        </div>

                    </div>



                    <div class="mb-4">

                        <small class="text-muted">

                            Created

                        </small>

                        <div>

                            {{ $workPackage->created_at->format('d M Y') }}

                        </div>

                    </div>



                    <div>

                        <small class="text-muted">

                            Last Updated

                        </small>

                        <div>

                            {{ $workPackage->updated_at->format('d M Y') }}

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
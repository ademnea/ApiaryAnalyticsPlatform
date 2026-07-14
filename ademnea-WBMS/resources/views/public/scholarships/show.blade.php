@extends('layouts.app')

@section('title', $scholarship->title)

@section('content')
<div class="container-fluid mt-4">
    <div class="rounded-4 overflow-hidden shadow-sm mb-4">
        @if($scholarship->banner_image)
            <img src="{{ $scholarship->banner_url }}" class="img-fluid w-100" alt="{{ $scholarship->title }}" style="max-height: 420px; object-fit: cover;">
        @endif
        <div class="p-4 bg-white border-top rounded-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h1 class="h3 mb-2">{{ $scholarship->title }}</h1>
                    <p class="text-muted mb-1">{{ $scholarship->institution }} · {{ $scholarship->country }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-success">{{ ucfirst($scholarship->status) }}</span>
                        @if($scholarship->is_featured)
                            <span class="badge bg-warning text-dark">Featured</span>
                        @endif
                    </div>
                </div>
                <div class="text-lg-end">
                    <a href="{{ $scholarship->application_link }}" target="_blank" class="btn btn-primary">Apply Now</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Description</h5>
                <p>{{ $scholarship->description }}</p>
            </div>
            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Eligibility</h5>
                <p>{{ $scholarship->eligibility }}</p>
            </div>
            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Benefits</h5>
                <p>{{ $scholarship->benefits }}</p>
            </div>
            <div class="card shadow-sm rounded-4 border-0 p-4">
                <h5>Application Procedure</h5>
                <p>{{ $scholarship->application_procedure }}</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-4 border-0 p-4 mb-4">
                <h5>Scholarship details</h5>
                <p class="mb-2"><strong>Funding Type:</strong> {{ $scholarship->funding_type }}</p>
                @if($scholarship->funding_amount)
                    <p class="mb-2"><strong>Funding Amount:</strong> {{ $scholarship->currency }} {{ number_format($scholarship->funding_amount, 2) }}</p>
                @endif
                <p class="mb-2"><strong>Category:</strong> {{ $scholarship->category }}</p>
                <p class="mb-2"><strong>Deadline:</strong> {{ $scholarship->application_deadline->format('M d, Y') }}</p>
                <p class="mb-2"><strong>Application Link:</strong> <a href="{{ $scholarship->application_link }}" target="_blank">Open</a></p>
                <p class="mb-0"><strong>Attachments:</strong> {{ $scholarship->attachments->count() }}</p>
            </div>
            @if($scholarship->attachments->isNotEmpty())
                <div class="card shadow-sm rounded-4 border-0 p-4">
                    <h5>Attachments</h5>
                    <ul class="list-group list-group-flush">
                        @foreach($scholarship->attachments as $attachment)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <span>{{ $attachment->file_name }}</span>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $scholarship->title)

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm rounded-4 border-0 overflow-hidden">
        @if($scholarship->banner_image)
            <img src="{{ $scholarship->banner_url }}" class="img-fluid w-100" alt="{{ $scholarship->title }}" style="max-height: 360px; object-fit: cover;">
        @endif
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-2">{{ $scholarship->title }}</h1>
                    <p class="text-muted mb-1">{{ $scholarship->institution }} · {{ $scholarship->country }}</p>
                    <span class="badge bg-{{ $scholarship->status === 'active' ? 'success' : ($scholarship->status === 'expired' ? 'danger' : 'secondary') }}">{{ ucfirst($scholarship->status) }}</span>
                    @if($scholarship->is_featured)
                        <span class="badge bg-warning text-dark">Featured</span>
                    @endif
                </div>
                <div class="text-end">
                    <a href="{{ route('admin.scholarship.edit', $scholarship) }}" class="btn btn-outline-secondary">Edit</a>
                    <form action="{{ route('admin.scholarship.destroy', $scholarship) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this scholarship?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p>{{ $scholarship->description }}</p>
                    </div>
                    <div class="mb-4">
                        <h5>Eligibility</h5>
                        <p>{{ $scholarship->eligibility }}</p>
                    </div>
                    <div class="mb-4">
                        <h5>Benefits</h5>
                        <p>{{ $scholarship->benefits }}</p>
                    </div>
                    <div class="mb-4">
                        <h5>Application Procedure</h5>
                        <p>{{ $scholarship->application_procedure }}</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card rounded-4 shadow-sm border-0 p-3">
                        <h5 class="mb-3">Details</h5>
                        <p class="mb-2"><strong>Country:</strong> {{ $scholarship->country }}</p>
                        <p class="mb-2"><strong>Category:</strong> {{ $scholarship->category }}</p>
                        <p class="mb-2"><strong>Funding:</strong> {{ $scholarship->funding_type }} @if($scholarship->funding_amount) · {{ $scholarship->currency }} {{ number_format($scholarship->funding_amount, 2) }} @endif</p>
                        <p class="mb-2"><strong>Deadline:</strong> {{ $scholarship->application_deadline->format('M d, Y') }}</p>
                        <p class="mb-2"><strong>Application Link:</strong> <a href="{{ $scholarship->application_link }}" target="_blank">Apply now</a></p>
                        <p class="mb-2"><strong>Attachments:</strong> {{ $scholarship->attachments->count() }}</p>
                        @if($scholarship->attachments->isNotEmpty())
                            <div class="mt-3">
                                <h6>Attachments</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach($scholarship->attachments as $attachment)
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
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
        </div>
    </div>
</div>
@endsection

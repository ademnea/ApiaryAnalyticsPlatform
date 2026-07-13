@extends('layouts.app')

@section('title', 'Edit Scholarship')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm rounded-4 border-0 overflow-hidden">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-2">Edit Scholarship</h2>
                    <p class="text-muted mb-0">Update scholarship details and attachments.</p>
                </div>
                <span class="badge rounded-pill text-bg-warning text-dark">Scholarship management</span>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.scholarship.update', $scholarship) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $scholarship->title) }}" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Institution</label>
                        <input type="text" name="institution" class="form-control" value="{{ old('institution', $scholarship->institution) }}" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-select" required>
                            @php
                                $selectedCountry = old('country', $scholarship->country);
                            @endphp
                            <option value="" {{ $selectedCountry ? '' : 'selected' }} disabled>Select Country</option>
                            @foreach(config('scholarship_options.countries') as $country)
                                <option value="{{ $country }}" {{ $selectedCountry === $country ? 'selected' : '' }}>{{ $country }}</option>
                            @endforeach
                            @if($selectedCountry && !in_array($selectedCountry, config('scholarship_options.countries'), true))
                                <option value="{{ $selectedCountry }}" selected>{{ $selectedCountry }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            @php
                                $selectedCategory = old('category', $scholarship->category);
                            @endphp
                            <option value="" {{ $selectedCategory ? '' : 'selected' }} disabled>Select Category</option>
                            @foreach(config('scholarship_options.categories') as $category)
                                <option value="{{ $category }}" {{ $selectedCategory === $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                            @if($selectedCategory && !in_array($selectedCategory, config('scholarship_options.categories'), true))
                                <option value="{{ $selectedCategory }}" selected>{{ $selectedCategory }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Funding Type</label>
                        <input type="text" name="funding_type" class="form-control" value="{{ old('funding_type', $scholarship->funding_type) }}" required>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Funding Amount</label>
                        <input type="number" step="0.01" name="funding_amount" class="form-control" value="{{ old('funding_amount', $scholarship->funding_amount) }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', $scholarship->currency) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required>{{ old('description', $scholarship->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Eligibility</label>
                        <textarea name="eligibility" class="form-control" rows="4" required>{{ old('eligibility', $scholarship->eligibility) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Benefits</label>
                        <textarea name="benefits" class="form-control" rows="4" required>{{ old('benefits', $scholarship->benefits) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Application Procedure</label>
                        <textarea name="application_procedure" class="form-control" rows="4" required>{{ old('application_procedure', $scholarship->application_procedure) }}</textarea>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" name="application_deadline" class="form-control" value="{{ old('application_deadline', $scholarship->application_deadline->toDateString()) }}" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Application Link</label>
                        <input type="url" name="application_link" class="form-control" value="{{ old('application_link', $scholarship->application_link) }}">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status', $scholarship->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $scholarship->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status', $scholarship->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $scholarship->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">Feature this scholarship</label>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Banner Image</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        @if($scholarship->banner_image)
                            <img src="{{ $scholarship->banner_url }}" alt="Banner" class="img-fluid rounded mt-2" style="max-height:160px;">
                        @endif
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Attachments (PDF)</label>
                        <input type="file" name="attachment_files[]" class="form-control" multiple accept="application/pdf">
                        <div class="form-text">Upload additional PDF attachments.</div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary px-4" style="background: #1B3022; border-color: #1B3022;">Save Changes</button>
                    <a href="{{ route('admin.scholarship.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>

            @if($scholarship->attachments->isNotEmpty())
                <div class="mt-5">
                    <h5>Attachments</h5>
                    <ul class="list-group">
                        @foreach($scholarship->attachments as $attachment)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
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

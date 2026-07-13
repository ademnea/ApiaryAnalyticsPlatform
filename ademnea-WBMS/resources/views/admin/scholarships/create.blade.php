@extends('layouts.app')

@section('title', 'Create Scholarship')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm rounded-4 border-0 overflow-hidden">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-2">Create Scholarship</h2>
                    <p class="text-muted mb-0">Add scholarship details, upload a banner, and attach application files.</p>
                </div>
                <span class="badge rounded-pill text-bg-warning text-dark">Scholarship management</span>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.scholarship.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Institution</label>
                        <input type="text" name="institution" class="form-control" value="{{ old('institution') }}" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-select" required>
                            <option value="" disabled selected>Select Country</option>
                            @foreach(config('scholarship_options.countries') as $country)
                                <option value="{{ $country }}" {{ old('country') === $country ? 'selected' : '' }}>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="" disabled selected>Select Category</option>
                            @foreach(config('scholarship_options.categories') as $category)
                                <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Funding Type</label>
                        <input type="text" name="funding_type" class="form-control" value="{{ old('funding_type') }}" required>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Funding Amount</label>
                        <input type="number" step="0.01" name="funding_amount" class="form-control" value="{{ old('funding_amount') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Eligibility</label>
                        <textarea name="eligibility" class="form-control" rows="4" required>{{ old('eligibility') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Benefits</label>
                        <textarea name="benefits" class="form-control" rows="4" required>{{ old('benefits') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Application Procedure</label>
                        <textarea name="application_procedure" class="form-control" rows="4" required>{{ old('application_procedure') }}</textarea>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" name="application_deadline" class="form-control" value="{{ old('application_deadline') }}" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Application Link</label>
                        <input type="url" name="application_link" class="form-control" value="{{ old('application_link') }}">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">Feature this scholarship</label>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Banner Image</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Attachments (PDF)</label>
                        <input type="file" name="attachment_files[]" class="form-control" multiple accept="application/pdf">
                        <div class="form-text">Upload one or more PDF attachments.</div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary px-4" style="background: #1B3022; border-color: #1B3022;">Save Scholarship</button>
                    <a href="{{ route('admin.scholarship.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

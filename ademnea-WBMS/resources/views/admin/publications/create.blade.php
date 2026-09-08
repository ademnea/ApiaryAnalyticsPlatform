@extends('layouts.app')

@section('title', 'Create Publication')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0">Create Publication</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Validation Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.publications.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="author" class="form-label">Author/Researcher Name <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('author') is-invalid @enderror"
                            id="author"
                            name="author"
                            value="{{ old('author') }}"
                            required
                        >
                        @error('author')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Publication Title <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            required
                        >
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="publisher" class="form-label">Publisher <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('publisher') is-invalid @enderror"
                            id="publisher"
                            name="publisher"
                            value="{{ old('publisher') }}"
                            required
                        >
                        @error('publisher')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="publication_year" class="form-label">Publication Year <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            class="form-control @error('publication_year') is-invalid @enderror"
                            id="publication_year"
                            name="publication_year"
                            value="{{ old('publication_year', now()->year) }}"
                            min="1900"
                            max="{{ now()->year }}"
                            required
                        >
                        @error('publication_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Abstract/Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="4"
                    >{{ old('description') }}</textarea>
                    <small class="text-muted">Max 1000 characters</small>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="attachment" class="form-label">PDF Attachment <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control @error('attachment') is-invalid @enderror"
                            id="attachment"
                            name="attachment"
                            accept=".pdf"
                            required
                        >
                        <small class="text-muted">PDF only, max 10 MB</small>
                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image" class="form-label">Cover Image (Optional)</label>
                        <input
                            type="file"
                            class="form-control @error('image') is-invalid @enderror"
                            id="image"
                            name="image"
                            accept="image/*"
                        >
                        <small class="text-muted">JPG, PNG, WEBP, GIF - max 5 MB</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Publication
                    </button>
                    <a href="{{ route('admin.publications.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

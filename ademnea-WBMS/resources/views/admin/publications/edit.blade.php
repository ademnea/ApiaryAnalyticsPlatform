@extends('layouts.app')

@section('title', 'Edit Publication')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0">Edit Publication</h1>
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
            <form action="{{ route('admin.publications.update', $publication->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="author" class="form-label">Author/Researcher Name <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('author') is-invalid @enderror"
                            id="author"
                            name="author"
                            value="{{ old('author', $publication->author) }}"
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
                            value="{{ old('title', $publication->title) }}"
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
                            value="{{ old('publisher', $publication->publisher) }}"
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
                            value="{{ old('publication_year', $publication->publication_year) }}"
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
                    >{{ old('description', $publication->description) }}</textarea>
                    <small class="text-muted">Max 1000 characters</small>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="attachment" class="form-label">PDF Attachment</label>
                        @if($publication->attachment_filename)
                            <div class="alert alert-info mb-2">
                                <strong>Current file:</strong> {{ $publication->attachment_filename }}
                                <small class="d-block text-muted">Upload a new file to replace</small>
                            </div>
                        @endif
                        <input
                            type="file"
                            class="form-control @error('attachment') is-invalid @enderror"
                            id="attachment"
                            name="attachment"
                            accept=".pdf"
                        >
                        <small class="text-muted">PDF only, max 10 MB - Leave empty to keep current file</small>
                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image" class="form-label">Cover Image</label>
                        @if($publication->image_filename)
                            <div class="alert alert-info mb-2">
                                <strong>Current image:</strong> {{ $publication->image_filename }}
                                <small class="d-block text-muted">Upload a new image to replace</small>
                            </div>
                        @else
                            <div class="alert alert-secondary mb-2">
                                <small>No cover image currently set</small>
                            </div>
                        @endif
                        <input
                            type="file"
                            class="form-control @error('image') is-invalid @enderror"
                            id="image"
                            name="image"
                            accept="image/*"
                        >
                        <small class="text-muted">JPG, PNG, WEBP, GIF - max 5 MB - Leave empty to keep current image</small>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Publication
                    </button>
                    <a href="{{ route('admin.publications.show', $publication->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

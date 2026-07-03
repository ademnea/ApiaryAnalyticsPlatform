@extends('layouts.app')

@section('title', 'Edit Gallery Album')
@section('page-title', 'Edit Gallery Album')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.gallery.index') }}">Gallery</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.gallery.update', $gallery) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $gallery->title) }}" required>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $gallery->slug) }}" placeholder="gallery-album-title">
                        <div class="form-text">Leave blank to generate automatically from the title.</div>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Status</label>
                        <select name="is_published" class="form-select">
                            <option value="1" {{ old('is_published', $gallery->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Published</option>
                            <option value="0" {{ old('is_published', $gallery->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-lg-8">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $gallery->description) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('category', $gallery->category) === $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select">
                            @foreach($visibilityOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('visibility', $gallery->visibility) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="is_published" class="form-select">
                            <option value="1" {{ old('is_published', $gallery->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Published</option>
                            <option value="0" {{ old('is_published', $gallery->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cover image</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        @if($gallery->cover_image)
                            <img src="{{ Storage::disk('public')->url($gallery->cover_image) }}" alt="Cover" class="img-fluid mt-2 rounded" style="max-height:160px;">
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Add images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Album Images</h5>
            <div class="row g-3">
                @forelse($gallery->images as $image)
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->file_name }}" class="img-fluid rounded mb-2" style="height:140px;object-fit:cover;width:100%;">
                            <div class="small text-muted mb-2">{{ $image->file_name }}</div>
                            <form method="POST" action="{{ route('admin.gallery.images.replace', $image) }}" enctype="multipart/form-data" class="mb-2">
                                @csrf
                                <input type="file" name="image" class="form-control form-control-sm mb-2" accept="image/jpeg,image/png,image/webp">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Replace</button>
                            </form>
                            <form method="POST" action="{{ route('admin.gallery.images.delete', $image) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete this image?');">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">No images in this album yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Gallery Album')
@section('page-title', 'Edit Gallery Album')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.gallery.index') }}">Gallery</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="container-fluid mt-4">

    {{-- Success flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-1">Edit Gallery Album</h2>
                    <p class="text-muted mb-0 small">Update the album details, cover image, or add more photos.</p>
                </div>
                <span class="badge rounded-pill text-bg-warning text-dark">AdEMNEA Beehive Monitoring System — NORHED II / Makerere University</span>
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

            <form method="POST" action="{{ route('admin.gallery.update', $gallery) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $gallery->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $gallery->slug) }}"
                               placeholder="leave-blank-to-auto-generate">
                        <div class="form-text">Leave blank to generate automatically from the title.</div>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Status</label>
                        <select name="is_published" class="form-select">
                            <option value="1" {{ old('is_published', $gallery->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Published</option>
                            <option value="0" {{ old('is_published', $gallery->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select @error('visibility') is-invalid @enderror">
                            @foreach($visibilityOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('visibility', $gallery->visibility) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('visibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Category <small class="text-muted">optional</small></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror">
                            <option value="">— None —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ old('category', $gallery->category) === $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Write a short summary of the gallery.">{{ old('description', $gallery->description) }}</textarea>
                    </div>

                    {{-- Cover image --}}
                    <div class="col-md-6">
                        <label class="form-label">Cover image</label>
                        <input type="file" name="cover_image"
                               class="form-control @error('cover_image') is-invalid @enderror"
                               accept="image/*">
                        <div class="form-text">Any image format. Max 10 MB.</div>
                        @error('cover_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($gallery->cover_image)
                            @php $coverUrl = asset('storage/' . $gallery->cover_image); @endphp
                            <img src="{{ $coverUrl }}"
                                 alt="Current cover"
                                 class="img-fluid mt-2 rounded"
                                 style="max-height:160px; object-fit:cover;"
                                 onerror="this.closest('.cover-preview')?.remove()">
                            <div class="form-text text-muted mt-1">Current cover — uploading a new file will replace it.</div>
                        @endif
                    </div>

                    {{-- Add more images --}}
                    <div class="col-md-6">
                        <label class="form-label">Add images</label>
                        <div class="border border-dashed rounded-4 p-3 text-center" style="border-color: rgba(27,48,34,0.25); background: rgba(212,175,55,0.05);">
                            <p class="mb-2 text-muted small">Select one or more image files to add to this album.</p>
                            <input type="file" name="images[]"
                                   class="form-control border-0 p-0 @error('images.*') is-invalid @enderror"
                                   multiple accept="image/*">
                            <div class="form-text">Any image format accepted. Max 10 MB per file.</div>
                        </div>
                        @error('images.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-success px-4">Save Changes</button>
                    <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Existing album images --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Album Images
                <span class="badge bg-secondary ms-2">{{ $gallery->images->count() }}</span>
            </h5>

            @if($gallery->images->isEmpty())
                <p class="text-muted">No images in this album yet. Use the form above to add some.</p>
            @else
                <div class="row g-3">
                    @foreach($gallery->images as $image)
                        @php $imgUrl = asset('storage/' . $image->path); @endphp
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="border rounded-3 p-2 h-100 d-flex flex-column">
                                {{-- Image preview with fallback --}}
                                <div class="mb-2 rounded overflow-hidden bg-light d-flex align-items-center justify-content-center"
                                     style="height:130px;">
                                    <img src="{{ $imgUrl }}"
                                         alt="{{ $image->file_name }}"
                                         class="img-fluid"
                                         style="max-height:130px; object-fit:cover; width:100%;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="text-muted small text-center p-2" style="display:none;">
                                        <i class="bi bi-image fs-3 d-block mb-1"></i>
                                        File not found
                                    </div>
                                </div>

                                <div class="small text-muted text-truncate mb-2" title="{{ $image->file_name }}">
                                    {{ $image->file_name }}
                                </div>

                                {{-- Replace --}}
                                <form method="POST"
                                      action="{{ route('admin.gallery.images.replace', $image) }}"
                                      enctype="multipart/form-data"
                                      class="mb-2">
                                    @csrf
                                    <input type="file" name="image"
                                           class="form-control form-control-sm mb-2"
                                           accept="image/*">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Replace</button>
                                </form>

                                {{-- Delete --}}
                                <form method="POST"
                                      action="{{ route('admin.gallery.images.delete', $image) }}"
                                      class="mt-auto">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger w-100"
                                            onclick="return confirm('Delete this image permanently?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

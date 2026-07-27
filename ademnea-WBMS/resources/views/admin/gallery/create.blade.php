@extends('layouts.app')

@section('title', 'Create Gallery Album')
@section('page-title', 'Create Gallery Album')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.gallery.index') }}">Gallery</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <h2 class="h4 mb-2">Create a new gallery album</h2>
                            <p class="text-muted mb-0">Add a title, description and upload cover and image files for your album.</p>
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

                    <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">Album Title</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Slug <small class="text-muted">optional</small></label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="gallery-album-title">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Status</label>
                                <select name="is_published" class="form-select">
                                    <option value="1" {{ old('is_published', '1') == '1' ? 'selected' : '' }}>Published</option>
                                    <option value="0" {{ old('is_published') == '0' ? 'selected' : '' }}>Draft</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Visibility</label>
                                <select name="visibility" class="form-select @error('visibility') is-invalid @enderror">
                                    @foreach($visibilityOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('visibility', 'public') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('visibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Category <small class="text-muted">optional</small></label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="">— None —</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" {{ old('category') === $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Cover image</label>
                                <input type="file" name="cover_image" class="form-control @error('cover_image') is-invalid @enderror" accept="image/*">
                                <div class="form-text">Any image format accepted. Max 10 MB.</div>
                                @error('cover_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Write a short summary of the gallery.">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Album images</label>
                                <div class="border border-dashed rounded-4 p-4 text-center" style="border-color: rgba(27, 48, 34, 0.25); background: rgba(212, 175, 55, 0.05);">
                                    <p class="mb-2 text-muted">Drag &amp; drop image files here or click to browse.</p>
                                    <input type="file" name="images[]" class="form-control border-0 p-0" multiple accept="image/*">
                                    <div class="form-text">Any image format accepted. Max 10 MB per file.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-primary px-4" style="background: #1B3022; border-color: #1B3022;">Save Album</button>
                            <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

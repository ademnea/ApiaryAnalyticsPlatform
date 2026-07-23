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
            <form id="gallery-edit-form" method="POST" action="{{ route('admin.gallery.update', $gallery) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $gallery->title) }}" required>
                        @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $gallery->slug) }}" placeholder="leave-blank-to-auto-generate">
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
                        <label class="form-label">Visibility <span class="text-danger">*</span></label>
                        <select name="visibility" class="form-select" required>
                            @foreach($visibilityOptions as $key => $label)
                                <option value="{{ $key }}" {{ old('visibility', $gallery->visibility) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('visibility') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="is_published" class="form-select">
                            <option value="1" {{ old('is_published', $gallery->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Published</option>
                            <option value="0" {{ old('is_published', $gallery->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>

                    {{-- Cover image --}}
                    <div class="col-md-6">
                        <label class="form-label">Cover image</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        @if($gallery->cover_image)
                            <img src="{{ Storage::disk('public')->url($gallery->cover_image) }}" alt="Cover" class="img-fluid mt-2 rounded" style="max-height:160px;">
                        @endif
                    </div>

                    {{-- Add more images --}}
                    <div class="col-md-6">
                        <label class="form-label">Add images</label>
                        <div class="border border-dashed rounded-4 p-3 text-center" id="upload-zone" style="border-color: rgba(27, 48, 34, 0.25); background: rgba(212, 175, 55, 0.05); cursor: pointer;">
                            <p class="mb-2 text-muted small">Drag &amp; drop image files here or click to browse.</p>
                            <input type="file" name="images[]" id="gallery-images-input" class="form-control border-0 p-0" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                            <div class="form-text">You may select multiple images at once. Max file size: 30 MB each.</div>
                        </div>
                        <div id="upload-progress-area" class="d-none mt-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span id="upload-status-text">Uploading...</span>
                                <span id="upload-percent">0%</span>
                            </div>
                            <div class="progress" style="height: 0.5rem;">
                                <div class="progress-bar" id="upload-progress-bar" role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
                        <div id="selected-count" class="mt-2 small text-muted d-none">
                            <span id="count-number">0</span> additional images selected
                        </div>
                        <div class="row g-2 mt-2" id="preview-grid"></div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-animate">Save Changes</button>
                    <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary btn-animate">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Existing album images --}}
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
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100 btn-animate">Replace</button>
                            </form>
                            <form method="POST" action="{{ route('admin.gallery.images.delete', $image) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 btn-animate" onclick="return confirm('Delete this image?');">Delete</button>
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

@push('scripts')
<script>
(function() {
    'use strict';

    const MAX_SIZE_MB = 30;
    const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    let selectedFiles = [];

    const zone = document.getElementById('upload-zone');
    const input = document.getElementById('gallery-images-input');
    const grid = document.getElementById('preview-grid');
    const countBadge = document.getElementById('selected-count');
    const countNumber = document.getElementById('count-number');
    const progressArea = document.getElementById('upload-progress-area');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-percent');
    const statusText = document.getElementById('upload-status-text');

    zone.addEventListener('click', () => input.click());

    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.style.borderColor = 'var(--clr-forest-mid)';
        zone.style.background = 'var(--clr-forest-pale)';
    });

    zone.addEventListener('dragleave', () => {
        zone.style.borderColor = 'rgba(27, 48, 34, 0.25)';
        zone.style.background = 'rgba(212, 175, 55, 0.05)';
    });

    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.style.borderColor = 'rgba(27, 48, 34, 0.25)';
        zone.style.background = 'rgba(212, 175, 55, 0.05)';
        handleFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', () => {
        handleFiles(input.files);
        input.value = '';
    });

    function handleFiles(files) {
        const fileArray = Array.from(files);
        for (const file of fileArray) {
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert('The selected file "' + file.name + '" is not an image. Only JPG, JPEG, PNG and WEBP are allowed.');
                continue;
            }
            if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                alert('The file "' + file.name + '" exceeds the 30 MB size limit.');
                continue;
            }
            selectedFiles.push(file);
        }
        renderPreviews();
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function renderPreviews() {
        grid.innerHTML = '';
        countNumber.textContent = selectedFiles.length;
        countBadge.classList.toggle('d-none', selectedFiles.length === 0);

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-sm-6 col-md-4 col-lg-3';

                const item = document.createElement('div');
                item.className = 'border rounded overflow-hidden';
                item.style.background = '#fff';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                img.className = 'img-fluid';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.style.width = '100%';

                const info = document.createElement('div');
                info.className = 'p-2 small text-muted';
                info.innerHTML = '<div class="text-truncate" title="' + file.name + '">' + file.name + '</div>' +
                    '<div>' + formatBytes(file.size) + '</div>';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger w-100 mt-1';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = () => removeFile(index);

                item.appendChild(img);
                item.appendChild(info);
                item.appendChild(removeBtn);
                col.appendChild(item);
                grid.appendChild(col);

                const tempImg = new Image();
                tempImg.onload = function() {
                    info.innerHTML += '<div>' + tempImg.naturalWidth + ' × ' + tempImg.naturalHeight + '</div>';
                };
                tempImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    const form = document.getElementById('gallery-edit-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        selectedFiles.forEach((file) => {
            formData.append('images[]', file);
        });

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);

        xhr.upload.addEventListener('progress', function(evt) {
            if (evt.lengthComputable) {
                const percent = Math.round((evt.loaded / evt.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        });

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                statusText.textContent = 'Completed!';
                statusText.classList.add('text-success');
                progressBar.classList.add('bg-success');
                setTimeout(() => {
                    window.location.href = '{{ route('admin.gallery.index') }}';
                }, 600);
            } else {
                statusText.textContent = 'Failed';
                statusText.classList.add('text-danger');
                progressBar.classList.add('bg-danger');
                alert('Update failed. Please try again.');
            }
        };

        xhr.onerror = function() {
            statusText.textContent = 'Error';
            statusText.classList.add('text-danger');
            progressBar.classList.add('bg-danger');
            alert('An error occurred during update.');
        };

        progressArea.classList.remove('d-none');
        statusText.textContent = 'Saving...';
        statusText.classList.remove('text-success', 'text-danger');
        progressBar.classList.remove('bg-success', 'bg-danger');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';

        xhr.send(formData);
    });
})();
</script>
@endpush

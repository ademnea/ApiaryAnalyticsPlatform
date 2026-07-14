@extends('layouts.app')

@section('title', $gallery->title)

@section('content')
<div class="container-fluid mt-4">
    <div class="mb-4">
        <a href="{{ route('public.gallery.index') }}" class="btn btn-outline-secondary">Back to Gallery</a>
    </div>

    <div class="row gy-4">
        <div class="col-lg-8">
            @if($gallery->cover_image)
                <img src="{{ Storage::disk('public')->url($gallery->cover_image) }}" class="img-fluid rounded-4 shadow-sm mb-4" alt="{{ $gallery->title }}" style="width:100%; object-fit:cover; max-height:420px;">
            @endif
        </div>
        <div class="col-lg-4">
            <div class="p-4 rounded-4 shadow-sm" style="background:#fff; border:1px solid rgba(27,48,34,0.08);">
                <h1 class="h3">{{ $gallery->title }}</h1>
                <p class="text-muted">{{ $gallery->description }}</p>
                <div class="mb-3">
                    <span class="badge bg-warning text-dark me-2">{{ $gallery->category ?? 'Uncategorized' }}</span>
                    <span class="badge bg-{{ $gallery->visibility === 'public' ? 'success' : 'dark' }}">{{ ucfirst($gallery->visibility) }}</span>
                </div>
                <div class="d-flex gap-3 text-muted small">
                    <div>{{ $gallery->images->count() }} images</div>
                    <div>{{ $gallery->views }} views</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        @forelse($gallery->images as $image)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm overflow-hidden rounded-4">
                    <img src="{{ Storage::disk('public')->url($image->path) }}" class="card-img-top" alt="{{ $image->file_name }}" style="height:260px;object-fit:cover;">
                </div>
            </div>
        @empty
            <div class="col-12">No images in this album yet.</div>
        @endforelse
    </div>
</div>
@endsection

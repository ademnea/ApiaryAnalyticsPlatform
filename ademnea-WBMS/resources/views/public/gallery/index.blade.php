@extends('layouts.app')

@section('title', 'Gallery')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Gallery</h1>
            <p class="text-muted mb-0">Browse public albums, filter by category, and preview images in a modern dashboard experience.</p>
        </div>
    </div>

    <div class="mb-4">
        <div class="btn-group" role="group" aria-label="Category filters">
            <a href="{{ route('public.gallery.index') }}" class="btn btn-outline-secondary {{ empty($selectedCategory) ? 'active' : '' }}">All Albums</a>
            @foreach($categories as $category)
                <a href="{{ route('public.gallery.index', ['category' => $category]) }}" class="btn btn-outline-secondary {{ $selectedCategory === $category ? 'active' : '' }}">{{ $category }}</a>
            @endforeach
        </div>
    </div>

    <div class="row g-4">
        @forelse($albums as $album)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm rounded-4 overflow-hidden" style="border:1px solid rgba(27,48,34,0.08);">
                    @if($album->cover_image)
                        <img src="{{ Storage::disk('public')->url($album->cover_image) }}" class="card-img-top" alt="{{ $album->title }}" style="height:240px;object-fit:cover;">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:240px;">No cover image</div>
                    @endif
                    <div class="card-body">
                        <span class="badge bg-warning text-dark mb-2">{{ $album->category ?? 'Uncategorized' }}</span>
                        <h5 class="card-title">{{ $album->title }}</h5>
                        <p class="card-text text-muted">{{ Str::limit($album->description, 120) }}</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">{{ $album->images_count }} images</small>
                            <a href="{{ route('public.gallery.show', $album) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">No public gallery albums available yet.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $albums->links() }}
    </div>
</div>
@endsection

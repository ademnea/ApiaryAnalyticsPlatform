@extends('layouts.app')

@section('title', 'Gallery Albums')
@section('page-title', 'Gallery Albums')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Gallery</li>
@endsection

@push('styles')
<style>
    .stat-card {
        background: #fff;
        border: 1px solid var(--clr-border);
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(27,67,50,0.1);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .stat-card .stat-value {
        font-family: var(--font-display);
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a2e1f;
        line-height: 1;
    }
    .stat-card .stat-label {
        font-size: 0.8rem;
        color: var(--clr-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }
    .table-row-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    .table-row-hover td {
        transition: background 0.2s ease;
    }
    .table-row-hover:hover td {
        background: rgba(27,67,50,0.02);
    }
    .img-zoom-hover {
        overflow: hidden;
    }
    .img-zoom-hover img {
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .img-zoom-hover:hover img {
        transform: scale(1.1);
    }
    .btn-animate {
        transition: all 0.2s ease;
    }
    .btn-animate:hover {
        transform: translateY(-1px);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
    }
</style>
@endendsection

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Gallery Albums</h2>
            <p class="text-muted mb-0">Manage public gallery albums and their images.</p>
        </div>
        <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary btn-animate" style="background: #1B3022; border-color: #1B3022;">
            <i class="bi bi-plus-circle me-1"></i> Create Album
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3 animate-fade-in-up">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-light text-dark">
                        <i class="bi bi-images"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['totalAlbums'] }}</div>
                        <div class="stat-label">Total Albums</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 animate-fade-in-up" style="animation-delay: 0.05s;">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-light text-dark">
                        <i class="bi bi-file-image"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['totalImages'] }}</div>
                        <div class="stat-label">Total Images</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 animate-fade-in-up" style="animation-delay: 0.1s;">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success text-white">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['publishedAlbums'] }}</div>
                        <div class="stat-label">Published Albums</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 animate-fade-in-up" style="animation-delay: 0.15s;">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning text-dark">
                        <i class="bi bi-eye"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ number_format($stats['totalViews']) }}</div>
                        <div class="stat-label">Total Views</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search albums...">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="category" class="form-select">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="visibility" class="form-select">
                <option value="">All visibility</option>
                @foreach($visibilityOptions as $key => $label)
                    <option value="{{ $key }}" {{ request('visibility') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-light w-100 btn-animate" style="background:var(--clr-forest-mid);">Filter</button>
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary w-100 btn-animate">Reset</a>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Album</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Visibility</th>
                        <th>Images</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($albums as $album)
                        <tr class="align-middle table-row-hover">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="img-zoom-hover rounded" style="width:60px;height:60px;">
                                        @if($album->cover_image)
                                            <img src="{{ Storage::disk('public')->url($album->cover_image) }}" alt="Cover" class="rounded" style="width:60px;height:60px;object-fit:cover;">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;color: var(--clr-muted);">
                                                <i class="bi bi-images"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ $album->title }}</strong>
                                        <div class="small text-muted">{{ Str::limit($album->description, 80) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $album->is_published ? 'bg-success' : 'bg-secondary' }}">{{ $album->is_published ? 'Published' : 'Draft' }}</span>
                                @if($album->trashed())
                                    <span class="badge bg-danger">Trashed</span>
                                @endif
                            </td>
                            <td>{{ $album->category ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $album->visibility === 'public' ? 'bg-success' : 'bg-dark' }}">{{ ucfirst($album->visibility) }}</span>
                            </td>
                            <td>{{ $album->images_count }}</td>
                            <td>{{ $album->updated_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('public.gallery.show', $album) }}" target="_blank" class="btn btn-outline-info btn-animate" title="View album">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.gallery.edit', $album) }}" class="btn btn-outline-primary btn-animate">Edit</a>
                                    <form method="POST" action="{{ route('admin.gallery.destroy', $album) }}" onsubmit="return confirm('Delete this album and all images?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-animate">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No gallery albums found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $albums->links() }}
    </div>
</div>
@endsection

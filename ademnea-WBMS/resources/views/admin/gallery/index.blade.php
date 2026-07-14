@extends('layouts.app')

@section('title', 'Gallery Albums')
@section('page-title', 'Gallery Albums')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Gallery</li>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1">Gallery Albums</h2>
            <p class="text-muted mb-0">Manage public gallery albums and their images.</p>
        </div>
        <a href="{{ route('admin.gallery.create') }}" class="btn btn-success">Create Album</a>
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
            <button type="submit" class="btn btn-outline-light w-100" style="background:var(--clr-forest-mid);">Filter</button>
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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
                        <tr class="align-middle">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($album->cover_image)
                                        <img src="{{ Storage::disk('public')->url($album->cover_image) }}" alt="Cover" class="rounded" style="width:60px;height:60px;object-fit:cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;">No image</div>
                                    @endif
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
                                    <a href="{{ route('public.gallery.show', $album) }}" target="_blank" class="btn btn-outline-info" title="View album">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.gallery.edit', $album) }}" class="btn btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('admin.gallery.destroy', $album) }}" onsubmit="return confirm('Delete this album and all images?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Delete</button>
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

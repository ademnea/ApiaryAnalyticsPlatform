@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ $event->title }}</h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @if ($event->is_published)
                        <form action="{{ route('admin.events.unpublish', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('Unpublish this event?')">
                                <i class="bi bi-eye-slash"></i> Unpublish
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.events.publish', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check"></i> Publish
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this event? This action cannot be undone.')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Event Details</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Venue</dt>
                        <dd class="col-sm-9">{{ $event->venue }}</dd>

                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ $event->event_date->format('l, F j, Y') }}</dd>

                        @if ($event->event_time)
                            <dt class="col-sm-3">Time</dt>
                            <dd class="col-sm-9">{{ $event->event_time->format('H:i') }}</dd>
                        @endif

                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">
                            <p>{{ $event->description }}</p>
                        </dd>

                        @if ($event->article_link)
                            <dt class="col-sm-3">Article Link</dt>
                            <dd class="col-sm-9">
                                <a href="{{ $event->article_link }}" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-link-45deg"></i> View Article
                                </a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Photos</h5>
                </div>
                <div class="card-body">
                    @if ($event->hasPhotos())
                        <div class="row g-3">
                            @foreach ($event->photos as $index => $photo)
                                <div class="col-md-4">
                                    <div class="card">
                                        <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="{{ $event->title }} - Photo {{ $index + 1 }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <small class="text-muted">{{ $photo->photo_filename }}</small>
                                            @if ($index === 0)
                                                <div class="mt-2">
                                                    <span class="badge bg-primary">Primary Photo</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No photos available for this event.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Event Status</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            @if ($event->is_published)
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-warning">Draft</span>
                            @endif
                        </dd>

                        @if ($event->is_published)
                            <dt class="col-sm-5">Published</dt>
                            <dd class="col-sm-7">{{ $event->published_at->format('M d, Y H:i') }}</dd>
                        @endif

                        <dt class="col-sm-5">Creator</dt>
                        <dd class="col-sm-7">{{ $event->creator->name ?? 'Unknown' }}</dd>

                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $event->created_at->format('M d, Y H:i') }}</dd>

                        <dt class="col-sm-5">Updated</dt>
                        <dd class="col-sm-7">{{ $event->updated_at->format('M d, Y H:i') }}</dd>

                        @if ($event->deleted_at)
                            <dt class="col-sm-5">Deleted</dt>
                            <dd class="col-sm-7">{{ $event->deleted_at->format('M d, Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

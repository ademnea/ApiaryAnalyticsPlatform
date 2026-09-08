@extends('layouts.app')

@section('title', 'View Publication')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">{{ $publication->title }}</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.publications.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Publication Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Author:</strong><br>
                            {{ $publication->author }}
                        </div>
                        <div class="col-md-6">
                            <strong>Publisher:</strong><br>
                            {{ $publication->publisher }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Publication Year:</strong><br>
                            {{ $publication->publication_year }}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            @if($publication->is_published)
                                <span class="badge bg-success">Published</span>
                                <small class="text-muted">{{ $publication->published_at->format('M d, Y H:i') }}</small>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif
                        </div>
                    </div>

                    @if($publication->description)
                        <div class="mb-3">
                            <strong>Abstract/Description:</strong><br>
                            <p class="text-muted">{{ $publication->description }}</p>
                        </div>
                    @endif

                    <hr>

                    <!-- Created By -->
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Created by:</strong><br>
                            {{ $publication->creator->name ?? 'Unknown' }}
                            <small class="text-muted d-block">{{ $publication->created_at->format('M d, Y H:i') }}</small>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong><br>
                            <small class="text-muted">{{ $publication->updated_at->format('M d, Y H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Files -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Attachments</h5>
                </div>
                <div class="card-body">
                    @if($publication->attachment_filename)
                        <div class="mb-3">
                            <strong><i class="bi bi-file-pdf"></i> PDF Attachment</strong>
                            <div class="mt-2">
                                <p class="text-muted mb-0">{{ $publication->attachment_filename }}</p>
                                <small class="text-muted">Path: {{ $publication->attachment_path }}</small>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No PDF attachment</p>
                    @endif

                    <hr class="my-3">

                    @if($publication->image_filename)
                        <div>
                            <strong><i class="bi bi-image"></i> Cover Image</strong>
                            <div class="mt-2">
                                @if($publication->image_url)
                                    <img src="{{ $publication->image_url }}"
                                         alt="Cover Image"
                                         class="img-thumbnail"
                                         style="max-width: 200px;">
                                @endif
                                <p class="text-muted mb-0 mt-2">{{ $publication->image_filename }}</p>
                                <small class="text-muted">Path: {{ $publication->image_path }}</small>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No cover image</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.publications.edit', $publication->id) }}"
                           class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit Publication
                        </a>

                        @if(!$publication->is_published)
                            <form action="{{ route('admin.publications.publish', $publication->id) }}"
                                  method="POST">
                                @csrf
                                <button type="submit"
                                        class="btn btn-success w-100"
                                        onclick="return confirm('Publish this publication?')">
                                    <i class="bi bi-check-circle"></i> Publish
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.publications.unpublish', $publication->id) }}"
                                  method="POST">
                                @csrf
                                <button type="submit"
                                        class="btn btn-warning w-100"
                                        onclick="return confirm('Unpublish this publication?')">
                                    <i class="bi bi-x-circle"></i> Unpublish
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('admin.publications.destroy', $publication->id) }}"
                              method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-danger w-100"
                                    onclick="return confirm('Delete this publication permanently? This cannot be undone.')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if($publication->is_published)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Public Link</h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">View published version:</small>
                        <a href="{{ route('public.publications.show', $publication->slug) }}"
                           class="btn btn-outline-primary w-100 mt-2"
                           target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> View Public
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

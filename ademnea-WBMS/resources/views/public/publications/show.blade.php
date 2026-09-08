@extends('layouts.public')

@section('title', $publication->title)

@section('content')
<div class="container">
    <div class="row mb-5">
        <div class="col-lg-8">
            <a href="{{ route('public.publications.index') }}" class="btn btn-link text-decoration-none mb-3">
                <i class="bi bi-arrow-left"></i> Back to Publications
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            @if($publication->image_url)
                <img src="{{ $publication->image_url }}"
                     alt="{{ $publication->title }}"
                     class="img-fluid rounded shadow-sm mb-4"
                     style="max-height: 400px; object-fit: cover; width: 100%;">
            @endif

            <h1 class="mb-3">{{ $publication->title }}</h1>

            <!-- Publication Meta -->
            <div class="row text-muted mb-4 pb-3 border-bottom">
                <div class="col-md-3">
                    <p class="mb-0">
                        <small><strong>Author</strong></small><br>
                        <small>{{ $publication->author }}</small>
                    </p>
                </div>
                <div class="col-md-3">
                    <p class="mb-0">
                        <small><strong>Publisher</strong></small><br>
                        <small>{{ $publication->publisher }}</small>
                    </p>
                </div>
                <div class="col-md-3">
                    <p class="mb-0">
                        <small><strong>Publication Year</strong></small><br>
                        <small>{{ $publication->publication_year }}</small>
                    </p>
                </div>
                <div class="col-md-3">
                    <p class="mb-0">
                        <small><strong>Published</strong></small><br>
                        <small>{{ $publication->published_at->format('M d, Y') }}</small>
                    </p>
                </div>
            </div>

            <!-- Description -->
            @if($publication->description)
                <div class="mb-5">
                    <h4 class="mb-3">Abstract</h4>
                    <p class="text-muted lead">{{ $publication->description }}</p>
                </div>
            @endif

            <!-- Download Section -->
            @if($publication->hasAttachment())
                <div class="card bg-light border-0 mb-5">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <i class="bi bi-file-pdf text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                            <div class="col">
                                <h6 class="mb-1">Download Publication</h6>
                                <p class="text-muted small mb-0">{{ $publication->attachment_filename }}</p>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('publications.download', $publication->id) }}"
                                   class="btn btn-primary"
                                   download>
                                    <i class="bi bi-download"></i> Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Publication Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row small">
                        <dt class="col-sm-5 text-muted">Author:</dt>
                        <dd class="col-sm-7">{{ $publication->author }}</dd>

                        <dt class="col-sm-5 text-muted">Publisher:</dt>
                        <dd class="col-sm-7">{{ $publication->publisher }}</dd>

                        <dt class="col-sm-5 text-muted">Year:</dt>
                        <dd class="col-sm-7">{{ $publication->publication_year }}</dd>

                        <dt class="col-sm-5 text-muted">Published:</dt>
                        <dd class="col-sm-7">{{ $publication->published_at->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Related Publications -->
            @php
                $relatedPublications = \App\Models\Publication::published()
                    ->where('publication_year', $publication->publication_year)
                    ->where('id', '!=', $publication->id)
                    ->limit(3)
                    ->get();
            @endphp

            @if(count($relatedPublications) > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">More from {{ $publication->publication_year }}</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($relatedPublications as $related)
                            <a href="{{ route('public.publications.show', $related->slug) }}"
                               class="list-group-item list-group-item-action py-3">
                                <h6 class="mb-1">{{ Str::limit($related->title, 50) }}</h6>
                                <small class="text-muted">by {{ $related->author }}</small>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Navigation Links -->
    <nav aria-label="Publication navigation" class="mt-5 pt-4 border-top">
        <div class="row">
            <div class="col-md-6">
                @php
                    $previousPub = \App\Models\Publication::published()
                        ->where('id', '<', $publication->id)
                        ->orderBy('id', 'desc')
                        ->first();
                @endphp
                @if($previousPub)
                    <a href="{{ route('public.publications.show', $previousPub->slug) }}"
                       class="btn btn-outline-secondary w-100">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                @endif
            </div>
            <div class="col-md-6">
                @php
                    $nextPub = \App\Models\Publication::published()
                        ->where('id', '>', $publication->id)
                        ->orderBy('id', 'asc')
                        ->first();
                @endphp
                @if($nextPub)
                    <a href="{{ route('public.publications.show', $nextPub->slug) }}"
                       class="btn btn-outline-secondary w-100">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </nav>
</div>
@endsection

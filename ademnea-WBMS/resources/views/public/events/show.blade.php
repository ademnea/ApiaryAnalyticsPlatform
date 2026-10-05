@extends('layouts.public')

@section('title', $event->title)

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            @if ($event->hasPhotos())
                <div id="eventCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($event->photos as $index => $photo)
                            <div class="carousel-item @if ($index === 0) active @endif">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="{{ $event->title }}" class="d-block w-100" style="height: 500px; object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                    @if ($event->photos->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    @endif
                </div>
            @endif

            <div class="mb-4">
                <h1 class="h2">{{ $event->title }}</h1>
                <p class="text-muted">
                    <i class="bi bi-geo-alt"></i> {{ $event->venue }}
                </p>
                <p class="text-muted">
                    <i class="bi bi-calendar-event"></i> {{ $event->event_date->format('l, F j, Y') }}
                    @if ($event->event_time)
                        at {{ $event->event_time->format('H:i') }}
                    @endif
                </p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">About This Event</h5>
                    <p>{{ $event->description }}</p>
                    @if ($event->article_link)
                        <a href="{{ $event->article_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <i class="bi bi-link-45deg"></i> Read Full Article
                        </a>
                    @endif
                </div>
            </div>

            @if ($event->photos->count() > 1)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Photo Gallery</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($event->photos as $photo)
                                <div class="col-md-6">
                                    <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="{{ $event->title }}" class="img-fluid rounded" style="cursor: pointer; max-height: 200px; object-fit: cover; width: 100%;" data-bs-toggle="modal" data-bs-target="#photoModal" onclick="document.getElementById('modalImage').src=this.src;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Event Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $event->event_date->format('M d, Y') }}</dd>

                        <dt class="col-sm-4">Venue</dt>
                        <dd class="col-sm-8">{{ $event->venue }}</dd>

                        @if ($event->photos->count())
                            <dt class="col-sm-4">Photos</dt>
                            <dd class="col-sm-8">{{ $event->photos->count() }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Share Event</h5>
                </div>
                <div class="card-body">
                    <a href="https://facebook.com/sharer/sharer.php?u={{ urlencode(route('public.events.show', $event->slug)) }}" target="_blank" class="btn btn-primary btn-sm mb-2 w-100">
                        <i class="bi bi-facebook"></i> Share on Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('public.events.show', $event->slug)) }}&text={{ urlencode($event->title) }}" target="_blank" class="btn btn-info btn-sm mb-2 w-100">
                        <i class="bi bi-twitter"></i> Share on Twitter
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">{{ $event->title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="{{ $event->title }}" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
@endsection

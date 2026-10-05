@extends('layouts.public')

@section('title', 'Events')

@section('content')
<div class="container py-5">
    <h1 class="h3 mb-4">Events</h1>

    <div class="row g-4">
        @forelse($events as $event)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    @if ($event->primary_photo_url)
                        <img src="{{ $event->primary_photo_url }}" alt="{{ $event->title }}" class="card-img-top" style="height: 250px; object-fit: cover;">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">{{ $event->title }}</h5>
                        <p class="card-text text-muted small">
                            <i class="bi bi-geo-alt"></i> {{ $event->venue }}
                        </p>
                        <p class="card-text small">
                            <i class="bi bi-calendar-event"></i> {{ $event->event_date->format('M d, Y') }}
                            @if ($event->event_time)
                                at {{ $event->event_time->format('H:i') }}
                            @endif
                        </p>
                        <p class="card-text">{{ Str::limit($event->description, 100) }}</p>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="{{ route('public.events.show', $event->slug) }}" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-eye"></i> View Event
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No events found.</p>
            </div>
        @endforelse
    </div>

    @if ($events->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection

@extends('layouts.public')

@section('title', 'Publications')

@section('content')
<div class="container-fluid">
    <div class="row mb-5">
        <div class="col-lg-8">
            <h1 class="display-5 mb-2">Research Publications</h1>
            <p class="lead text-muted">Explore our collection of research publications, reports, and technical documentation.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            @if(count($years) > 0)
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Filter by Year</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('public.publications.index') }}"
                           class="list-group-item list-group-item-action @if(!request('year')) active @endif">
                            All Years
                        </a>
                        @foreach($years as $year)
                            <a href="{{ route('public.publications.index', ['year' => $year]) }}"
                               class="list-group-item list-group-item-action @if(request('year') == $year) active @endif">
                                {{ $year }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-9">
            <!-- Publications Grid -->
            <div class="row">
                @forelse($publications as $pub)
                    <div class="col-md-6 mb-4">
                        <a href="{{ route('public.publications.show', $pub->slug) }}" class="text-decoration-none">
                            <div class="card h-100 shadow-sm hover-shadow transition">
                                @if($pub->image_url)
                                    <img src="{{ $pub->image_url }}"
                                         alt="{{ $pub->title }}"
                                         class="card-img-top"
                                         style="height: 200px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                         style="height: 200px;">
                                        <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @endif

                                <div class="card-body">
                                    <p class="card-text text-muted small mb-2">
                                        <i class="bi bi-person"></i> {{ $pub->author }}
                                    </p>

                                    <h5 class="card-title text-dark">{{ $pub->title }}</h5>

                                    <p class="card-text text-muted small mb-2">
                                        <i class="bi bi-building"></i> {{ $pub->publisher }}
                                    </p>

                                    <p class="card-text text-muted small">
                                        <i class="bi bi-calendar"></i> {{ $pub->publication_year }}
                                    </p>

                                    @if($pub->description)
                                        <p class="card-text text-muted small">
                                            {{ Str::limit($pub->description, 100) }}
                                        </p>
                                    @endif
                                </div>

                                <div class="card-footer bg-white border-top-0">
                                    <small class="text-muted">
                                        Published {{ $pub->published_at->format('M d, Y') }}
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                            <p class="mt-3">No publications available at this time.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($publications instanceof \Illuminate\Pagination\Paginator && $publications->total())
                <div class="d-flex justify-content-center mt-5">
                    {{ $publications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .hover-shadow {
        transition: box-shadow 0.3s ease;
    }
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    a.text-decoration-none {
        color: inherit;
    }
</style>
@endsection

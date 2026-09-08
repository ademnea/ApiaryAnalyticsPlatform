@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Search Events</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.events.search') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="q" class="form-control" placeholder="Search events by title or venue..." value="{{ old('q', $query) }}" required>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            </form>
        </div>
    </div>

    @if ($query)
        <div class="card">
            <div class="card-body">
                @if ($events->count() > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Venue</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td>
                                        <strong>{{ $event->title }}</strong>
                                    </td>
                                    <td>{{ $event->venue }}</td>
                                    <td>{{ $event->event_date->format('M d, Y') }}</td>
                                    <td>
                                        @if ($event->is_published)
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td>{{ $event->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($events->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $events->links() }}
                        </div>
                    @endif
                @else
                    <p class="text-center text-muted py-4">No events found matching "{{ $query }}"</p>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Enter a search term to find events.
        </div>
    @endif
</div>
@endsection

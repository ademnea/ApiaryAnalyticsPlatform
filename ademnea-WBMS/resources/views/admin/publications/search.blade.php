@extends('layouts.app')

@section('title', 'Search Publications')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Search Publications</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.publications.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.publications.search') }}" class="d-flex gap-2">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Search by author, title, publisher, or year..."
                    value="{{ $query }}"
                    autofocus
                >
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="{{ route('admin.publications.search') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            </form>
        </div>
    </div>

    @if($query)
        <div class="alert alert-info">
            Showing results for: <strong>{{ $query }}</strong>
        </div>
    @endif

    <!-- Results Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Author</th>
                        <th>Title</th>
                        <th>Publisher</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($publications as $pub)
                        <tr>
                            <td>{{ $pub->author }}</td>
                            <td>
                                <strong>{{ $pub->title }}</strong>
                                @if($pub->deleted_at)
                                    <span class="badge bg-danger">Deleted</span>
                                @endif
                            </td>
                            <td>{{ $pub->publisher }}</td>
                            <td>{{ $pub->publication_year }}</td>
                            <td>
                                @if($pub->is_published)
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td>{{ $pub->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.publications.show', $pub->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.publications.edit', $pub->id) }}"
                                   class="btn btn-sm btn-warning"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                @if($query)
                                    No publications found matching "{{ $query }}"
                                @else
                                    Enter a search query to begin
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($publications instanceof \Illuminate\Pagination\Paginator && $publications->total())
        <div class="d-flex justify-content-center mt-4">
            {{ $publications->links() }}
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Publications')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">Publications</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.publications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Publication
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.publications.search') }}" class="d-flex gap-2">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Search by author, title, publisher, or year..."
                    value="{{ request('q') }}"
                >
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> Search
                </button>
            </form>
        </div>
    </div>

    <!-- Publications Table -->
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

                                @if(!$pub->is_published)
                                    <form action="{{ route('admin.publications.publish', $pub->id) }}"
                                          method="POST"
                                          style="display: inline;">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-success"
                                                title="Publish"
                                                onclick="return confirm('Publish this publication?')">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.publications.unpublish', $pub->id) }}"
                                          method="POST"
                                          style="display: inline;">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-warning"
                                                title="Unpublish"
                                                onclick="return confirm('Unpublish this publication?')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.publications.destroy', $pub->id) }}"
                                      method="POST"
                                      style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            title="Delete"
                                            onclick="return confirm('Delete this publication permanently?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No publications found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($publications instanceof \Illuminate\Pagination\Paginator)
        <div class="d-flex justify-content-center mt-4">
            {{ $publications->links() }}
        </div>
    @endif
</div>
@endsection

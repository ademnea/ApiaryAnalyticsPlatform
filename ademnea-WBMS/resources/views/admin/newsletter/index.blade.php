@extends('layouts.app')

@section('title', 'Newsletters')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Newsletters</h1>
        <a href="{{ route('admin.newsletter.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Newsletter</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card mb-4"><div class="card-body">
        <form method="GET" action="{{ route('admin.newsletter.index') }}" class="row g-2">
            <div class="col-md-10"><input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search title or description..."></div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button></div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Title</th><th>Status</th><th>Views</th><th>Published</th><th>Created By</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($newsletters as $newsletter)
            <tr>
                <td><strong>{{ $newsletter->title }}</strong>@if($newsletter->deleted_at) <span class="badge bg-danger">Deleted</span>@endif<br><small class="text-muted">{{ $newsletter->excerpt }}</small></td>
                <td>@if($newsletter->is_published)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Draft</span>@endif</td>
                <td>{{ $newsletter->view_count }}</td>
                <td>{{ $newsletter->published_at?->format('M d, Y') ?? 'Not published' }}</td>
                <td>{{ $newsletter->creator->name ?? 'Unknown' }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.newsletter.show', $newsletter) }}" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                    @if(!$newsletter->deleted_at)
                        <a href="{{ route('admin.newsletter.edit', $newsletter) }}" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route($newsletter->is_published ? 'admin.newsletter.unpublish' : 'admin.newsletter.publish', $newsletter) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm {{ $newsletter->is_published ? 'btn-warning' : 'btn-success' }}" title="{{ $newsletter->is_published ? 'Unpublish' : 'Publish' }}"><i class="bi bi-{{ $newsletter->is_published ? 'x' : 'check' }}-circle"></i></button></form>
                        <form action="{{ route('admin.newsletter.destroy', $newsletter) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this newsletter?')"><i class="bi bi-trash"></i></button></form>
                    @else
                        <form action="{{ route('admin.newsletter.restore', $newsletter->id) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button></form>
                    @endif
                </td>
            </tr>
        @empty <tr><td colspan="6" class="text-center py-4 text-muted">No newsletters found.</td></tr> @endforelse
        </tbody>
    </table></div></div>
    <div class="d-flex justify-content-center mt-4">{{ $newsletters->links() }}</div>
</div>
@endsection

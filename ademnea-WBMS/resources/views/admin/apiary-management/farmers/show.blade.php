@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">{{ $farmer->full_name }}</h1>
        <div>
            @if ($farmer->trashed())
                <form method="POST" action="{{ route('admin.farmers.restore', $farmer) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">Restore</button>
                </form>
            @else
                <a href="{{ route('admin.farmers.edit', $farmer) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endif
        </div>
    </div>

    @if ($farmer->trashed())
        <div class="alert alert-warning">This farmer record has been removed (soft-deleted).</div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <dl class="row">
        <dt class="col-sm-3">Email</dt>
        <dd class="col-sm-9">{{ $farmer->email ?? '—' }}</dd>
        <dt class="col-sm-3">Phone</dt>
        <dd class="col-sm-9">{{ $farmer->phone ?? '—' }}</dd>
        <dt class="col-sm-3">Secondary Phone</dt>
        <dd class="col-sm-9">{{ $farmer->phone_secondary ?? '—' }}</dd>
        <dt class="col-sm-3">Country / Region</dt>
        <dd class="col-sm-9">{{ $farmer->country }} / {{ $farmer->region ?? '—' }}</dd>
        <dt class="col-sm-3">Village</dt>
        <dd class="col-sm-9">{{ $farmer->village ?? '—' }}</dd>
        <dt class="col-sm-3">National ID</dt>
        <dd class="col-sm-9">{{ $farmer->national_id ?? '—' }}</dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9"><span class="badge bg-{{ $farmer->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($farmer->status) }}</span></dd>
    </dl>

    <h2 class="h5 mt-4">Apiaries</h2>
    <table class="table table-sm table-striped">
        <thead><tr><th>Name</th><th>Country</th><th>Hives</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($farmer->apiaries as $apiary)
                <tr>
                    <td><a href="{{ route('admin.apiaries.show', $apiary) }}">{{ $apiary->name }}</a></td>
                    <td>{{ $apiary->country }}</td>
                    <td>{{ $apiary->hives_count ?? $apiary->hives->count() }}</td>
                    <td>{{ ucfirst($apiary->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No apiaries linked yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

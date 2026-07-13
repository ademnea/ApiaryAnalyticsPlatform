@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Apiaries</h1>
        <a href="{{ route('admin.apiaries.create') }}" class="btn btn-primary">Register New Apiary</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Name</th><th>Country</th><th>Managing Farmer</th><th>Hives</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($apiaries as $apiary)
                <tr>
                    <td>{{ $apiary->name }}</td>
                    <td>{{ $apiary->country }}</td>
                    <td>
                        @if($apiary->farmer)
                            {{ $apiary->farmer->full_name }}
                        @else
                            <span class="text-muted">Organization-managed</span>
                        @endif
                    </td>
                    <td>{{ $apiary->hives_count ?? $apiary->hives->count() }} / {{ $apiary->hive_capacity }}</td>
                    <td><span class="badge bg-{{ $apiary->status === 'Active' ? 'success' : 'secondary' }}">{{ ucfirst($apiary->status) }}</span></td>
                    <td>
                        <a href="{{ route('admin.apiaries.show', $apiary) }}" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="{{ route('admin.apiaries.edit', $apiary) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No apiaries registered yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $apiaries->links() }}
</div>
@endsection

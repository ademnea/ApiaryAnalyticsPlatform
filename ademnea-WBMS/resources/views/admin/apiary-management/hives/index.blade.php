@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Hives</h1>
    <a href="{{ route('admin.hives.create') }}" class="btn btn-primary">Register Hive</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('admin.hives.index') }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="apiary_id" class="form-select">
            <option value="">All apiaries</option>
            @foreach($apiaries as $apiary)
                <option value="{{ $apiary->id }}" @selected(request('apiary_id') == $apiary->id)>{{ $apiary->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
    </div>
</form>

<table class="table table-striped align-middle">
    <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Apiary</th>
            <th>Type</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($hives as $hive)
            <tr>
                <td><a href="{{ route('admin.hives.show', $hive) }}"><code>{{ $hive->hybrid_identifier }}</code></a></td>
                <td>{{ $hive->display_name }}</td>
                <td>
                    <a href="{{ route('admin.apiaries.show', $hive->apiary) }}">{{ $hive->apiary->name }}</a>
                </td>
                <td>{{ $hive->hive_type }}</td>
                <td>
                    <span class="badge bg-{{ $hive->current_status === 'Active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($hive->current_status) }}
                    </span>
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.hives.edit', $hive) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No hives match these filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $hives->links() }}
@endsection

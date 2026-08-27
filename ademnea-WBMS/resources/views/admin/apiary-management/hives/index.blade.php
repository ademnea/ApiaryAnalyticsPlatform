@extends('layouts.app')

@section('title', 'Hives')
@section('page-title', 'Hives')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Hives</li>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Apiary</label>
                <select name="apiary_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All apiaries</option>
                    @foreach($apiaries as $apiary)
                        <option value="{{ $apiary->id }}" @selected(request('apiary_id') == $apiary->id)>{{ $apiary->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-forest w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <div class="col-md-2 text-end">
                <a href="{{ route('admin.hives.index') }}" class="btn btn-sm btn-outline-forest"><i class="bi bi-x-circle me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $hives->total() }} hive(s) found.</p>
    <a href="{{ route('admin.hives.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Register Hive</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Code</th><th>Name</th><th>Apiary</th><th>Type</th><th>Status</th><th class="text-end pe-3">Actions</th></tr>
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
    </div>
</div>

{{ $hives->links() }}
@endsection

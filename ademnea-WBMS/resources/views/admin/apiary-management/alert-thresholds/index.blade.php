@extends('layouts.app')

@section('title', 'Alert Thresholds')
@section('page-title', 'Alert Thresholds')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Alert Thresholds</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $thresholds->total() }} threshold(s) found.</p>
    <a href="{{ route('admin.alert-thresholds.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Threshold</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Key</th><th>Value</th><th>Description</th><th>Hive</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($thresholds as $threshold)
                    <tr>
                        <td><code>{{ $threshold->key }}</code></td>
                        <td>{{ $threshold->value }}</td>
                        <td>{{ $threshold->description ?? '—' }}</td>
                        <td>{!! $threshold->hive?->display_name ?? '<span class="text-muted">Global</span>' !!}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.alert-thresholds.edit', $threshold) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.alert-thresholds.destroy', $threshold) }}" class="d-inline" onsubmit="return confirm('Delete this threshold?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No alert thresholds found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $thresholds->links() }}
@endsection

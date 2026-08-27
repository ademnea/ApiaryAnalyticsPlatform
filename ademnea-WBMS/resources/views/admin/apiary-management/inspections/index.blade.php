@extends('layouts.app')

@section('title', 'Inspections')
@section('page-title', 'Inspection Records')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Inspections</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $inspections->total() }} record(s) found.</p>
    <a href="{{ route('admin.inspections.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Inspection</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Hive</th><th>Date</th><th>Strength</th><th>Disease</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($inspections as $inspection)
                    <tr>
                        <td><a href="{{ route('admin.inspections.show', $inspection) }}">{{ $inspection->hive->display_name ?? '—' }}</a></td>
                        <td>{{ $inspection->inspected_at?->format('d M Y') }}</td>
                        <td>{{ $inspection->strength_rating ?? '—' }}</td>
                        <td>{{ $inspection->disease_events ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.inspections.edit', $inspection) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No inspection records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $inspections->links() }}
@endsection

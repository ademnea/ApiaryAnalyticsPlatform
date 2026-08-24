@extends('layouts.app')

@section('title', 'Harvests')
@section('page-title', 'Harvest Records')
@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Harvests</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $harvests->total() }} record(s) found.</p>
    <a href="{{ route('admin.harvests.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Harvest</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Hive</th><th>Date</th><th>Honey (kg)</th><th>Beeswax (kg)</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($harvests as $harvest)
                    <tr>
                        <td><a href="{{ route('admin.harvests.show', $harvest) }}">{{ $harvest->hive->display_name ?? '—' }}</a></td>
                        <td>{{ $harvest->harvest_date?->format('d M Y') }}</td>
                        <td>{{ $harvest->honey_yield_kg ?? '—' }}</td>
                        <td>{{ $harvest->beeswax_yield_kg ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.harvests.edit', $harvest) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No harvest records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $harvests->links() }}
@endsection

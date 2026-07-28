@extends('layouts.app')

@section('title', $hive->display_name)
@section('page-title', $hive->display_name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.index') }}">Hives</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $hive->hybrid_identifier }}</li>
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>Hive Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:0.85rem;">
                    <dt class="col-sm-4 text-muted">Code</dt>
                    <dd class="col-sm-8"><code>{{ $hive->hybrid_identifier }}</code></dd>

                    <dt class="col-sm-4 text-muted">Apiary</dt>
                    <dd class="col-sm-8"><a href="{{ route('admin.apiaries.show', $hive->apiary) }}">{{ $hive->apiary->name }}</a></dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $hive->current_status === 'Active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($hive->current_status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-4 text-muted">Type</dt>
                    <dd class="col-sm-8">{{ $hive->hive_type }}</dd>

                    <dt class="col-sm-4 text-muted">Queen</dt>
                    <dd class="col-sm-8">{{ ucfirst($hive->queen_status) }}</dd>

                    <dt class="col-sm-4 text-muted">Origin</dt>
                    <dd class="col-sm-8">{{ ucfirst($hive->colony_origin) ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Installed</dt>
                    <dd class="col-sm-8">{{ $hive->installation_date?->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Coordinates</dt>
                    <dd class="col-sm-8">{{ $hive->latitude }}, {{ $hive->longitude }}</dd>

                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $hive->notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-arrow-repeat me-1"></i>Change Status</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.hives.updateStatus', $hive) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-2">
                        <div class="col-md-5">
                            <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                                <option value="">— Select new status —</option>
                                @foreach (['Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'] as $option)
                                    @continue($option === $hive->current_status)
                                    <option value="{{ $option }}" @selected(old('status') === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <textarea name="change_notes" rows="1" class="form-control form-control-sm @error('change_notes') is-invalid @enderror"
                                      placeholder="Reason (optional)">{{ old('change_notes') }}</textarea>
                            @error('change_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-1"></i>Status History</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr><th>When</th><th>From</th><th>To</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($hive->statusHistory as $entry)
                            <tr>
                                <td>{{ $entry->transitioned_at->format('d M Y H:i') }}</td>
                                <td>{{ $entry->previous_status ?? '—' }}</td>
                                <td>{{ $entry->new_status }}</td>
                                <td>{{ $entry->reason_note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No status changes recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

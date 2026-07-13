@extends('admin.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        {{ $hive->display_name }}
        <small class="text-muted"><code>{{ $hive->hybrid_identifier }}</code></small>
    </h1>
    <div>
        <a href="{{ route('admin.apiaries.show', $hive->apiary) }}" class="btn btn-outline-secondary btn-sm">Back to Apiary</a>
        <a href="{{ route('admin.hives.edit', $hive) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <dl class="row">
            <dt class="col-5">Apiary</dt>
            <dd class="col-7"><a href="{{ route('admin.apiaries.show', $hive->apiary) }}">{{ $hive->apiary->name }}</a></dd>

            <dt class="col-5">Status</dt>
            <dd class="col-7">
                <span class="badge bg-{{ $hive->current_status === 'Active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($hive->current_status) }}
                </span>
            </dd>

            <dt class="col-5">Type</dt>
            <dd class="col-7">{{ $hive->hive_type }}</dd>

            <dt class="col-5">Queen status</dt>
            <dd class="col-7">{{ ucfirst($hive->queen_status) }}</dd>

            <dt class="col-5">Colony origin</dt>
            <dd class="col-7">{{ ucfirst($hive->colony_origin) ?? '—' }}</dd>

            <dt class="col-5">Installed</dt>
            <dd class="col-7">{{ $hive->installation_date?->format('d M Y') ?? '—' }}</dd>

            <dt class="col-5">Coordinates</dt>
            <dd class="col-7">{{ $hive->latitude }}, {{ $hive->longitude }}</dd>

            <dt class="col-5">Notes</dt>
            <dd class="col-7">{{ $hive->notes ?? '—' }}</dd>
        </dl>

        {{-- Change status --}}
        <div class="card">
            <div class="card-body">
                <h2 class="h6">Change Status</h2>
                <form method="POST" action="{{ route('admin.hives.updateStatus', $hive) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required>
                            <option value="">— Select new status —</option>
                            @foreach (['Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'] as $option)
                                @continue($option === $hive->current_status)
                                <option value="{{ $option }}" @selected(old('status') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-2">
                        <textarea name="change_notes" rows="2" class="form-control form-control-sm @error('change_notes') is-invalid @enderror"
                                  placeholder="Reason (optional)">{{ old('change_notes') }}</textarea>
                        @error('change_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <h2 class="h6">Status History</h2>
        <table class="table table-sm table-striped mb-4">
            <thead>
                <tr>
                    <th>When</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Notes</th>
                </tr>
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
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">No status changes recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 text-muted small">
                    <strong class="d-block text-body">Device Assignments</strong>
                    Coming once IoT Data Receiver (Developer C) lands.
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 text-muted small">
                    <strong class="d-block text-body">Inspections</strong>
                    Coming in Phase 2 of the implementation plan.
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 text-muted small">
                    <strong class="d-block text-body">Harvest Records</strong>
                    Coming in Phase 3 of the implementation plan.
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 text-muted small">
                    <strong class="d-block text-body">Alert Thresholds</strong>
                    Coming in Phase 4 of the implementation plan.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

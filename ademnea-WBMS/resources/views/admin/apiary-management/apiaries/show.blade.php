@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">{{ $apiary->name }}</h1>
        <div>
            <a href="{{ route('admin.apiaries.edit', $apiary) }}" class="btn btn-outline-secondary">Edit</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <dl class="row">
        <dt class="col-sm-3">Country</dt><dd class="col-sm-9">{{ $apiary->country }}</dd>
        <dt class="col-sm-3">Region</dt><dd class="col-sm-9">{{ $apiary->region ?? '—' }}</dd>
        <dt class="col-sm-3">District</dt><dd class="col-sm-9">{{ $apiary->district ?? '—' }}</dd>
        <dt class="col-sm-3">Managing Farmer</dt>
        <dd class="col-sm-9">
            @if($apiary->farmer)
                <a href="{{ route('admin.farmers.show', $apiary->farmer) }}">{{ $apiary->farmer->full_name }}</a>
            @else
                Organization-managed
            @endif
        </dd>
        <dt class="col-sm-3">Contact Person</dt>
        <dd class="col-sm-9">{{ $apiary->contact_person_name ?? '—' }} ({{ $apiary->contact_person_phone ?? '—' }})</dd>
        <dt class="col-sm-3">Coordinates</dt>
        <dd class="col-sm-9">{{ $apiary->latitude ?? '—' }}, {{ $apiary->longitude ?? '—' }}</dd>
        <dt class="col-sm-3">Hive Capacity</dt><dd class="col-sm-9">{{ $apiary->hives_count ?? $apiary->hives->count() }} / {{ $apiary->hive_capacity }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($apiary->status) }}</dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $apiary->description ?? '—' }}</dd>
    </dl>

    <h2 class="h5 mt-4">Hives</h2>
    <table class="table table-sm">
        <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Last Inspection</th></tr></thead>
        <tbody>
            @foreach($apiary->hives as $hive)
                <tr>
                    <td><a href="{{ route('admin.hives.show', $hive) }}">{{ $hive->hybrid_identifier }}</a></td>
                    <td>{{ $hive->display_name }}</td>
                    <td>{{ ucfirst($hive->current_status) }}</td>
                    <td>{{ $hive->last_inspection_date?->format('d M Y') ?? 'Never' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

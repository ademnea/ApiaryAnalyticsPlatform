@extends('layouts.app')

@section('title', 'Pending Approvals')
@section('page-title', 'Pending Farmer Approvals')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.farmers.index') }}">Farmers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pending Approvals</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:0.82rem;">{{ $farmers->total() }} pending farmer(s).</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Country</th><th>Registered</th><th class="text-end pe-3">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($farmers as $farmer)
                    <tr>
                        <td>{{ $farmer->full_name }}</td>
                        <td>{{ $farmer->email }}</td>
                        <td>{{ $farmer->phone ?? '—' }}</td>
                        <td>{{ $farmer->country_name }}</td>
                        <td>{{ $farmer->registration_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.farmers.approve', $farmer) }}" class="d-inline" onsubmit="return confirm('Approve this farmer?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-circle me-1"></i>Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.farmers.reject', $farmer) }}" class="d-inline" onsubmit="return confirm('Reject this farmer?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Reject</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No pending farmers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $farmers->links() }}
@endsection

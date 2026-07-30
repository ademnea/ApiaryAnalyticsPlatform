@extends('layouts.app')

@section('title', 'Work Package Management')

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Work Package Management</h2>
            <p class="text-muted mb-0">
                Manage AdEMNEA project work packages displayed on the public website.
            </p>
        </div>

        <a href="{{ route('admin.work-packages.create') }}"
           class="btn btn-success rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i>
            Add Work Package
        </a>
    </div>


    {{-- Statistics --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">

                    <div class="text-muted small">
                        Total Work Packages
                    </div>

                    <h2 class="fw-bold mt-2">
                        {{ $stats['total'] }}
                    </h2>

                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">

                    <div class="text-muted small">
                        Published
                    </div>

                    <h2 class="fw-bold text-success mt-2">
                        {{ $stats['published'] }}
                    </h2>

                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">

                    <div class="text-muted small">
                        Draft
                    </div>

                    <h2 class="fw-bold text-warning mt-2">
                        {{ $stats['draft'] }}
                    </h2>

                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">

                    <div class="text-muted small">
                        Archived
                    </div>

                    <h2 class="fw-bold text-danger mt-2">
                        {{ $stats['archived'] }}
                    </h2>

                </div>
            </div>
        </div>

    </div>


    {{-- Search Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-lg-5">

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Search work package..."
                        >

                    </div>


                    <div class="col-lg-3">

                        <select
                            name="status"
                            class="form-select">

                            <option value="">All Status</option>

                            <option value="Published"
                                @selected(request('status')=='Published')>
                                Published
                            </option>

                            <option value="Draft"
                                @selected(request('status')=='Draft')>
                                Draft
                            </option>

                            <option value="Archived"
                                @selected(request('status')=='Archived')>
                                Archived
                            </option>

                        </select>

                    </div>


                    <div class="col-lg-2">

                        <button
                            class="btn btn-primary w-100">

                            Search

                        </button>

                    </div>


                    <div class="col-lg-2">

                        <a
                            href="{{ route('admin.work-packages.index') }}"
                            class="btn btn-light border w-100">

                            Reset

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- Table --}}
    <div class="card border-0 shadow rounded-4">

        <div class="card-header bg-white border-0 py-3">

            <h5 class="fw-bold mb-0">
                Work Package Records
            </h5>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>

                    <th>#</th>

                    <th>Work Package</th>

                    <th>Lead</th>

                    <th>Status</th>

                    <th>Display Order</th>

                    <th width="180">
                        Actions
                    </th>

                </tr>

                </thead>

                <tbody>

                @forelse($workPackages as $package)

                    <tr>

                        <td>

                            <strong>{{ $package->wp_number }}</strong>

                        </td>

                        <td>

                            <div class="fw-semibold">
                                {{ $package->title }}
                            </div>

                            <small class="text-muted">
                                {{ Str::limit($package->summary,70) }}
                            </small>

                        </td>

                        <td>

                            {{ $package->lead }}

                        </td>

                        <td>

                            @if($package->status=='Published')

                                <span class="badge bg-success">
                                    Published
                                </span>

                            @elseif($package->status=='Draft')

                                <span class="badge bg-warning text-dark">
                                    Draft
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Archived
                                </span>

                            @endif

                        </td>

                        <td>

                            {{ $package->display_order }}

                        </td>

                        <td>

                            <div class="d-flex gap-2">

                                <a
                                    href="{{ route('admin.work-packages.show',$package) }}"
                                    class="btn btn-sm btn-outline-primary">

                                    <i class="bi bi-eye"></i>

                                </a>

                                <a
                                    href="{{ route('admin.work-packages.edit',$package) }}"
                                    class="btn btn-sm btn-outline-success">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <form
                                    action="{{ route('admin.work-packages.destroy',$package) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this Work Package?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="btn btn-sm btn-outline-danger">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <div class="text-center py-5">

                                <i class="bi bi-folder2-open fs-1 text-muted"></i>

                                <h5 class="mt-3">
                                    No Work Packages Found
                                </h5>

                                <p class="text-muted">
                                    Create your first Work Package.
                                </p>

                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        @if($workPackages->hasPages())

            <div class="card-footer bg-white">

                {{ $workPackages->links() }}

            </div>

        @endif

    </div>

</div>
@endsection
@extends('layouts.app')

@section('title','Team Profile Management')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Team Profile Management
            </h2>

            <p class="text-muted mb-0">
                Manage all AdEMNEA team members displayed on the public website.
            </p>
        </div>

        <a href="{{ route('admin.team-profiles.create') }}"
           class="btn btn-success rounded-pill px-4">

            <i class="bi bi-person-plus-fill me-2"></i>

            Add Team Member

        </a>

    </div>


    {{-- Statistics --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        Total Members
                    </small>

                    <h2 class="fw-bold mt-2">
                        {{ $stats['total'] }}
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        Published
                    </small>

                    <h2 class="fw-bold text-success mt-2">
                        {{ $stats['published'] }}
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        Draft
                    </small>

                    <h2 class="fw-bold text-warning mt-2">
                        {{ $stats['draft'] }}
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-lg-3">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <small class="text-muted">
                        Archived
                    </small>

                    <h2 class="fw-bold text-danger mt-2">
                        {{ $stats['archived'] }}
                    </h2>

                </div>

            </div>

        </div>

    </div>



    {{-- Search --}}
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
                            placeholder="Search team member...">

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

                        <button class="btn btn-primary w-100">

                            Search

                        </button>

                    </div>

                    <div class="col-lg-2">

                        <a href="{{ route('admin.team-profiles.index') }}"
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

        <div class="card-header bg-white border-0">

            <h5 class="fw-bold mb-0">

                Team Members

            </h5>

        </div>

        <div class="table-responsive">

            <table class="table align-middle table-hover mb-0">

                <thead class="table-light">

                <tr>

                    <th width="80">
                        Photo
                    </th>

                    <th>Name</th>

                    <th>Role</th>

                    <th>Institution</th>

                    <th>Status</th>

                    <th>Order</th>

                    <th width="180">

                        Actions

                    </th>

                </tr>

                </thead>

                <tbody>

                @forelse($teamProfiles as $member)

                    <tr>

                        <td>

                            @if($member->profile_photo)

                                <img
                                    src="{{ $member->profile_photo_url }}"
                                    class="rounded-circle"
                                    width="55"
                                    height="55"
                                    style="object-fit:cover;">

                            @else

                                <div
                                    class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                    style="width:55px;height:55px;">

                                    <i class="bi bi-person fs-4"></i>

                                </div>

                            @endif

                        </td>

                        <td>

                            <div class="fw-semibold">

                                {{ $member->full_name }}

                            </div>

                            <small class="text-muted">

                                {{ $member->email }}

                            </small>

                        </td>

                        <td>

                            {{ $member->role }}

                        </td>

                        <td>

                            {{ $member->institution }}

                        </td>

                        <td>

                            <span class="badge bg-{{ $member->status_color }}">

                                {{ $member->status }}

                            </span>

                        </td>

                        <td>

                            {{ $member->display_order }}

                        </td>

                        <td>

                            <div class="d-flex gap-2">

                                <a
                                    href="{{ route('admin.team-profiles.show',$member) }}"
                                    class="btn btn-outline-primary btn-sm">

                                    <i class="bi bi-eye"></i>

                                </a>

                                <a
                                    href="{{ route('admin.team-profiles.edit',$member) }}"
                                    class="btn btn-outline-success btn-sm">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <form
                                    action="{{ route('admin.team-profiles.destroy',$member) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this team member?')">

                                    @csrf

                                    @method('DELETE')

                                    <button
                                        class="btn btn-outline-danger btn-sm">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7">

                            <div class="text-center py-5">

                                <i class="bi bi-people fs-1 text-muted"></i>

                                <h5 class="mt-3">

                                    No Team Members Found

                                </h5>

                                <p class="text-muted">

                                    Add your first team member.

                                </p>

                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        @if($teamProfiles->hasPages())

            <div class="card-footer bg-white">

                {{ $teamProfiles->links() }}

            </div>

        @endif

    </div>

</div>

@endsection
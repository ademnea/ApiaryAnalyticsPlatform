@extends('layouts.app')

@section('title', $teamProfile->full_name)

@section('content')

<div class="container-fluid py-4">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.team-profiles.index') }}">
                    Team Profile Management
                </a>
            </li>

            <li class="breadcrumb-item active">
                {{ $teamProfile->full_name }}
            </li>
        </ol>
    </nav>


    <div class="row">

        {{-- Left Card --}}
        <div class="col-lg-4 mb-4">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body text-center p-4">

                    @if($teamProfile->profile_photo)

                        <img
                            src="{{ $teamProfile->profile_photo_url }}"
                            class="rounded-circle shadow mb-3"
                            style="width:180px;height:180px;object-fit:cover;">

                    @else

                        <div
                            class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:180px;height:180px;">

                            <i class="bi bi-person-fill fs-1 text-secondary"></i>

                        </div>

                    @endif

                    <h3 class="fw-bold mb-1">

                        {{ $teamProfile->full_name }}

                    </h3>

                    <p class="text-success fw-semibold mb-1">

                        {{ $teamProfile->role }}

                    </p>

                    @if($teamProfile->institution)

                        <p class="text-muted">

                            {{ $teamProfile->institution }}

                        </p>

                    @endif


                    @if($teamProfile->status=='Published')

                        <span class="badge bg-success px-3 py-2">
                            Published
                        </span>

                    @elseif($teamProfile->status=='Draft')

                        <span class="badge bg-warning text-dark px-3 py-2">
                            Draft
                        </span>

                    @else

                        <span class="badge bg-danger px-3 py-2">
                            Archived
                        </span>

                    @endif

                    <hr>

                    <div class="text-start">

                        @if($teamProfile->email)

                            <p class="mb-2">

                                <i class="bi bi-envelope me-2 text-success"></i>

                                {{ $teamProfile->email }}

                            </p>

                        @endif

                        @if($teamProfile->phone)

                            <p class="mb-2">

                                <i class="bi bi-telephone me-2 text-success"></i>

                                {{ $teamProfile->phone }}

                            </p>

                        @endif

                        <p class="mb-0">

                            <i class="bi bi-sort-numeric-down me-2 text-success"></i>

                            Display Order:
                            <strong>{{ $teamProfile->display_order }}</strong>

                        </p>

                    </div>

                </div>

            </div>

        </div>



        {{-- Right Card --}}
        <div class="col-lg-8">

            {{-- Biography --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">

                <div class="card-header bg-white border-0">

                    <h5 class="fw-bold mb-0">

                        Biography

                    </h5>

                </div>

                <div class="card-body">

                    @if($teamProfile->biography)

                        {!! nl2br(e($teamProfile->biography)) !!}

                    @else

                        <span class="text-muted">

                            No biography available.

                        </span>

                    @endif

                </div>

            </div>



            {{-- Research Interests --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">

                <div class="card-header bg-white border-0">

                    <h5 class="fw-bold mb-0">

                        Research Interests

                    </h5>

                </div>

                <div class="card-body">

                    @if($teamProfile->research_interests)

                        {!! nl2br(e($teamProfile->research_interests)) !!}

                    @else

                        <span class="text-muted">

                            No research interests provided.

                        </span>

                    @endif

                </div>

            </div>



            {{-- Actions --}}
            <div class="d-flex justify-content-between">

                <a
                    href="{{ route('admin.team-profiles.index') }}"
                    class="btn btn-light border">

                    <i class="bi bi-arrow-left me-2"></i>

                    Back

                </a>


                <div>

                    <a
                        href="{{ route('admin.team-profiles.edit',$teamProfile) }}"
                        class="btn btn-success me-2">

                        <i class="bi bi-pencil-square me-2"></i>

                        Edit

                    </a>


                    <form
                        action="{{ route('admin.team-profiles.destroy',$teamProfile) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Delete this team member?')">

                        @csrf
                        @method('DELETE')

                        <button
                            class="btn btn-danger">

                            <i class="bi bi-trash me-2"></i>

                            Delete

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
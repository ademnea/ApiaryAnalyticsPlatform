@extends('layouts.app')

@section('title', $teamProfile->full_name)

@section('content')
<div class="container-fluid mt-4">

    <div class="row g-4">
        {{-- Left Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center p-4">
                    @if($teamProfile->profile_photo)
                        <img src="{{ $teamProfile->profile_photo_url }}" class="rounded-circle shadow mb-3" alt="{{ $teamProfile->full_name }}" style="width:180px;height:180px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width:180px;height:180px;">
                            <i class="bi bi-person-fill fs-1 text-secondary"></i>
                        </div>
                    @endif

                    <h3 class="fw-bold mb-1">{{ $teamProfile->full_name }}</h3>
                    <p class="text-success fw-semibold mb-1">{{ $teamProfile->role }}</p>

                    @if($teamProfile->institution)
                        <p class="text-muted">{{ $teamProfile->institution }}</p>
                    @endif

                    <hr>

                    <div class="text-start">
                        @if($teamProfile->email)
                            <p class="mb-2">
                                <i class="bi bi-envelope me-2 text-success"></i>
                                <a href="mailto:{{ $teamProfile->email }}">{{ $teamProfile->email }}</a>
                            </p>
                        @endif

                        @if($teamProfile->phone)
                            <p class="mb-2">
                                <i class="bi bi-telephone me-2 text-success"></i>
                                {{ $teamProfile->phone }}
                            </p>
                        @endif
                    </div>

                    <a href="{{ route('public.team.index') }}" class="btn btn-outline-secondary mt-3">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Team
                    </a>
                </div>
            </div>
        </div>

        {{-- Right Card --}}
        <div class="col-lg-8">
            @if($teamProfile->biography)
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="fw-bold mb-0">Biography</h5>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($teamProfile->biography)) !!}
                    </div>
                </div>
            @endif

            @if($teamProfile->research_interests)
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="fw-bold mb-0">Research Interests</h5>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($teamProfile->research_interests)) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
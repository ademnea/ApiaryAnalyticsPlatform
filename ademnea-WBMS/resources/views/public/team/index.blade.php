@extends('layouts.app')

@section('title', 'Our Team')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Our Team</h1>
            <p class="text-muted mb-0">Meet the AdEMNEA project team members.</p>
        </div>
    </div>

    <div class="row g-4">
        @forelse($teamProfiles as $member)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm rounded-4 text-center" style="border:1px solid rgba(27,48,34,0.08);">
                    <div class="card-body p-4">
                        @if($member->profile_photo)
                            <img src="{{ $member->profile_photo_url }}" class="rounded-circle shadow mb-3" alt="{{ $member->full_name }}" style="width:160px;height:160px;object-fit:cover;" loading="lazy">
                        @else
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width:160px;height:160px;">
                                <i class="bi bi-person-fill fs-1 text-secondary"></i>
                            </div>
                        @endif
                        <h5 class="card-title mb-1">{{ $member->full_name }}</h5>
                        <p class="text-success fw-semibold mb-1">{{ $member->role }}</p>
                        @if($member->institution)
                            <p class="text-muted mb-2">{{ $member->institution }}</p>
                        @endif
                        <a href="{{ route('public.team.show', $member) }}" class="btn btn-sm btn-outline-primary">View Profile</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-people fs-1 text-muted"></i>
                    <h5 class="mt-3">No Team Members Available</h5>
                    <p class="text-muted">Published team profiles will appear here.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
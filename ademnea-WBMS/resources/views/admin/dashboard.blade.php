@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Overview</li>
@endsection

@push('styles')
<style>
    /* Ensure tab content area doesn't collapse */
    #activityTabContent .tab-pane { min-height: 120px; }

    /* Tighten up the nav tabs inside the activity card */
    #activityTabs .nav-link {
        padding: 0.45rem 0.75rem;
        color: var(--clr-muted);
        border-radius: 6px 6px 0 0;
    }
    #activityTabs .nav-link.active {
        color: var(--clr-forest);
        border-bottom-color: #fff;
        font-weight: 500;
    }
    #activityTabs .nav-link:hover:not(.active) {
        color: var(--clr-forest-mid);
        background: rgba(45,106,79,0.06);
    }

    /* Page-level timestamp badge */
    .dashboard-timestamp {
        font-size: 0.72rem;
        color: var(--clr-muted);
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
</style>
@endpush

@section('content')

{{-- ── Page header row ──────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <p class="mb-0" style="font-size:0.82rem;color:var(--clr-muted);">
            Welcome back, <strong>{{ auth()->user()->name }}</strong>.
            Here's a real-time overview of the beehive monitoring system.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="dashboard-timestamp">
            <i class="bi bi-clock"></i>
            Last refreshed: {{ now()->format('D, d M Y · H:i') }}
        </span>
        <button class="btn btn-sm btn-outline-forest"
                onclick="window.location.reload()"
                title="Refresh dashboard">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

{{-- ================================================================== --}}
{{-- SECTION 1 — Summary Cards                                          --}}
{{-- ================================================================== --}}
@include('admin.dashboard.partials.summary-cards', ['summary' => $summary])

{{-- ================================================================== --}}
{{-- SECTION 2 — Hive Monitoring Summary                                --}}
{{-- ================================================================== --}}
@include('admin.dashboard.partials.monitoring-summary', ['monitoring' => $monitoring])

{{-- ================================================================== --}}
{{-- SECTION 3 — Charts                                                 --}}
{{-- ================================================================== --}}
@include('admin.dashboard.partials.charts', ['chartData' => $chartData])

{{-- ================================================================== --}}
{{-- SECTION 4 & 5 — Alerts + Recent Activity (side by side on desktop) --}}
{{-- ================================================================== --}}
<div class="row g-3 mb-4">

    {{-- Alerts (col-lg-5) --}}
    <div class="col-12 col-lg-5">
        @include('admin.dashboard.partials.alerts-section', ['alerts' => $alerts])
    </div>

    {{-- Recent Activity (col-lg-7) --}}
    <div class="col-12 col-lg-7">
        @include('admin.dashboard.partials.recent-activity', ['activity' => $activity])
    </div>

</div>

{{-- ================================================================== --}}
{{-- SECTION 6 — Quick Navigation                                       --}}
{{-- ================================================================== --}}
@include('admin.dashboard.partials.quick-nav')

@endsection

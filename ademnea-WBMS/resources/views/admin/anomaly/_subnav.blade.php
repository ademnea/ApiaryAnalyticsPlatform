@push('styles')
    <style>
        .module-subnav { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem; }
        .module-subnav a {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.35rem 0.85rem; border-radius: 999px;
            font-size: 0.78rem; font-weight: 600; text-decoration: none;
            color: var(--clr-forest-mid); background: var(--clr-canvas); border: 1px solid var(--clr-border);
            transition: background-color 0.12s ease, color 0.12s ease;
        }
        .module-subnav a:hover { background: var(--clr-forest-pale); color: var(--clr-forest); }
        .module-subnav a.active { background: var(--clr-forest); color: #fff; border-color: var(--clr-forest); }
    </style>
@endpush

<div class="module-subnav">
    <a href="{{ route('admin.anomaly.dashboard') }}" class="{{ request()->routeIs('admin.anomaly.dashboard') ? 'active' : '' }}">
        <i class="bi bi-shield-exclamation"></i> Dashboard
    </a>
    <a href="{{ route('admin.anomaly.anomalies.index') }}" class="{{ request()->routeIs('admin.anomaly.anomalies.*') ? 'active' : '' }}">
        <i class="bi bi-list-ul"></i> All Anomalies
    </a>
    <a href="{{ route('admin.anomaly.analytics') }}" class="{{ request()->routeIs('admin.anomaly.analytics') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line"></i> Analytics
    </a>
</div>

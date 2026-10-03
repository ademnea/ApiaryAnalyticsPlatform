@extends('layouts.app')

{{--
    Frame shared by every Sensor Monitoring page: styles, stream tabs, filter
    bar and auto-refresh bar. The page's own `monitoring` section is wrapped in
    the "monitoring-live" fragment, which is all an auto-refresh fetches and
    swaps, so the filters, tabs and scroll position are left untouched.

    From MonitoringController: $stream (a SensorMetric, a MediaKind, or null on
    the overview and insights), $pageRoute and $pageLabel (null on the
    overview), $filters, $apiaries, $hiveGroups, $refreshSeconds.
    Pages must not reuse $stream, $pageRoute or $pageLabel as variable names.
--}}

@php
    use App\Enums\MediaKind;
    use App\Enums\SensorMetric;

    $query = $filters->queryString();
    $pageUrl = route($pageRoute);
    $isSensorPage = $stream instanceof SensorMetric;

    // [route, icon, label] per tab; null marks a divider.
    $tabs = [
        ['admin.monitoring.index', 'bi-grid-1x2', 'Overview'],
        null,
        ...array_map(fn (SensorMetric $m) => [$m->routeName(), $m->icon(), $m->label()], SensorMetric::cases()),
        ['admin.monitoring.insights', 'bi-activity', 'Hive Insights'],
        null,
        ...array_map(fn (MediaKind $k) => [$k->routeName(), $k->icon(), $k->label()], MediaKind::cases()),
    ];

    $selectedHive = $filters->hiveId !== null ? $hiveGroups->flatten(1)->firstWhere('id', $filters->hiveId) : null;
    $selectedApiary = $filters->apiaryId !== null ? $apiaries->firstWhere('id', $filters->apiaryId) : null;
    $scope = match (true) {
        $selectedHive !== null => 'Hive: '.($selectedHive->display_name ?: $selectedHive->hive_code),
        $selectedApiary !== null => 'Apiary: '.$selectedApiary->name,
        default => 'All apiaries and hives',
    };
@endphp

@section('title', $pageLabel ?? 'Sensor Monitoring')
@section('page-title', $pageLabel ?? 'Sensor Monitoring')

@section('breadcrumbs')
    @if($pageLabel)
        <li class="breadcrumb-item"><a href="{{ route('admin.monitoring.index', $query) }}">Sensor Monitoring</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $pageLabel }}</li>
    @else
        <li class="breadcrumb-item active" aria-current="page">Sensor Monitoring</li>
    @endif
@endsection

@push('styles')
    <style>
        .monitoring-filter-label { font-size: 0.75rem; font-weight: 500; color: var(--clr-muted); }
        .monitoring-hint { font-size: 0.75rem; }
        .monitoring-date { max-width: 9.75rem; }

        .monitoring-tabs { display: flex; gap: 0.35rem; overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: thin; }
        .monitoring-tab {
            display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;
            padding: 0.35rem 0.8rem; border-radius: 999px; border: 1px solid var(--clr-border);
            background: var(--clr-card); color: #1a2e1f; font-size: 0.78rem; text-decoration: none;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .monitoring-tab:hover { border-color: var(--clr-forest-light); color: var(--clr-forest); }
        .monitoring-tab.active { background: var(--clr-forest-mid); border-color: var(--clr-forest-mid); color: #fff; }
        .monitoring-tab:focus-visible, .metric-card a:focus-visible { outline: 2px solid var(--clr-honey); outline-offset: 2px; }
        .monitoring-tab-divider { width: 1px; background: var(--clr-border); margin: 0.2rem 0.25rem; flex-shrink: 0; }

        .stat-sub { font-size: 0.7rem; color: var(--clr-muted); margin-top: 0.2rem; }
        a.stat-sub { color: var(--clr-forest-mid); text-decoration: none; }
        a.stat-sub:hover { text-decoration: underline; }

        .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 0.35rem; vertical-align: middle; }
        .status-dot.live { background: var(--clr-forest-light); }
        .status-dot.stale { background: var(--clr-honey); }
        .status-dot.backlog { background: #0057b8; }
        .badge-backlog { background: #D0E4FF; color: #0057b8; }

        .sparkline-wrap { position: relative; height: 44px; margin-top: 0.6rem; }
        .chart-wrap--short { height: 220px; }
        @media (max-width: 576px) { .chart-wrap--short { height: 180px; } }
        .band-swatch { display: inline-block; width: 14px; height: 10px; border-radius: 2px; background: rgba(212, 160, 23, 0.18); border: 1px dashed rgba(212, 160, 23, 0.7); vertical-align: middle; margin-right: 0.3rem; }
        .insight-question { font-size: 0.8rem; color: var(--clr-muted); }

        .tabular { font-variant-numeric: tabular-nums; }
        .table th .unit-suffix { text-transform: none; font-weight: 400; letter-spacing: 0; }
        .table th.row-label { text-transform: none; letter-spacing: 0; background: transparent; font-size: 0.82rem; }
        .zone-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 0.45rem; vertical-align: middle; }
        .chart-wrap { position: relative; height: 320px; }
        @media (max-width: 576px) { .chart-wrap { height: 240px; } }

        .metric-card .metric-icon {
            width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center;
            justify-content: center; background: var(--clr-forest-pale); color: var(--clr-forest);
        }
        .metric-card .metric-title { font-family: var(--font-display); font-weight: 600; font-size: 0.9rem; }
        .metric-card .metric-value { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .metric-card .metric-unit { font-size: 0.85rem; font-weight: 500; color: var(--clr-muted); }
        .metric-facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin: 0.9rem 0 0; }
        .metric-facts dt { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--clr-muted); }
        .metric-facts dd { margin: 0; font-size: 0.82rem; font-weight: 500; }

        .media-frame { display: block; aspect-ratio: 4 / 3; background: #EEF3EF; border-radius: 10px 10px 0 0; overflow: hidden; }
        .media-frame img, .media-frame video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .media-frame--audio { display: flex; align-items: center; justify-content: center; font-size: 2.4rem; color: var(--clr-forest-light); aspect-ratio: 16 / 7; }
        .media-meta { display: grid; grid-template-columns: auto 1fr; column-gap: 0.6rem; row-gap: 0.1rem; margin: 0.5rem 0 0; font-size: 0.75rem; }
        .media-meta dt { color: var(--clr-muted); font-weight: 400; }
        .media-meta dd { margin: 0; text-align: right; }

        .monitoring-empty-icon { font-size: 1.8rem; color: var(--clr-forest-light); }

        .monitoring-live-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .live-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--clr-forest-light); animation: live-pulse 2s ease-out infinite; }
        .live-dot.is-loading { background: var(--clr-honey); animation: none; }
        .live-dot.is-paused { background: #ADB5BD; animation: none; }
        .live-dot.is-error { background: #B30000; animation: none; }
        @keyframes live-pulse {
            0% { box-shadow: 0 0 0 0 rgba(64, 145, 108, 0.55); }
            70% { box-shadow: 0 0 0 6px rgba(64, 145, 108, 0); }
            100% { box-shadow: 0 0 0 0 rgba(64, 145, 108, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .stat-card, .monitoring-tab { transition: none; }
            .live-dot { animation: none; }
        }
    </style>
@endpush

@section('content')
    {{-- Stream tabs carry the filters across, so comparing streams is one click. --}}
    <nav class="monitoring-tabs mb-3" aria-label="Monitoring streams">
        @foreach($tabs as $tab)
            @if($tab === null)
                <span class="monitoring-tab-divider" aria-hidden="true"></span>
                @continue
            @endif
            @php
                [$tabRoute, $tabIcon, $tabLabel] = $tab;
            @endphp
            <a href="{{ route($tabRoute, $query) }}"
               class="monitoring-tab {{ $tabRoute === $pageRoute ? 'active' : '' }}"
               @if($tabRoute === $pageRoute) aria-current="page" @endif>
                <i class="bi {{ $tabIcon }}" aria-hidden="true"></i>{{ $tabLabel }}
            </a>
        @endforeach
    </nav>

    {{-- Filter bar. GET, so every view is a shareable URL. --}}
    <form method="GET" action="{{ $pageUrl }}" class="card mb-3" role="search" aria-label="Filter readings">
        <input type="hidden" name="range" value="{{ $filters->range }}">
        {{-- Table preferences survive a filter change; `upto` deliberately does not. --}}
        @foreach(\Illuminate\Support\Arr::only($query, ['per_page', 'sort']) as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="filter-apiary" class="form-label mb-1 monitoring-filter-label">Apiary</label>
                    <select id="filter-apiary" name="apiary_id" class="form-select form-select-sm"
                            onchange="this.form.elements['hive_id'].value = ''">
                        <option value="">All apiaries</option>
                        @foreach($apiaries as $apiary)
                            <option value="{{ $apiary->id }}" @selected($filters->apiaryId === $apiary->id)>{{ $apiary->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="filter-hive" class="form-label mb-1 monitoring-filter-label">Hive</label>
                    <select id="filter-hive" name="hive_id" class="form-select form-select-sm">
                        <option value="">All hives</option>
                        @foreach($hiveGroups as $apiaryName => $groupHives)
                            <optgroup label="{{ $apiaryName }}">
                                @foreach($groupHives as $hive)
                                    <option value="{{ $hive->id }}" @selected($filters->hiveId === $hive->id)>
                                        {{ $hive->display_name ?: $hive->hive_code }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text monitoring-hint">Choosing a hive overrides the apiary filter.</div>
                </div>

                <div class="col-12 col-lg-6">
                    <span class="form-label mb-1 monitoring-filter-label d-block" id="range-label">Time window</span>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="btn-group btn-group-sm" role="group" aria-labelledby="range-label">
                            @foreach(array_keys(\App\Services\Monitoring\MonitoringFilters::PRESETS) as $preset)
                                <a href="{{ route($pageRoute, ['range' => $preset] + \Illuminate\Support\Arr::except($query, ['range', 'from', 'to'])) }}"
                                   class="btn {{ $filters->range === $preset ? 'btn-primary' : 'btn-outline-forest' }}"
                                   @if($filters->range === $preset) aria-current="true" @endif>{{ strtoupper($preset) }}</a>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label for="filter-from" class="visually-hidden">Start date</label>
                            <input type="date" id="filter-from" name="from" class="form-control form-control-sm monitoring-date"
                                   value="{{ $filters->isCustom() ? $filters->from->toDateString() : '' }}" max="{{ now()->toDateString() }}">
                            <span class="text-muted monitoring-hint">to</span>
                            <label for="filter-to" class="visually-hidden">End date</label>
                            <input type="date" id="filter-to" name="to" class="form-control form-control-sm monitoring-date"
                                   value="{{ $filters->isCustom() ? $filters->to->toDateString() : '' }}" max="{{ now()->toDateString() }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-center mt-1">
                <div class="col-12 col-lg-6">
                    @if($isSensorPage)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" value="1" id="filter-flagged" name="flagged" @checked($filters->flaggedOnly)>
                            <label class="form-check-label monitoring-hint" for="filter-flagged">Flagged readings only</label>
                        </div>
                    @endif
                </div>
                <div class="col-12 col-lg-6 d-flex flex-wrap justify-content-lg-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply filters
                    </button>
                    @if($filters->hasAnyScope())
                        <a href="{{ $pageUrl }}" class="btn btn-sm btn-outline-forest">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear
                        </a>
                    @endif
                    @if($isSensorPage)
                        <a href="{{ route('admin.monitoring.export', ['metric' => $stream->value] + $query) }}" class="btn btn-sm btn-outline-forest">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>Export CSV
                        </a>
                    @endif
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-warning mt-3 mb-0 py-2 monitoring-hint" role="alert">
                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ $errors->first() }}
                </div>
            @endif
        </div>
    </form>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0 monitoring-hint">
            <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ $filters->windowLabel() }}
            <span class="mx-1" aria-hidden="true">&middot;</span>
            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $scope }}
            @if($filters->flaggedOnly)
                <span class="mx-1" aria-hidden="true">&middot;</span>
                <i class="bi bi-flag me-1" aria-hidden="true"></i>Flagged readings only
            @endif
        </p>

        @if($refreshSeconds > 0)
            <div class="monitoring-live-bar" data-monitoring-refresh
                 data-refresh-url="{{ request()->fullUrl() }}" data-refresh-seconds="{{ $refreshSeconds }}">
                <span class="live-dot" data-refresh-dot aria-hidden="true"></span>
                <span class="monitoring-hint text-muted tabular" data-refresh-text>Reload the page for new readings</span>
                <a href="{{ request()->fullUrl() }}" class="monitoring-hint" data-refresh-reload hidden>Reload page</a>
                <button type="button" class="btn btn-sm btn-outline-forest" data-refresh-now title="Fetch the latest readings now">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="ms-1">Refresh</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-forest" data-refresh-toggle aria-pressed="false">
                    <i class="bi bi-pause-fill" aria-hidden="true" data-refresh-toggle-icon></i><span class="ms-1" data-refresh-toggle-label>Pause</span>
                </button>
                {{-- Announces each completed refresh once, not every countdown tick. --}}
                <span class="visually-hidden" role="status" aria-live="polite" data-refresh-announcer></span>
            </div>
        @endif
    </div>

    @fragment('monitoring-live')
        <div id="monitoring-live" data-monitoring-live>
            @yield('monitoring')
        </div>
    @endfragment
@endsection

@if($refreshSeconds > 0)
    @push('scripts')
        <script>
            /*
             * Auto-refresh. On each interval, fetch the current URL with the
             * X-Monitoring-Refresh header; the controller returns only the
             * #monitoring-live fragment and htmx (already loaded by the admin
             * layout) swaps it in. Anything that must redraw afterwards listens
             * for "monitoring:refreshed". A cycle is skipped while the tab is
             * hidden, while paused, or while audio or video is playing.
             */
            (function () {
                const bar = document.querySelector('[data-monitoring-refresh]');

                if (!bar || typeof htmx === 'undefined') {
                    return;
                }

                const TARGET_ID = 'monitoring-live';
                const PAUSE_KEY = 'ademnea.monitoring.autoRefreshPaused';
                const MEDIA_RETRY_MS = 30 * 1000;
                const STUCK_REQUEST_MS = 60 * 1000;

                const url = bar.dataset.refreshUrl;
                const intervalMs = Math.max(30, parseInt(bar.dataset.refreshSeconds, 10) || 300) * 1000;
                const el = (name) => bar.querySelector(`[data-refresh-${name}]`);
                const [dot, text, reloadLink, nowButton, toggleButton, toggleIcon, toggleLabel, announcer] =
                    ['dot', 'text', 'reload', 'now', 'toggle', 'toggle-icon', 'toggle-label', 'announcer'].map(el);

                // Storage can throw in private browsing; fall back to memory.
                const pauseStore = {
                    read: () => { try { return localStorage.getItem(PAUSE_KEY) === '1'; } catch (e) { return false; } },
                    write: (value) => { try { localStorage.setItem(PAUSE_KEY, value ? '1' : '0'); } catch (e) { /* memory only */ } },
                };

                let paused = pauseStore.read();
                let nextAt = Date.now() + intervalMs;
                let lastUpdated = new Date();
                let requestStartedAt = null;
                let failed = false;

                const clock = (date) => date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const countdown = () => {
                    const seconds = Math.max(0, Math.ceil((nextAt - Date.now()) / 1000));
                    return Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
                };
                const mediaIsPlaying = () => Array.from(document.querySelectorAll(`#${TARGET_ID} audio, #${TARGET_ID} video`))
                    .some((media) => !media.paused && !media.ended);

                function render() {
                    const loading = requestStartedAt !== null;

                    dot.className = 'live-dot' + (loading ? ' is-loading' : failed ? ' is-error' : paused ? ' is-paused' : '');
                    text.textContent = loading ? 'Updating readings…'
                        : failed ? (paused ? 'Could not update readings.' : `Could not update readings. Retrying in ${countdown()}.`)
                        : paused ? `Updated ${clock(lastUpdated)} · auto-refresh paused`
                        : `Updated ${clock(lastUpdated)} · next update in ${countdown()}`;

                    reloadLink.hidden = !failed || loading;
                    toggleButton.setAttribute('aria-pressed', paused ? 'true' : 'false');
                    toggleIcon.className = 'bi ' + (paused ? 'bi-play-fill' : 'bi-pause-fill');
                    toggleLabel.textContent = paused ? 'Resume' : 'Pause';
                }

                function refresh(reason) {
                    const inFlight = requestStartedAt !== null && Date.now() - requestStartedAt < STUCK_REQUEST_MS;

                    if (inFlight || !document.getElementById(TARGET_ID)) {
                        return;
                    }

                    if (reason === 'timer' && mediaIsPlaying()) {
                        nextAt = Date.now() + MEDIA_RETRY_MS;
                        return;
                    }

                    failed = false;
                    requestStartedAt = Date.now();
                    render();

                    htmx.ajax('GET', url, { target: '#' + TARGET_ID, swap: 'outerHTML', headers: { 'X-Monitoring-Refresh': '1' } })
                        .catch(() => { failed = true; })
                        .finally(() => {
                            requestStartedAt = null;
                            nextAt = Date.now() + intervalMs;

                            if (!failed) {
                                lastUpdated = new Date();
                                announcer.textContent = `Readings updated at ${clock(lastUpdated)}`;
                                document.dispatchEvent(new CustomEvent('monitoring:refreshed'));
                            }

                            render();
                        });
                }

                // Swap only a genuine fragment. An expired session redirects to
                // the login page, which still arrives as a 200.
                document.body.addEventListener('htmx:beforeSwap', (event) => {
                    if (event.detail.target?.id !== TARGET_ID) {
                        return;
                    }

                    if (event.detail.xhr.status !== 200 || !event.detail.serverResponse.includes('data-monitoring-live')) {
                        event.detail.shouldSwap = false;
                        failed = true;
                    }
                });

                nowButton.addEventListener('click', () => refresh('manual'));

                toggleButton.addEventListener('click', () => {
                    paused = !paused;
                    pauseStore.write(paused);

                    if (!paused) {
                        refresh('resume'); // do not leave old readings up for another full interval
                    }

                    render();
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && !paused && Date.now() >= nextAt) {
                        refresh('visible');
                    }
                });

                setInterval(() => {
                    if (!paused && document.visibilityState === 'visible' && Date.now() >= nextAt) {
                        refresh('timer');
                    }

                    render();
                }, 1000);

                render();
            })();
        </script>
    @endpush
@endif

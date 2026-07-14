{{--
    Partial: dashboard/partials/charts.blade.php
    Receives: $chartData (array from DashboardService::getChartData())
    SRS: REQ-DASH-03 – Chart.js charts for temperature, humidity, CO₂,
         hive weight, and hive activity over time.
    Chart.js is already loaded in layouts/app.blade.php.
--}}

<div class="row g-3 mb-4">

    {{-- ── Hive Activity (real data, uses HiveStatusHistory) ─────── --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart-line me-2 text-success"></i>Hive Activity (Last 7 Days)</span>
                <span style="font-size:0.72rem;color:var(--clr-muted);">Status change events</span>
            </div>
            <div class="card-body" style="position:relative;height:220px;">
                <canvas id="chartHiveActivity" aria-label="Hive activity over the last 7 days" role="img"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Temperature Trend ───────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-thermometer-half me-2" style="color:#dc3545;"></i>Temperature Trend</span>
                <a href="{{ route('admin.monitoring.temperature') }}"
                   style="font-size:0.72rem;color:var(--clr-forest-mid);">View detail →</a>
            </div>
            <div class="card-body" style="position:relative;height:220px;">
                @if(collect($chartData['temperature'])->filter()->isNotEmpty())
                    <canvas id="chartTemperature" aria-label="Temperature trend chart" role="img"></canvas>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-thermometer display-5 opacity-25"></i>
                        <p class="mt-2 mb-0" style="font-size:0.82rem;">
                            {{-- TODO: chart will populate once HiveTemperature model is integrated --}}
                            Temperature data not yet available
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Humidity Trend ──────────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-droplet-half me-2" style="color:#0d6efd;"></i>Humidity Trend</span>
                <a href="{{ route('admin.monitoring.humidity') }}"
                   style="font-size:0.72rem;color:var(--clr-forest-mid);">View detail →</a>
            </div>
            <div class="card-body" style="position:relative;height:220px;">
                @if(collect($chartData['humidity'])->filter()->isNotEmpty())
                    <canvas id="chartHumidity" aria-label="Humidity trend chart" role="img"></canvas>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-droplet display-5 opacity-25"></i>
                        <p class="mt-2 mb-0" style="font-size:0.82rem;">
                            {{-- TODO: chart will populate once HiveHumidity model is integrated --}}
                            Humidity data not yet available
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── CO₂ Trend ───────────────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-wind me-2" style="color:#D4A017;"></i>CO₂ Trend</span>
                <a href="{{ route('admin.monitoring.co2') }}"
                   style="font-size:0.72rem;color:var(--clr-forest-mid);">View detail →</a>
            </div>
            <div class="card-body" style="position:relative;height:220px;">
                @if(collect($chartData['co2'])->filter()->isNotEmpty())
                    <canvas id="chartCo2" aria-label="CO2 trend chart" role="img"></canvas>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-wind display-5 opacity-25"></i>
                        <p class="mt-2 mb-0" style="font-size:0.82rem;">
                            {{-- TODO: chart will populate once HiveCo2 model is integrated --}}
                            CO₂ data not yet available
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Hive Weight Trend ───────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-speedometer me-2 text-success"></i>Hive Weight Trend</span>
                <a href="{{ route('admin.monitoring.weight') }}"
                   style="font-size:0.72rem;color:var(--clr-forest-mid);">View detail →</a>
            </div>
            <div class="card-body" style="position:relative;height:220px;">
                @if(collect($chartData['weight'])->filter()->isNotEmpty())
                    <canvas id="chartWeight" aria-label="Hive weight trend chart" role="img"></canvas>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-speedometer display-5 opacity-25"></i>
                        <p class="mt-2 mb-0" style="font-size:0.82rem;">
                            {{-- TODO: chart will populate once HiveWeight model is integrated --}}
                            Weight data not yet available
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Shared chart data passed from Laravel ──────────────────────────────
    const labels      = @json($chartData['labels']);
    const activity    = @json($chartData['hive_activity']);
    const temperature = @json($chartData['temperature']);
    const humidity    = @json($chartData['humidity']);
    const co2         = @json($chartData['co2']);
    const weight      = @json($chartData['weight']);

    // ── Shared defaults ────────────────────────────────────────────────────
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0D1B12',
                titleColor: '#D4A017',
                bodyColor: '#d0e8d9',
                padding: 10,
                cornerRadius: 6,
            },
        },
        scales: {
            x: {
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { font: { size: 11 }, color: '#6B7F74' },
            },
            y: {
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: { font: { size: 11 }, color: '#6B7F74' },
                beginAtZero: true,
            },
        },
    };

    function buildLine(id, data, label, borderColor, bgColor) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    borderColor,
                    backgroundColor: bgColor,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: defaultOptions,
        });
    }

    function buildBar(id, data, label, borderColor, bgColor) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: bgColor,
                    borderColor,
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: defaultOptions,
        });
    }

    // ── Hive Activity (bar — always rendered, real data) ──────────────────
    buildBar(
        'chartHiveActivity',
        activity,
        'Status Changes',
        '#2D6A4F',
        'rgba(45,106,79,0.6)'
    );

    // ── Temperature (line — only rendered when data has values) ───────────
    buildLine(
        'chartTemperature',
        temperature,
        'Avg Temp (°C)',
        '#dc3545',
        'rgba(220,53,69,0.08)'
    );

    // ── Humidity (line) ───────────────────────────────────────────────────
    buildLine(
        'chartHumidity',
        humidity,
        'Avg Humidity (%)',
        '#0d6efd',
        'rgba(13,110,253,0.08)'
    );

    // ── CO₂ (line) ────────────────────────────────────────────────────────
    buildLine(
        'chartCo2',
        co2,
        'Avg CO₂ (ppm)',
        '#D4A017',
        'rgba(212,160,23,0.08)'
    );

    // ── Hive Weight (line) ────────────────────────────────────────────────
    buildLine(
        'chartWeight',
        weight,
        'Avg Weight (kg)',
        '#1B4332',
        'rgba(27,67,50,0.08)'
    );

})();
</script>
@endpush

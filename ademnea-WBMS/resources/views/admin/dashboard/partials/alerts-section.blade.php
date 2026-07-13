{{--
    Partial: dashboard/partials/alerts-section.blade.php
    Receives: $alerts (array from DashboardService::getAlerts())
    SRS: REQ-DASH-04 – Critical hive alerts, offline devices, high temp,
         low humidity, recent abnormal readings. Bootstrap alert components.
--}}

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bell-fill me-2" style="color:#D4A017;"></i>Active Alerts</span>
        <a href="{{ route('admin.alerts.index') }}" class="btn btn-sm btn-outline-forest">
            All Alerts <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body">

        @php
            $hasAnyAlert =
                $alerts['critical_hives']->isNotEmpty()          ||
                $alerts['hives_needing_inspection']->isNotEmpty() ||
                $alerts['pending_farmers']->isNotEmpty()          ||
                $alerts['offline_devices']->isNotEmpty()          ||
                $alerts['high_temperature_alerts']->isNotEmpty()  ||
                $alerts['low_humidity_alerts']->isNotEmpty();
        @endphp

        @if(! $hasAnyAlert)
            <div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div>No active alerts — all systems nominal.</div>
            </div>
        @else

            {{-- ── Critical Hive Status ─────────────────────────────── --}}
            @if($alerts['critical_hives']->isNotEmpty())
                <div class="alert alert-bee-error alert-ademnea d-flex align-items-start gap-2 mb-3" role="alert"
                     style="border-left-color:#dc3545;background:#fff5f5;">
                    <i class="bi bi-exclamation-octagon-fill flex-shrink-0 mt-1" style="color:#dc3545;"></i>
                    <div class="w-100">
                        <strong>Critical Hive Alerts</strong>
                        <ul class="mb-0 mt-1 ps-3" style="font-size:0.82rem;">
                            @foreach($alerts['critical_hives'] as $hive)
                                <li>
                                    <a href="{{ route('admin.hives.index') }}"
                                       class="text-decoration-none" style="color:#7f1d1d;">
                                        {{ $hive->display_name }}
                                    </a>
                                    @if($hive->apiary)
                                        <span class="text-muted">({{ $hive->apiary->name }})</span>
                                    @endif
                                    — <span class="badge badge-offline" style="text-transform:capitalize;">
                                            {{ str_replace('_', ' ', $hive->status) }}
                                      </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- ── Hives Needing Inspection ────────────────────────── --}}
            @if($alerts['hives_needing_inspection']->isNotEmpty())
                <div class="alert alert-ademnea d-flex align-items-start gap-2 mb-3" role="alert">
                    <i class="bi bi-clipboard2-x-fill flex-shrink-0 mt-1" style="color:#856404;"></i>
                    <div class="w-100">
                        <strong>Hives Overdue for Inspection</strong>
                        <span class="text-muted ms-1" style="font-size:0.78rem;">(last inspection &gt;30 days ago)</span>
                        <ul class="mb-0 mt-1 ps-3" style="font-size:0.82rem;">
                            @foreach($alerts['hives_needing_inspection'] as $hive)
                                <li>
                                    <a href="{{ route('admin.hives.index') }}"
                                       class="text-decoration-none" style="color:#664d03;">
                                        {{ $hive->display_name }}
                                    </a>
                                    @if($hive->apiary)
                                        <span class="text-muted">({{ $hive->apiary->name }})</span>
                                    @endif
                                    —
                                    @if($hive->last_inspection_date)
                                        last inspected
                                        <strong>{{ $hive->last_inspection_date->diffForHumans() }}</strong>
                                    @else
                                        <strong>never inspected</strong>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- ── Pending Farmer Approvals ─────────────────────────── --}}
            @if($alerts['pending_farmers']->isNotEmpty())
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert"
                     style="font-size:0.82rem;">
                    <i class="bi bi-person-fill-exclamation flex-shrink-0 mt-1"></i>
                    <div class="w-100">
                        <strong>Farmer Registrations Pending Approval</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($alerts['pending_farmers'] as $farmer)
                                <li>
                                    <a href="{{ route('admin.farmers.pending') }}"
                                       class="text-decoration-none text-dark">
                                        {{ $farmer->full_name }}
                                    </a>
                                    <span class="text-muted">&mdash; registered {{ $farmer->created_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('admin.farmers.pending') }}"
                           class="btn btn-sm btn-warning mt-2" style="font-size:0.75rem;">
                            Review Pending Farmers
                        </a>
                    </div>
                </div>
            @endif

            {{-- ── Offline Devices ─────────────────────────────────── --}}
            @if($alerts['offline_devices']->isNotEmpty())
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert"
                     style="font-size:0.82rem;">
                    <i class="bi bi-wifi-off flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Offline IoT Devices</strong>
                        {{-- TODO: render IotDevice records once model exists --}}
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($alerts['offline_devices'] as $device)
                                <li>{{ $device->name ?? 'Device #'.$device->id }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- ── High Temperature Alerts ─────────────────────────── --}}
            @if($alerts['high_temperature_alerts']->isNotEmpty())
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert"
                     style="font-size:0.82rem;">
                    <i class="bi bi-thermometer-high flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>High Temperature Alerts</strong>
                        {{-- TODO: render HiveTemperature records once model exists --}}
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($alerts['high_temperature_alerts'] as $reading)
                                <li>Hive #{{ $reading->hive_id ?? '—' }} &mdash; {{ $reading->value ?? '—' }}&deg;C</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- ── Low Humidity Alerts ──────────────────────────────── --}}
            @if($alerts['low_humidity_alerts']->isNotEmpty())
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert"
                     style="font-size:0.82rem;">
                    <i class="bi bi-droplet-slash flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Low Humidity Alerts</strong>
                        {{-- TODO: render HiveHumidity records once model exists --}}
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($alerts['low_humidity_alerts'] as $reading)
                                <li>Hive #{{ $reading->hive_id ?? '—' }} &mdash; {{ $reading->value ?? '—' }}%</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

        @endif

    </div>
</div>

{{--
    Partial: dashboard/partials/summary-cards.blade.php
    Receives: $summary (array from DashboardService::getSummaryCounts())
    SRS: REQ-DASH-01 – Display summary cards for Farmers, Farms, Hives,
         Team Members, and IoT Devices.
--}}

<div class="row g-3 mb-4">

    {{-- Total Farmers --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.farmers.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon green">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($summary['total_farmers']) }}</div>
                    <div class="stat-label">Total Farmers</div>
                    @if($summary['pending_farmers'] > 0)
                        <span class="badge badge-pending mt-1" style="font-size:0.65rem;">
                            {{ $summary['pending_farmers'] }} pending
                        </span>
                    @endif
                </div>
            </div>
        </a>
    </div>

    {{-- Total Farms (Apiaries) --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.apiaries.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon green">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($summary['total_apiaries']) }}</div>
                    <div class="stat-label">Total Farms</div>
                    <div style="font-size:0.7rem;color:var(--clr-muted);margin-top:2px;">
                        {{ $summary['active_apiaries'] }} active
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Total Hives --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.hives.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon honey">
                    <i class="bi bi-hexagon-fill"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($summary['total_hives']) }}</div>
                    <div class="stat-label">Total Hives</div>
                    <div style="font-size:0.7rem;color:var(--clr-muted);margin-top:2px;">
                        {{ $summary['active_hives'] }} active
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Team Members --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon blue">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($summary['total_team_members']) }}</div>
                    <div class="stat-label">Team Members</div>
                    <div style="font-size:0.7rem;color:var(--clr-muted);margin-top:2px;">
                        admins &amp; officers
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- IoT Devices --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.devices.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon blue">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <div>
                    @if($summary['total_iot_devices'] !== null)
                        <div class="stat-value">{{ number_format($summary['total_iot_devices']) }}</div>
                        <div class="stat-label">IoT Devices</div>
                    @else
                        <div class="stat-value text-muted" style="font-size:1rem;">—</div>
                        <div class="stat-label">IoT Devices</div>
                        {{-- TODO: remove placeholder once IotDevice model is available --}}
                        <span style="font-size:0.65rem;color:var(--clr-muted);">module pending</span>
                    @endif
                </div>
            </div>
        </a>
    </div>

    {{-- Total Users --}}
    <div class="col-6 col-sm-4 col-xl-2">
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
            <div class="stat-card h-100">
                <div class="stat-icon green">
                    <i class="bi bi-person-fill-check"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($summary['total_users']) }}</div>
                    <div class="stat-label">Registered Users</div>
                    <div style="font-size:0.7rem;color:var(--clr-muted);margin-top:2px;">all roles</div>
                </div>
            </div>
        </a>
    </div>

</div>

{{--
    Partial: dashboard/partials/quick-nav.blade.php
    SRS: REQ-DASH-06 – Quick navigation shortcuts to key modules.
    No variables needed — routes are resolved directly.
--}}

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-grid-3x3-gap me-2 text-success"></i>Quick Navigation
    </div>
    <div class="card-body">
        <div class="row g-2">

            @php
                $shortcuts = [
                    [
                        'label'  => 'Users',
                        'route'  => 'admin.users.index',
                        'icon'   => 'bi-shield-lock',
                        'color'  => 'blue',
                    ],
                    [
                        'label'  => 'Farmers',
                        'route'  => 'admin.farmers.index',
                        'icon'   => 'bi-people-fill',
                        'color'  => 'green',
                    ],
                    [
                        'label'  => 'Farms',
                        'route'  => 'admin.apiaries.index',
                        'icon'   => 'bi-building',
                        'color'  => 'green',
                    ],
                    [
                        'label'  => 'Hives',
                        'route'  => 'admin.hives.index',
                        'icon'   => 'bi-hexagon-fill',
                        'color'  => 'honey',
                    ],
                    [
                        'label'  => 'Monitoring',
                        'route'  => 'admin.monitoring.temperature',
                        'icon'   => 'bi-activity',
                        'color'  => 'red',
                    ],
                    [
                        'label'  => 'IoT Devices',
                        'route'  => 'admin.devices.index',
                        'icon'   => 'bi-cpu-fill',
                        'color'  => 'blue',
                    ],
                    [
                        'label'  => 'Publications',
                        'route'  => 'admin.publications.index',
                        'icon'   => 'bi-journal-richtext',
                        'color'  => 'honey',
                    ],
                    [
                        'label'  => 'Gallery',
                        'route'  => 'admin.gallery.index',
                        'icon'   => 'bi-images',
                        'color'  => 'honey',
                    ],
                    [
                        'label'  => 'Events',
                        'route'  => 'admin.events.index',
                        'icon'   => 'bi-calendar-event',
                        'color'  => 'green',
                    ],
                    [
                        'label'  => 'Feedback',
                        'route'  => 'admin.feedback.index',
                        'icon'   => 'bi-chat-square-text',
                        'color'  => 'blue',
                    ],
                    [
                        'label'  => 'Scholarships',
                        'route'  => 'admin.scholarship.index',
                        'icon'   => 'bi-mortarboard',
                        'color'  => 'green',
                    ],
                    [
                        'label'  => 'Hive Map',
                        'route'  => 'admin.hives.map',
                        'icon'   => 'bi-geo-alt-fill',
                        'color'  => 'red',
                    ],
                ];
            @endphp

            @foreach($shortcuts as $s)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <a href="{{ route($s['route']) }}"
                       class="d-flex flex-column align-items-center justify-content-center text-decoration-none
                              p-3 rounded text-center"
                       style="background:var(--clr-canvas);border:1px solid var(--clr-border);
                              transition:box-shadow 0.2s,border-color 0.2s;"
                       onmouseover="this.style.boxShadow='0 4px 14px rgba(27,67,50,0.1)';this.style.borderColor='var(--clr-forest-mid)';"
                       onmouseout="this.style.boxShadow='';this.style.borderColor='var(--clr-border)';">
                        <div class="stat-icon {{ $s['color'] }} mb-2">
                            <i class="bi {{ $s['icon'] }}"></i>
                        </div>
                        <span style="font-size:0.75rem;font-weight:500;color:#1a2e1f;">
                            {{ $s['label'] }}
                        </span>
                    </a>
                </div>
            @endforeach

        </div>
    </div>
</div>

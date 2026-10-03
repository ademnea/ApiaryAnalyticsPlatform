@extends('admin.monitoring.layout')

{{-- One gallery page for photos, audio and video; $kind decides which. --}}

@php
    use App\Enums\MediaKind;
    use Carbon\CarbonInterval;
    use Illuminate\Support\Number;

    $size = fn (?int $bytes) => $bytes ? Number::fileSize($bytes, maxPrecision: 1) : '—';
    $length = fn (?int $seconds) => $seconds ? CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true]) : '—';
    $lastCaptureAt = $summary['last_capture_at'];
@endphp

@section('monitoring')
    {{-- ---- KPI row ---- --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon green"><i class="bi {{ $kind->icon() }}" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format($summary['files']) }}</div>
                    <div class="stat-label">{{ Str::ucfirst(Str::plural($kind->singular(), $summary['files'])) }} Captured</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon blue"><i class="bi bi-hexagon" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ number_format($summary['hives_reporting']) }}</div>
                    <div class="stat-label">{{ Str::plural('Hive', $summary['hives_reporting']) }} Covered</div>
                    <div class="stat-sub">{{ number_format($summary['devices_reporting']) }} {{ Str::plural('device', $summary['devices_reporting']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon honey"><i class="bi bi-hdd" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">{{ $size($summary['total_bytes']) }}</div>
                    <div class="stat-label">Storage Used</div>
                    @if($kind->hasDuration())
                        <div class="stat-sub">{{ $length($summary['total_seconds']) }} recorded</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon {{ $lastCaptureAt ? 'green' : 'red' }}"><i class="bi bi-clock-history" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-value tabular">
                        @if($lastCaptureAt)
                            <time datetime="{{ $lastCaptureAt->toIso8601String() }}" title="{{ $lastCaptureAt->format('Y-m-d H:i:s T') }}">{{ $lastCaptureAt->diffForHumans(['short' => true, 'parts' => 1]) }}</time>
                        @else
                            —
                        @endif
                    </div>
                    <div class="stat-label">Latest Capture</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ---- Gallery ---- --}}
    <div class="card" id="gallery">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi {{ $kind->icon() }} me-1" aria-hidden="true"></i>{{ $kind->label() }} Gallery</span>
            @if($items->total() > 0)
                <span class="text-muted fw-normal monitoring-hint">Newest first</span>
            @endif
        </div>

        @if($newSinceSnapshot > 0)
            <div class="alert alert-light border-0 border-bottom rounded-0 mb-0 py-2 monitoring-hint d-flex flex-wrap align-items-center gap-2" role="status">
                <i class="bi bi-arrow-up-circle text-success" aria-hidden="true"></i>
                <span>{{ number_format($newSinceSnapshot) }} new {{ Str::plural($kind->singular(), $newSinceSnapshot) }} arrived since this gallery was opened.</span>
                <a href="{{ route($kind->routeName(), $filters->queryString()) }}#gallery" class="fw-medium">Show latest</a>
            </div>
        @endif

        @if($items->isEmpty())
            <div class="text-center text-muted py-5 px-3" role="status">
                <i class="bi {{ $kind->icon() }} d-block mb-2 monitoring-empty-icon" aria-hidden="true"></i>
                <p class="fw-medium text-body mb-1">No {{ Str::plural($kind->singular()) }} yet</p>
                <p class="mb-0 monitoring-hint">
                    {{ $filters->hasAnyScope() ? 'Nothing matches these filters. Try a wider window or clear the filters.' : 'Captures appear here once media devices upload them.' }}
                </p>
            </div>
        @else
            <div class="card-body">
                <ul class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-3 list-unstyled mb-0">
                    @foreach($items as $item)
                        @php
                            $url = $item->url;
                            $hiveName = $item->hive ? ($item->hive->display_name ?: $item->hive->hive_code) : 'Unassigned';
                            $caption = Str::ucfirst($kind->singular()).' from '.$hiveName.', captured '.$item->recorded_at->format('M j, Y H:i');
                        @endphp
                        <li class="col">
                            <article class="card h-100" aria-label="{{ $caption }}">
                                @if($kind === MediaKind::Photo)
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="media-frame">
                                        <img src="{{ $url }}" alt="{{ $caption }}" loading="lazy" decoding="async">
                                    </a>
                                @elseif($kind === MediaKind::Video)
                                    <div class="media-frame">
                                        <video controls preload="none" playsinline src="{{ $url }}" aria-label="{{ $caption }}">
                                            Your browser cannot play this video. <a href="{{ $url }}">Download it</a>.
                                        </video>
                                    </div>
                                @else
                                    <div class="media-frame media-frame--audio" aria-hidden="true"><i class="bi bi-soundwave"></i></div>
                                    <div class="px-3 pt-3">
                                        <audio controls preload="none" class="w-100" src="{{ $url }}" aria-label="{{ $caption }}">
                                            Your browser cannot play this clip. <a href="{{ $url }}">Download it</a>.
                                        </audio>
                                    </div>
                                @endif

                                <div class="card-body py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="text-truncate">
                                            @if($item->hive)
                                                <a href="{{ route('admin.hives.show', $item->hive) }}" class="fw-medium text-decoration-none">{{ $hiveName }}</a>
                                            @else
                                                <span class="fw-medium text-muted">{{ $hiveName }}</span>
                                            @endif
                                            <div class="text-muted monitoring-hint text-truncate">
                                                {{ $item->hive?->apiary?->name ?? '—' }} <span aria-hidden="true">&middot;</span> {{ $item->device?->device_code ?? '#'.$item->device_id }}
                                            </div>
                                        </div>
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-muted flex-shrink-0" title="Open original file">
                                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="visually-hidden">Open original file</span>
                                        </a>
                                    </div>
                                    <dl class="media-meta">
                                        <dt>Captured</dt>
                                        <dd><time datetime="{{ $item->recorded_at->toIso8601String() }}">{{ $item->recorded_at->format('M j, H:i') }}</time></dd>
                                        <dt>Size</dt>
                                        <dd class="tabular">{{ $size($item->file_size_bytes) }}</dd>
                                        @if($kind->hasDuration())
                                            <dt>Length</dt>
                                            <dd class="tabular">{{ $length($item->duration_seconds) }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
            @if($items->hasPages())
                <div class="card-footer bg-white">{{ $items->links() }}</div>
            @endif
        @endif
    </div>
@endsection

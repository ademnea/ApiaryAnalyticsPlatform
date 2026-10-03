{{--
    How recently a stream was heard from, as a badge or, with variant 'dot',
    a compact table cell. Params: $lastAt (newest recorded_at), $staleAfterMinutes,
    optional $receivedAt (newest created_at) and $variant.

    With $receivedAt, an old reading that arrived recently is "Catching up"
    rather than "Stale": the device is online and uploading a backlog.
--}}
@php
    use App\Services\Monitoring\MonitoringService;

    $variant ??= 'badge';
    $receivedAt ??= null;
    $isStale = MonitoringService::isStale($lastAt, $staleAfterMinutes);
    $isBacklog = MonitoringService::isBacklog($lastAt, $receivedAt, $staleAfterMinutes);
    $exact = $lastAt?->format('Y-m-d H:i:s T');
@endphp

@if($variant === 'dot')
    @if($lastAt === null)
        <span class="text-muted" aria-hidden="true">—</span><span class="visually-hidden">No data in window</span>
    @else
        <span class="status-dot {{ $isBacklog ? 'backlog' : ($isStale ? 'stale' : 'live') }}" aria-hidden="true"></span>
        <time datetime="{{ $lastAt->toIso8601String() }}" title="{{ $exact }}" class="tabular">{{ $lastAt->diffForHumans(['short' => true, 'parts' => 1]) }}</time>
        <span class="visually-hidden">{{ $isBacklog ? '(catching up)' : ($isStale ? '(stale)' : '(live)') }}</span>
    @endif
@elseif($lastAt === null)
    <span class="badge badge-offline"><i class="bi bi-wifi-off me-1" aria-hidden="true"></i>No data</span>
@elseif($isBacklog)
    <span class="badge badge-backlog" title="Newest reading was taken {{ $exact }}, but the device last delivered data {{ $receivedAt->diffForHumans() }}. It is uploading older readings.">
        <i class="bi bi-cloud-arrow-down me-1" aria-hidden="true"></i>Catching up
    </span>
@elseif($isStale)
    <span class="badge badge-warning" title="No reading for over {{ $staleAfterMinutes }} minutes. Last: {{ $exact }}">
        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Stale
    </span>
@else
    <span class="badge badge-active" title="Last reading {{ $exact }}">
        <i class="bi bi-broadcast me-1" aria-hidden="true"></i>Live
    </span>
@endif

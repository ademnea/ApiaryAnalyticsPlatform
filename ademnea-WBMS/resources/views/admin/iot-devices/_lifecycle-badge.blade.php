{{-- Lifecycle stage of a device. Expects: $status (provisioned | deployed | offline | retired); optional $withHint spells the meaning out. --}}
@php
    [$lifecycleClass, $lifecycleHint] = match ($status) {
        'provisioned' => ['badge-info', 'Registered, not yet installed on a hive'],
        'deployed' => ['badge-active', 'Installed on a hive'],
        'offline' => ['badge-offline', 'Marked as out of service'],
        'retired' => ['badge-retired', 'No longer in use'],
        default => ['badge-retired', ''],
    };
@endphp
<span class="badge {{ $lifecycleClass }}" title="{{ $lifecycleHint }}">{{ ucfirst($status) }}</span>
@if(($withHint ?? false) && $lifecycleHint)<span class="fact-hint">{{ $lifecycleHint }}</span>@endif

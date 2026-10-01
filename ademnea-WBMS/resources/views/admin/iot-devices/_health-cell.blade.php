{{-- Live connection state for a registry row. Expects: $device, $deviceHealth (IotDeviceHealthEvaluator). --}}
@if(! $device->active_flag)
    <span class="health-pill is-silent"><span class="dot"></span>Not monitored</span>
    <div class="health-meta">Access is revoked</div>
@elseif($deviceHealth['never_reported'])
    <a href="{{ route('admin.iot-devices.show', $device) }}" class="health-pill is-silent">
        <span class="dot"></span>Never reported
    </a>
    <div class="health-meta">No data received yet</div>
@else
    @php
        [$healthClass, $healthLabel] = match ($deviceHealth['status']) {
            'offline' => ['is-offline', 'Offline'],
            'warning' => ['is-warning', 'Needs attention'],
            default => ['is-online', 'Online'],
        };
    @endphp
    <a href="{{ route('admin.iot-devices.show', $device) }}" class="health-pill {{ $healthClass }}">
        <span class="dot"></span>{{ $healthLabel }}
    </a>
    <div class="health-meta" title="{{ $deviceHealth['last_contact']->format('Y-m-d H:i:s') }}">
        @if($deviceHealth['status'] === 'offline')
            Last seen {{ $deviceHealth['last_contact']->diffForHumans() }}
        @else
            @if($deviceHealth['battery_level'] !== null)
                Battery {{ (int) $deviceHealth['battery_level'] }}%{{ $deviceHealth['is_low_battery'] ? ' (low)' : '' }}
            @endif
            @if($deviceHealth['battery_level'] !== null && $deviceHealth['signal_strength'] !== null) &middot; @endif
            @if($deviceHealth['signal_strength'] !== null)
                Signal {{ $deviceHealth['is_weak_signal'] ? 'weak' : 'good' }}
            @endif
            @if($deviceHealth['battery_level'] === null && $deviceHealth['signal_strength'] === null)
                Seen {{ $deviceHealth['last_contact']->diffForHumans() }}
            @endif
        @endif
    </div>
@endif

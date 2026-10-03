{{-- Which hive a device sits on, by name. Expects: $device (with `hive` loaded). --}}
@if($device->hive)
    <a href="{{ route('admin.hives.show', $device->hive) }}" class="text-decoration-none fw-medium">
        {{ $device->hive->display_name ?? $device->hive->hive_code }}
    </a>
    @if($device->hive->display_name && $device->hive->hive_code)
        <div class="cell-sub">{{ $device->hive->hive_code }}</div>
    @endif
@elseif($device->hive_id)
    Hive #{{ $device->hive_id }}
@else
    <span class="text-muted">Not on a hive</span>
    <div class="cell-sub">
        <a href="{{ route('admin.iot-devices.assign.form', $device) }}" class="text-decoration-none">
            <i class="bi bi-geo-alt me-1"></i>Assign to a hive
        </a>
    </div>
@endif

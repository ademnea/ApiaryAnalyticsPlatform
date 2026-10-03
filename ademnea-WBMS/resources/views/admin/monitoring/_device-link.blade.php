{{-- A reading's device code, linked to its device page (health + anomalies) for users allowed to see it. Params: $reading. --}}
@if(! $reading->device)
    <span class="text-muted">#{{ $reading->device_id }}</span>
@elsecan('manage-iot-devices')
    <a href="{{ route('admin.iot-devices.show', $reading->device_id) }}" class="text-decoration-none">{{ $reading->device->device_code }}</a>
@else
    {{ $reading->device->device_code }}
@endif

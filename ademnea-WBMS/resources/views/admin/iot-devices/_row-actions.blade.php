<div class="d-inline-flex align-items-center gap-1">
    <a href="{{ route('admin.iot-devices.show', $device) }}" class="btn btn-sm btn-outline-forest">
        <i class="bi bi-eye me-1"></i>View
    </a>

    <div class="dropdown">
        <button type="button" class="btn btn-sm btn-outline-forest dropdown-toggle" data-bs-toggle="dropdown"
                data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                aria-label="More actions for {{ $device->device_code }}">
            More
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item" href="{{ route('admin.iot-devices.edit', $device) }}">
                    <i class="bi bi-pencil me-1"></i>Edit details
                </a>
            </li>
            <li>
                @if(is_null($device->hive_id))
                    <a class="dropdown-item" href="{{ route('admin.iot-devices.assign.form', $device) }}">
                        <i class="bi bi-geo-alt me-1"></i>Assign to a hive
                    </a>
                @else
                    <form action="{{ route('admin.iot-devices.unassign', $device) }}" method="POST"
                          onsubmit="return confirm('Unassign {{ $device->device_code }} from its hive?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item"><i class="bi bi-geo-alt-fill me-1"></i>Unassign from hive</button>
                    </form>
                @endif
            </li>
            <li>
                @if($device->active_flag)
                    <form action="{{ route('admin.iot-devices.revoke', $device) }}" method="POST"
                          onsubmit="return confirm('Revoke access for {{ $device->device_code }}? It will immediately stop being able to submit data.');">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-slash-circle me-1"></i>Revoke access</button>
                    </form>
                @else
                    <form action="{{ route('admin.iot-devices.reactivate', $device) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-1"></i>Reactivate access</button>
                    </form>
                @endif
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ route('admin.iot-devices.destroy', $device) }}" method="POST"
                      onsubmit="return confirm('Remove {{ $device->device_code }} from the active registry? Its historical data is kept.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-1"></i>Remove device</button>
                </form>
            </li>
        </ul>
    </div>
</div>

<div class="btn-group">
    <a href="{{ route('admin.iot-devices.show', $device) }}" class="btn btn-sm btn-outline-forest" title="View device"><i class="bi bi-eye"></i></a>
    <a href="{{ route('admin.iot-devices.edit', $device) }}" class="btn btn-sm btn-outline-forest" title="Edit device"><i class="bi bi-pencil"></i></a>

    @if(is_null($device->hive_id))
        <a href="{{ route('admin.iot-devices.assign.form', $device) }}" class="btn btn-sm btn-outline-forest" title="Assign to hive">
            <i class="bi bi-geo-alt"></i>
        </a>
    @else
        <button type="button" class="btn btn-sm btn-outline-forest" disabled title="Already assigned — unassign first to reassign">
            <i class="bi bi-geo-alt-fill"></i>
        </button>
    @endif

    @if($device->active_flag)
        <form action="{{ route('admin.iot-devices.revoke', $device) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Revoke access for {{ $device->device_code }}? It will immediately stop being able to submit data.');">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Revoke device access"><i class="bi bi-slash-circle"></i></button>
        </form>
    @else
        <form action="{{ route('admin.iot-devices.reactivate', $device) }}" method="POST" class="d-inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-forest" title="Reactivate device access"><i class="bi bi-arrow-counterclockwise"></i></button>
        </form>
    @endif

    <form action="{{ route('admin.iot-devices.destroy', $device) }}" method="POST" class="d-inline"
          onsubmit="return confirm('Remove {{ $device->device_code }} from the active registry? Its historical data is kept.');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove device"><i class="bi bi-trash"></i></button>
    </form>
</div>
@if(!isset($lockedTeam))
    <div class="mb-3">
        <label for="hardware_team_id" class="form-label">Hardware Team <span class="text-danger">*</span></label>
        <select class="form-select @error('hardware_team_id') is-invalid @enderror" id="hardware_team_id" name="hardware_team_id" required>
            <option value="">Select team…</option>
            @foreach($hardwareTeams as $team)
                <option value="{{ $team->id }}" @selected(old('hardware_team_id', $iotDevice->hardware_team_id ?? '') == $team->id)>
                    {{ $team->name }} ({{ $team->country }})
                </option>
            @endforeach
        </select>
        @error('hardware_team_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@else
    <div class="mb-3">
        <label class="form-label">Hardware Team</label>
        <input type="text" class="form-control" value="{{ $lockedTeam->name }}" disabled readonly>
        <input type="hidden" name="hardware_team_id" value="{{ $lockedTeam->id }}">
        <div class="form-text">Adding this device under {{ $lockedTeam->name }}.</div>
    </div>
@endif

<div class="mb-3">
    <label for="device_code" class="form-label">Device Code <span class="text-danger">*</span></label>
    @if(isset($iotDevice))
        <input type="text" class="form-control" value="{{ $iotDevice->device_code }}" disabled readonly>
        <input type="hidden" name="device_code" value="{{ $iotDevice->device_code }}">
        <div class="form-text">Device codes cannot be changed after provisioning.</div>
    @else
        <input type="text" class="form-control @error('device_code') is-invalid @enderror" id="device_code" name="device_code"
               value="{{ old('device_code') }}" maxlength="50" placeholder="e.g. AEU-UG-014" required>
        @error('device_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
</div>

<div class="mb-3">
    <label for="device_type" class="form-label">Device Type <span class="text-danger">*</span></label>
    <select class="form-select @error('device_type') is-invalid @enderror" id="device_type" name="device_type" required>
        <option value="">Select type…</option>
        @foreach(['numeric_sensor' => 'Numeric Sensor (temp / humidity / CO₂ / weight)', 'media_capture' => 'Media Capture (audio / video / photo)', 'combo' => 'Combo Unit'] as $value => $label)
            <option value="{{ $value }}" @selected(old('device_type', $iotDevice->device_type ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('device_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="expected_interval_minutes" class="form-label">
            Expected Reporting Interval (minutes)@if(isset($iotDevice)) <span class="text-danger">*</span>@endif
        </label>
        <input type="number" min="1" class="form-control @error('expected_interval_minutes') is-invalid @enderror"
               id="expected_interval_minutes" name="expected_interval_minutes"
               value="{{ old('expected_interval_minutes', $iotDevice->expected_interval_minutes ?? '') }}"
               placeholder="Defaults by device type if left blank" @if(isset($iotDevice)) required @endif>
        <div class="form-text">Used by the gap-detection job to flag a silent device.</div>
        @error('expected_interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="hardware_revision" class="form-label">Hardware Revision</label>
        <input type="text" class="form-control @error('hardware_revision') is-invalid @enderror" id="hardware_revision"
               name="hardware_revision" value="{{ old('hardware_revision', $iotDevice->hardware_revision ?? '') }}" maxlength="30">
        @error('hardware_revision') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@if(isset($iotDevice))
    <div class="mb-3">
        <label class="form-label">Assigned Hive</label>
        <input type="text" class="form-control" value="{{ $iotDevice->hive_id ? 'Hive #'.$iotDevice->hive_id : 'Unassigned' }}" disabled readonly>
        <div class="form-text">Hive assignment is managed from the device detail page, not here.</div>
    </div>

    <div class="mb-3">
        <label for="status" class="form-label">Lifecycle Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach(['provisioned', 'deployed', 'offline', 'retired'] as $status)
                <option value="{{ $status }}" @selected(old('status', $iotDevice->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@endif

<div class="mb-3">
    <label for="firmware_notes" class="form-label">Firmware / Build Notes</label>
    <textarea class="form-control @error('firmware_notes') is-invalid @enderror" id="firmware_notes"
              name="firmware_notes" rows="2" maxlength="255">{{ old('firmware_notes', $iotDevice->firmware_notes ?? '') }}</textarea>
    @error('firmware_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
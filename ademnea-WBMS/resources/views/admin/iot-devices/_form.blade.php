{{-- Device fields. Expects: $hardwareTeams (unless $lockedTeam is set); $iotDevice when editing. --}}
@include('admin.iot-devices._styles')

<div class="row g-3">
    <div class="col-md-6">
        @if(!isset($lockedTeam))
            <label for="hardware_team_id" class="form-label">Hardware Team <span class="text-danger" aria-hidden="true">*</span></label>
            <select class="form-select @error('hardware_team_id') is-invalid @enderror" id="hardware_team_id" name="hardware_team_id" required>
                <option value="">Select team…</option>
                @foreach($hardwareTeams as $team)
                    <option value="{{ $team->id }}" @selected(old('hardware_team_id', $iotDevice->hardware_team_id ?? '') == $team->id)>
                        {{ $team->name }} ({{ $team->country }})
                    </option>
                @endforeach
            </select>
            @error('hardware_team_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">The team that built and maintains this device.</div>
        @else
            <label for="locked_team" class="form-label">Hardware Team</label>
            <input type="text" class="form-control" id="locked_team" value="{{ $lockedTeam->name }}" disabled readonly>
            <input type="hidden" name="hardware_team_id" value="{{ $lockedTeam->id }}">
            <div class="form-text">Adding this device under {{ $lockedTeam->name }}.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="device_code" class="form-label">Device Code <span class="text-danger" aria-hidden="true">*</span></label>
        @if(isset($iotDevice))
            <input type="text" class="form-control" id="device_code" value="{{ $iotDevice->device_code }}" disabled readonly>
            <input type="hidden" name="device_code" value="{{ $iotDevice->device_code }}">
            <div class="form-text">Device codes cannot be changed after provisioning.</div>
        @else
            <input type="text" class="form-control @error('device_code') is-invalid @enderror" id="device_code" name="device_code"
                   value="{{ old('device_code') }}" maxlength="50" placeholder="e.g. AEU-UG-014" required>
            @error('device_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">The unique label printed on the device. It cannot be changed later.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="device_type" class="form-label">Device Type <span class="text-danger" aria-hidden="true">*</span></label>
        <select class="form-select @error('device_type') is-invalid @enderror" id="device_type" name="device_type" required>
            <option value="">Select type…</option>
            @foreach(['numeric_sensor' => 'Numeric Sensor (temp / humidity / CO₂ / weight)', 'media_capture' => 'Media Capture (audio / video / photo)', 'combo' => 'Combo Unit'] as $value => $label)
                <option value="{{ $value }}" @selected(old('device_type', $iotDevice->device_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('device_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">What the device measures or records.</div>
    </div>

    <div class="col-md-6">
        <label for="expected_interval_minutes" class="form-label">
            Reports every (minutes)@if(isset($iotDevice)) <span class="text-danger" aria-hidden="true">*</span>@endif
        </label>
        <input type="number" min="1" class="form-control @error('expected_interval_minutes') is-invalid @enderror"
               id="expected_interval_minutes" name="expected_interval_minutes"
               value="{{ old('expected_interval_minutes', $iotDevice->expected_interval_minutes ?? '') }}"
               @if(isset($iotDevice)) required @else placeholder="Default for the type" @endif>
        @error('expected_interval_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">
            How often the device should send data. If it stays silent for longer, it is flagged.
            @unless(isset($iotDevice)) Leave blank for the default: 5 minutes for sensors and combo units, 60 for media capture. @endunless
        </div>
    </div>

    @if(isset($iotDevice))
        <div class="col-md-6">
            <label for="status" class="form-label">Lifecycle Stage <span class="text-danger" aria-hidden="true">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach(['provisioned' => 'Provisioned — registered, not yet on a hive', 'deployed' => 'Deployed — installed on a hive', 'offline' => 'Offline — out of service', 'retired' => 'Retired — no longer in use'] as $status => $label)
                    <option value="{{ $status }}" @selected(old('status', $iotDevice->status) === $status)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="assigned_hive" class="form-label">Assigned Hive</label>
            <input type="text" class="form-control" id="assigned_hive" disabled readonly
                   value="{{ $iotDevice->hive ? ($iotDevice->hive->display_name ?? $iotDevice->hive->hive_code) : ($iotDevice->hive_id ? 'Hive #'.$iotDevice->hive_id : 'Not on a hive') }}">
            <div class="form-text">
                Change this from the <a href="{{ route('admin.iot-devices.show', $iotDevice) }}">device page</a>, not here.
            </div>
        </div>
    @endif

    <div class="col-md-6">
        <label for="hardware_revision" class="form-label">Hardware Revision <span class="text-muted fw-normal">(optional)</span></label>
        <input type="text" class="form-control @error('hardware_revision') is-invalid @enderror" id="hardware_revision"
               name="hardware_revision" value="{{ old('hardware_revision', $iotDevice->hardware_revision ?? '') }}" maxlength="30" placeholder="e.g. rev C">
        @error('hardware_revision') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">The board or enclosure version, if the team tracks one.</div>
    </div>

    <div class="col-12">
        <label for="firmware_notes" class="form-label">Firmware / Build Notes <span class="text-muted fw-normal">(optional)</span></label>
        <textarea class="form-control @error('firmware_notes') is-invalid @enderror" id="firmware_notes"
                  name="firmware_notes" rows="3" maxlength="255">{{ old('firmware_notes', $iotDevice->firmware_notes ?? '') }}</textarea>
        @error('firmware_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Anything the next person should know about this build. Up to 255 characters.</div>
    </div>
</div>

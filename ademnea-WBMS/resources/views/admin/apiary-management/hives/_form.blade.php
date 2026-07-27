{{--
    Shared field partial for Hive create/edit forms.
    Expects $apiary (parent Apiary) and optional $hive (null on create).
--}}
@php($hive = $hive ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="display_name" class="form-label">Display name *</label>
        <input type="text" name="display_name" id="display_name"
               class="form-control @error('display_name') is-invalid @enderror"
               value="{{ old('display_name', $hive?->display_name) }}" required maxlength="150">
        @error('display_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if ($hive)
            <div class="form-text">System identifier: <code>{{ $hive->hybrid_identifier }}</code> (immutable).</div>
        @else
            <div class="form-text">The system-generated hive identifier is assigned automatically on save.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="hive_type" class="form-label">Hive type *</label>
        <select name="hive_type" id="hive_type" class="form-select @error('hive_type') is-invalid @enderror" required>
            @foreach (['TopBar', 'Langstroth', 'Warre', 'Kenya', 'Other'] as $option)
                <option value="{{ $option }}" @selected(old('hive_type', $hive?->hive_type ?? 'Langstroth') === $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        @error('hive_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="construction_material" class="form-label">Construction material</label>
        <input type="text" name="construction_material" id="construction_material"
               class="form-control @error('construction_material') is-invalid @enderror"
               value="{{ old('construction_material', $hive?->construction_material) }}" maxlength="100">
        @error('construction_material') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="installation_date" class="form-label">Installation date</label>
        <input type="date" name="installation_date" id="installation_date"
               class="form-control @error('installation_date') is-invalid @enderror"
               value="{{ old('installation_date', $hive?->installation_date?->format('Y-m-d')) }}">
        @error('installation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="colony_origin" class="form-label">Colony origin</label>
        <select name="colony_origin" id="colony_origin" class="form-select @error('colony_origin') is-invalid @enderror">
            <option value="">— Select —</option>
            @foreach (['Wild Capture', 'Split', 'Package', 'NUC', 'Unknown'] as $option)
                <option value="{{ $option }}" @selected(old('colony_origin', $hive?->colony_origin) === $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        @error('colony_origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="queen_status" class="form-label">Queen status</label>
        <select name="queen_status" id="queen_status" class="form-select @error('queen_status') is-invalid @enderror">
            @foreach (['Present', 'Absent', 'New', 'Old', 'Superseded', 'Unknown'] as $option)
                <option value="{{ $option }}" @selected(old('queen_status', $hive?->queen_status ?? 'Unknown') === $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        @error('queen_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="latitude" class="form-label">Latitude *</label>
        <input type="number" step="0.0000001" name="latitude" id="latitude"
               class="form-control @error('latitude') is-invalid @enderror"
               value="{{ old('latitude', $hive?->latitude ?? '') }}" required>
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="longitude" class="form-label">Longitude *</label>
        <input type="number" step="0.0000001" name="longitude" id="longitude"
               class="form-control @error('longitude') is-invalid @enderror"
               value="{{ old('longitude', $hive?->longitude ?? '') }}" required>
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="last_inspection_date" class="form-label">Last Inspection Date</label>
        <input type="date" name="last_inspection_date" id="last_inspection_date"
               class="form-control @error('last_inspection_date') is-invalid @enderror"
               value="{{ old('last_inspection_date', $hive?->last_inspection_date?->format('Y-m-d')) }}">
        @error('last_inspection_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $hive?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    </div>
</div>

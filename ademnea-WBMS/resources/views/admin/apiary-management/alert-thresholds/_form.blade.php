{{--
    Shared field partial for Alert Threshold create/edit forms.
    Expects $hives collection.
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="key" class="form-label">Key *</label>
        <input type="text" name="key" id="key"
               class="form-control @error('key') is-invalid @enderror"
               value="{{ old('key', $threshold->key ?? '') }}" maxlength="100" required>
        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="value" class="form-label">Value *</label>
        <input type="text" name="value" id="value"
               class="form-control @error('value') is-invalid @enderror"
               value="{{ old('value', $threshold->value ?? '') }}" maxlength="255" required>
        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="description" class="form-label">Description</label>
        <input type="text" name="description" id="description"
               class="form-control @error('description') is-invalid @enderror"
               value="{{ old('description', $threshold->description ?? '') }}" maxlength="255">
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="hive_id" class="form-label">Hive (optional)</label>
        <select name="hive_id" id="hive_id" class="form-select @error('hive_id') is-invalid @enderror">
            <option value="">— Global threshold —</option>
            @foreach($hives as $hive)
                <option value="{{ $hive->id }}" @selected(old('hive_id', $threshold->hive_id ?? '') == $hive->id)>
                    {{ $hive->display_name }} ({{ $hive->hybrid_identifier }})
                </option>
            @endforeach
        </select>
        @error('hive_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

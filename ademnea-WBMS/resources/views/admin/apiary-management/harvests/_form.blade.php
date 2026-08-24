{{--
    Shared field partial for Harvest create/edit forms.
    Expects $harvest (null on create) and $hives collection.
--}}
@php($harvest = $harvest ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="hive_id" class="form-label">Hive *</label>
        <select name="hive_id" id="hive_id" class="form-select @error('hive_id') is-invalid @enderror" required>
            <option value="">— Select hive —</option>
            @foreach($hives as $hive)
                <option value="{{ $hive->id }}" @selected(old('hive_id', $harvest?->hive_id) == $hive->id)>
                    {{ $hive->display_name }} ({{ $hive->hybrid_identifier }})
                </option>
            @endforeach
        </select>
        @error('hive_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="harvest_date" class="form-label">Harvest date *</label>
        <input type="date" name="harvest_date" id="harvest_date"
               class="form-control @error('harvest_date') is-invalid @enderror"
               value="{{ old('harvest_date', $harvest?->harvest_date?->format('Y-m-d')) }}" required>
        @error('harvest_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="honey_yield_kg" class="form-label">Honey yield (kg)</label>
        <input type="number" step="0.01" name="honey_yield_kg" id="honey_yield_kg"
               class="form-control @error('honey_yield_kg') is-invalid @enderror"
               value="{{ old('honey_yield_kg', $harvest?->honey_yield_kg) }}" min="0">
        @error('honey_yield_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="beeswax_yield_kg" class="form-label">Beeswax yield (kg)</label>
        <input type="number" step="0.01" name="beeswax_yield_kg" id="beeswax_yield_kg"
               class="form-control @error('beeswax_yield_kg') is-invalid @enderror"
               value="{{ old('beeswax_yield_kg', $harvest?->beeswax_yield_kg) }}" min="0">
        @error('beeswax_yield_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $harvest?->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

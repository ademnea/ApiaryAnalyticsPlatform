{{--
    Shared field partial for Inspection create/edit forms.
    Expects $inspection (null on create) and $hives collection.
--}}
@php($inspection = $inspection ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="hive_id" class="form-label">Hive *</label>
        <select name="hive_id" id="hive_id" class="form-select @error('hive_id') is-invalid @enderror" required>
            <option value="">— Select hive —</option>
            @foreach($hives as $hive)
                <option value="{{ $hive->id }}" @selected(old('hive_id', $inspection?->hive_id) == $hive->id)>
                    {{ $hive->display_name }} ({{ $hive->hybrid_identifier }})
                </option>
            @endforeach
        </select>
        @error('hive_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="inspected_at" class="form-label">Inspection date *</label>
        <input type="date" name="inspected_at" id="inspected_at"
               class="form-control @error('inspected_at') is-invalid @enderror"
               value="{{ old('inspected_at', $inspection?->inspected_at?->format('Y-m-d')) }}" required>
        @error('inspected_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="strength_rating" class="form-label">Strength rating</label>
        <input type="text" name="strength_rating" id="strength_rating"
               class="form-control @error('strength_rating') is-invalid @enderror"
               value="{{ old('strength_rating', $inspection?->strength_rating) }}" maxlength="50">
        @error('strength_rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="disease_events" class="form-label">Disease events</label>
        <textarea name="disease_events" id="disease_events" rows="2" class="form-control @error('disease_events') is-invalid @enderror">{{ old('disease_events', $inspection?->disease_events) }}</textarea>
        @error('disease_events') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="queen_status_notes" class="form-label">Queen status notes</label>
        <textarea name="queen_status_notes" id="queen_status_notes" rows="2" class="form-control @error('queen_status_notes') is-invalid @enderror">{{ old('queen_status_notes', $inspection?->queen_status_notes) }}</textarea>
        @error('queen_status_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="general_notes" class="form-label">General notes</label>
        <textarea name="general_notes" id="general_notes" rows="3" class="form-control @error('general_notes') is-invalid @enderror">{{ old('general_notes', $inspection?->general_notes) }}</textarea>
        @error('general_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

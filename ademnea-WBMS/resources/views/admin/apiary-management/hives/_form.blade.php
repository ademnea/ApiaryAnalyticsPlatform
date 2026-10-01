{{--
    Shared field partial for Hive create/edit forms.
    Expects $apiary (parent Apiary) and optional $hive (null on create).
--}}
@php($hive = $hive ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="apiary_id" class="form-label">Parent apiary *</label>
        <select name="apiary_id" id="apiary_id" class="form-select @error('apiary_id') is-invalid @enderror" required>
            <option value="">— Select an apiary —</option>
            @foreach(($apiaries ?? collect()) as $apiaryOption)
                <option value="{{ $apiaryOption->id }}" @selected(old('apiary_id', $hive?->apiary_id) == $apiaryOption->id)>
                    {{ $apiaryOption->name }} ({{ $apiaryOption->country }})
                </option>
            @endforeach
        </select>
        @error('apiary_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

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

    {{-- Fills in latitude and longitude from the device's position. The two
         fields below stay as they are, and stay editable. --}}
    <div class="col-12">
        <fieldset class="hive-location" id="hive-location-tools">
            <legend>Hive location</legend>
            <p class="hive-location-help">
                Stand at the hive and use this device's location. Latitude and longitude below fill in for you.
            </p>

            <button type="button" class="btn btn-primary" id="use-my-location">
                <i class="bi bi-crosshair me-1" aria-hidden="true"></i><span>Use my location</span>
            </button>

            <div class="hive-location-status" id="location-status" role="status" aria-live="polite"></div>
            <a href="#" id="check-location" target="_blank" rel="noopener" hidden>
                <i class="bi bi-map me-1" aria-hidden="true"></i>Check this spot on a map
            </a>
        </fieldset>

        {{-- How precise the device's fix was, in metres. Empty when the coordinates were typed. --}}
        <input type="hidden" name="accuracy_meters" id="accuracy_meters" value="{{ old('accuracy_meters', $hive?->accuracy_meters) }}">
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

@pushOnce('styles')
<style>
    .hive-location { margin: 0; padding: 1rem 1.1rem; border: 1px solid var(--clr-border); border-radius: 10px; background: var(--clr-canvas); min-width: 0; }
    .hive-location legend { float: none; width: auto; margin: 0 0 0.25rem; padding: 0; font-size: 0.95rem; font-weight: 700; color: var(--clr-forest); }
    .hive-location-help { margin: 0 0 0.85rem; font-size: 0.85rem; color: #52635A; }
    .hive-location .btn { min-height: 2.4rem; }
    .hive-location-status { margin-top: 0.75rem; font-size: 0.9rem; font-weight: 600; }
    .hive-location-status:empty { display: none; }
    .hive-location-status[data-tone="ok"] { color: #14532D; }
    .hive-location-status[data-tone="warn"] { color: #7A4D00; }
    .hive-location-status[data-tone="error"] { color: #9B1C1C; }
    #check-location { display: inline-block; margin-top: 0.4rem; font-size: 0.85rem; }
</style>
@endPushOnce

@pushOnce('scripts')
@verbatim
<script>
(function () {
    const tools = document.getElementById('hive-location-tools');

    if (!tools) {
        return;
    }

    const latitude = document.getElementById('latitude');
    const longitude = document.getElementById('longitude');
    const accuracy = document.getElementById('accuracy_meters');
    const status = document.getElementById('location-status');
    const check = document.getElementById('check-location');
    const useButton = document.getElementById('use-my-location');

    // A fix worse than this is still used, but the person is told to try again.
    const GOOD_ACCURACY_METRES = 25;

    function say(text, tone) {
        status.textContent = text;
        status.dataset.tone = tone;
    }

    function inRange(lat, lng) {
        return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
    }

    function refreshCheckLink() {
        const lat = parseFloat(latitude.value);
        const lng = parseFloat(longitude.value);
        check.hidden = !inRange(lat, lng);

        if (!check.hidden) {
            check.href = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=18/' + lat + '/' + lng;
        }
    }

    // accuracyMetres is null when the coordinates did not come from this device.
    function setLocation(lat, lng, accuracyMetres) {
        latitude.value = lat.toFixed(7);
        longitude.value = lng.toFixed(7);
        accuracy.value = accuracyMetres === null ? '' : accuracyMetres.toFixed(2);
        refreshCheckLink();
    }

    /* ---- Typed by hand: the stored accuracy no longer describes these numbers ---- */
    [latitude, longitude].forEach((field) => field.addEventListener('input', () => {
        accuracy.value = '';
        say('', '');
        refreshCheckLink();
    }));

    /* ---- Use my location ---- */
    useButton.addEventListener('click', () => {
        if (!('geolocation' in navigator)) {
            say('This browser cannot share its location. Type the coordinates instead.', 'error');
            return;
        }

        if (!window.isSecureContext) {
            say('Browsers only share location on a secure (https) address. Type the coordinates instead.', 'error');
            return;
        }

        const label = useButton.querySelector('span');
        useButton.disabled = true;
        label.textContent = 'Finding your location…';
        say('Finding your location. This can take a few seconds outdoors.', '');

        navigator.geolocation.getCurrentPosition((position) => {
            const metres = Math.round(position.coords.accuracy);
            setLocation(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
            useButton.disabled = false;
            label.textContent = 'Use my location';

            if (metres <= GOOD_ACCURACY_METRES) {
                say('Location set from this device, accurate to about ' + metres + ' m.', 'ok');
            } else {
                say('Location set, but only accurate to about ' + metres + ' m. Stand next to the hive under open sky and try again for a better fix.', 'warn');
            }
        }, (error) => {
            useButton.disabled = false;
            label.textContent = 'Use my location';
            say({
                1: 'Location access was refused. Allow it for this site in the browser settings, or type the coordinates instead.',
                2: 'This device could not work out where it is. Check that location is switched on, or type the coordinates instead.',
                3: 'Finding the location took too long. Move into the open and try again.',
            }[error.code] || 'The location could not be read. Type the coordinates instead.', 'error');
        }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
    });

    refreshCheckLink();
})();
</script>
@endverbatim
@endPushOnce

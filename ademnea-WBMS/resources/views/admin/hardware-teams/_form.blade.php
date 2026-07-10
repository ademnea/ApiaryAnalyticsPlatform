<div class="mb-3">
    <label for="name" class="form-label">Team Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $hardwareTeam->name ?? '') }}" maxlength="150" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
    <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
        <option value="">Select country…</option>
        @foreach(['Uganda', 'South Sudan', 'Tanzania'] as $country)
            <option value="{{ $country }}" @selected(old('country', $hardwareTeam->country ?? '') === $country)>{{ $country }}</option>
        @endforeach
    </select>
    @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="contact_email" class="form-label">Contact Email</label>
        <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email"
               name="contact_email" value="{{ old('contact_email', $hardwareTeam->contact_email ?? '') }}" maxlength="255">
        <div class="form-text">Used for provisioning and health-alert notifications.</div>
        @error('contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="contact_phone" class="form-label">Contact Phone</label>
        <input type="tel" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone"
               name="contact_phone" value="{{ old('contact_phone', $hardwareTeam->contact_phone ?? '') }}" maxlength="30">
        <div class="form-text">Used for critical SMS alerts (e.g. critical battery).</div>
        @error('contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
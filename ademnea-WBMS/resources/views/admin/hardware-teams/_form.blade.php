<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Team Name <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
               value="{{ old('name', $hardwareTeam->name ?? '') }}" maxlength="150" placeholder="e.g. Makerere Field Deployment Team" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">The name admins will see next to every device this team owns.</div>
    </div>

    <div class="col-md-6">
        <label for="country" class="form-label">Country <span class="text-danger" aria-hidden="true">*</span></label>
        <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
            <option value="">Select country…</option>
            @foreach(['Uganda', 'South Sudan', 'Tanzania'] as $country)
                <option value="{{ $country }}" @selected(old('country', $hardwareTeam->country ?? '') === $country)>{{ $country }}</option>
            @endforeach
        </select>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Where the team is based and deploys devices.</div>
    </div>

    <div class="col-md-6">
        <label for="contact_email" class="form-label">Contact Email <span class="text-muted fw-normal">(optional)</span></label>
        <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email"
               name="contact_email" value="{{ old('contact_email', $hardwareTeam->contact_email ?? '') }}" maxlength="255" placeholder="team@example.org">
        @error('contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Device health alerts and provisioning notices are emailed here.</div>
    </div>

    <div class="col-md-6">
        <label for="contact_phone" class="form-label">Contact Phone <span class="text-muted fw-normal">(optional)</span></label>
        <input type="tel" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone"
               name="contact_phone" value="{{ old('contact_phone', $hardwareTeam->contact_phone ?? '') }}" maxlength="30" placeholder="e.g. +256 700 000000">
        @error('contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Urgent alerts, such as a critically low battery, are sent here by SMS.</div>
    </div>
</div>

<div class="mb-3">
    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $member->name ?? '') }}" maxlength="150" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="team_role" class="form-label">Team Role</label>
        <input type="text" class="form-control @error('team_role') is-invalid @enderror" id="team_role" name="team_role"
               value="{{ old('team_role', $member->team_role ?? '') }}" maxlength="100"
               placeholder="e.g. Lead Technician, Field Engineer">
        <div class="form-text">Their responsibility on this team — helps admins know who to contact for what.</div>
        @error('team_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="profession" class="form-label">Profession</label>
        <input type="text" class="form-control @error('profession') is-invalid @enderror" id="profession" name="profession"
               value="{{ old('profession', $member->profession ?? '') }}" maxlength="100">
        @error('profession') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="country" class="form-label">Country</label>
        <select class="form-select @error('country') is-invalid @enderror" id="country" name="country">
            <option value="">Select country…</option>
            @foreach(['Uganda', 'South Sudan', 'Tanzania'] as $country)
                <option value="{{ $country }}" @selected(old('country', $member->country ?? '') === $country)>{{ $country }}</option>
            @endforeach
        </select>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
               value="{{ old('phone', $member->phone ?? '') }}" maxlength="30">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
           value="{{ old('email', $member->email ?? '') }}" maxlength="255">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
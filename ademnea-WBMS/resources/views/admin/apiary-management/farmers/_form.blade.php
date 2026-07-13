{{--
    Shared field partial for Farmer create/edit forms.
    Expects an optional $farmer variable (null on create).
--}}
@php($farmer = $farmer ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="first_name" class="form-label">First name *</label>
        <input type="text" name="first_name" id="first_name"
               class="form-control @error('first_name') is-invalid @enderror"
               value="{{ old('first_name', $farmer?->first_name) }}" required maxlength="100">
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="last_name" class="form-label">Last name *</label>
        <input type="text" name="last_name" id="last_name"
               class="form-control @error('last_name') is-invalid @enderror"
               value="{{ old('last_name', $farmer?->last_name) }}" required maxlength="100">
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $farmer?->email) }}" maxlength="255">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" name="phone" id="phone"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $farmer?->phone) }}" maxlength="20">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="phone_secondary" class="form-label">Secondary Phone</label>
        <input type="text" name="phone_secondary" id="phone_secondary"
               class="form-control @error('phone_secondary') is-invalid @enderror"
               value="{{ old('phone_secondary', $farmer?->phone_secondary) }}" maxlength="20">
        @error('phone_secondary') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="country" class="form-label">Country *</label>
        <select name="country" id="country" class="form-select @error('country') is-invalid @enderror">
            @foreach($countries as $code => $name)
                <option value="{{ $code }}" @selected(old('country', $farmer?->country) === $code)>{{ $name }}</option>
            @endforeach
        </select>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="region" class="form-label">Region</label>
        <input type="text" name="region" id="region"
               class="form-control @error('region') is-invalid @enderror"
               value="{{ old('region', $farmer?->region) }}" maxlength="100">
        @error('region') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="village" class="form-label">Village</label>
        <input type="text" name="village" id="village"
               class="form-control @error('village') is-invalid @enderror"
               value="{{ old('village', $farmer?->village) }}" maxlength="100">
        @error('village') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="national_id" class="form-label">National ID</label>
        <input type="text" name="national_id" id="national_id"
               class="form-control @error('national_id') is-invalid @enderror"
               value="{{ old('national_id', $farmer?->national_id) }}" maxlength="50">
        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if ($farmer)
        <div class="col-md-6">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $farmer?->status) === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>

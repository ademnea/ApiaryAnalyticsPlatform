@extends('layouts.app')

@section('title', 'Register Apiary')
@section('page-title', 'Register Apiary')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.apiaries.index') }}">Apiaries</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
<div class="row g-4 align-items-start">
    <div class="col-lg-8 col-xxl-7">
        <form method="POST" action="{{ route('admin.apiaries.store') }}" class="card apiary-form" id="apiary-form">
            @csrf
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Apiary Details</span>
                <span class="apiary-form-required"><span class="text-danger" aria-hidden="true">*</span> Required</span>
            </div>

            <div class="card-body">
                {{-- Failures that do not belong to one field (see ApiaryController::store). --}}
                @error('error')
                    <div class="alert alert-danger d-flex gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <div>{{ $message }}</div>
                    </div>
                @enderror

                <div class="mb-4">
                    <label for="name" class="form-label">Apiary Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required maxlength="150" autocomplete="off"
                           placeholder="e.g. Kasese Hillside Apiary" aria-describedby="name-help"
                           @if(! $errors->any()) autofocus @endif>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="name-help">A farmer cannot have two apiaries with the same name in one country.</div>
                </div>

                <fieldset class="apiary-form-section">
                    <legend>Location</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="country" class="form-label">Country of Deployment <span class="text-danger" aria-hidden="true">*</span></label>
                            <select name="country" id="country" class="form-select @error('country') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" @selected(old('country') == $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="region" class="form-label">Region</label>
                            <input type="text" name="region" id="region"
                                   class="form-control @error('region') is-invalid @enderror"
                                   value="{{ old('region') }}" maxlength="100">
                            @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="district" class="form-label">District</label>
                            <input type="text" name="district" id="district"
                                   class="form-control @error('district') is-invalid @enderror"
                                   value="{{ old('district') }}" maxlength="100">
                            @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="apiary-form-section">
                    <legend>Management</legend>
                    <div class="mb-3">
                        <label for="farmer_id" class="form-label">Managing Farmer</label>
                        <select name="farmer_id" id="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror" aria-describedby="farmer-help">
                            <option value="">— None —</option>
                            @foreach($farmers as $farmer)
                                <option value="{{ $farmer->id }}" @selected(old('farmer_id') == $farmer->id)>
                                    {{ $farmer->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="farmer-help">
                            @if($farmers->isEmpty())
                                There are no active farmers yet. You can leave this empty and assign one later.
                            @else
                                Only active farmers are listed. Leave empty to assign one later.
                            @endif
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Landmarks, access notes, surrounding forage…">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </fieldset>
            </div>

            <div class="card-footer d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" id="apiary-submit">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i><span>Register Apiary</span>
                </button>
                <a href="{{ route('admin.apiaries.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <aside class="apiary-form-aside" aria-labelledby="apiary-next-title">
            <h2 id="apiary-next-title"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>What happens next</h2>
            <ul>
                <li>The apiary code is generated for you from the name and country.</li>
                <li>The apiary starts as <strong>Active</strong>.</li>
                <li>Once it is registered, add its hives from <a href="{{ route('admin.hives.create') }}">Register Hive</a>.</li>
            </ul>
        </aside>
    </div>
</div>
@endsection

@push('styles')
<style>
    .apiary-form-required { font-family: var(--font-body); font-size: 0.75rem; font-weight: 400; color: var(--clr-muted); }
    .apiary-form .card-body { padding: 1.25rem; }
    .apiary-form .form-label { margin-bottom: 0.3rem; font-weight: 500; color: #1a2e1f; }
    .apiary-form .form-control:focus,
    .apiary-form .form-select:focus { border-color: var(--clr-forest-light); box-shadow: 0 0 0 0.2rem rgba(64, 145, 108, 0.2); }
    .apiary-form-section { margin: 1.5rem 0 0; padding: 0; border: 0; min-width: 0; }
    .apiary-form-section legend { float: none; width: 100%; margin: 0 0 0.75rem; padding: 1rem 0 0; border-top: 1px solid var(--clr-border); font-family: var(--font-display); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: var(--clr-forest-mid); }
    .apiary-form .card-footer { padding: 0.85rem 1.25rem; background: var(--clr-canvas); border-top: 1px solid var(--clr-border); border-radius: 0 0 10px 10px; }
    .apiary-form-aside { padding: 1rem 1.1rem; border: 1px solid var(--clr-border); border-left: 4px solid var(--clr-honey); border-radius: 10px; background: #FFFBF0; }
    .apiary-form-aside h2 { margin: 0 0 0.6rem; font-family: var(--font-display); font-size: 0.9rem; font-weight: 600; color: #1a2e1f; }
    .apiary-form-aside ul { margin: 0; padding-left: 1.1rem; font-size: 0.82rem; color: #3D4F45; }
    .apiary-form-aside li + li { margin-top: 0.4rem; }
    .apiary-form-aside a { color: var(--clr-forest-mid); font-weight: 500; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('apiary-form');
    const submit = document.getElementById('apiary-submit');

    // A second click while the request is in flight would register the apiary twice.
    form.addEventListener('submit', () => {
        submit.disabled = true;
        submit.querySelector('span').textContent = 'Registering…';
    });
})();
</script>
@endpush

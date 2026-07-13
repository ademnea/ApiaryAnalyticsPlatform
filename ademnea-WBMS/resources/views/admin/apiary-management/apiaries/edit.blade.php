@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3">Edit Apiary — {{ $apiary->name }}</h1>

    <form method="POST" action="{{ route('admin.apiaries.update', $apiary) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Apiary Name *</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $apiary->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Country of Deployment *</label>
                <select name="country" class="form-select" required>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ old('country', $apiary->country) == $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Region</label>
                <input type="text" name="region" class="form-control" value="{{ old('region', $apiary->region) }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">District</label>
                <input type="text" name="district" class="form-control" value="{{ old('district', $apiary->district) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Hive Capacity *</label>
                <input type="number" name="hive_capacity" min="0" max="10000" class="form-control" value="{{ old('hive_capacity', $apiary->hive_capacity) }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Managing Farmer</label>
            <select name="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror">
                <option value="">— Organization-managed (no individual farmer) —</option>
                @foreach($farmers as $farmer)
                    <option value="{{ $farmer->id }}" {{ old('farmer_id', $apiary->farmer_id) == $farmer->id ? 'selected' : '' }}>
                        {{ $farmer->full_name }} — {{ $farmer->country }}
                    </option>
                @endforeach
            </select>
            @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <fieldset class="border rounded p-3 mb-3">
            <legend class="fs-6">Contact Information</legend>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact Person Name</label>
                    <input type="text" name="contact_person_name" class="form-control" value="{{ old('contact_person_name', $apiary->contact_person_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact Person Phone</label>
                    <input type="text" name="contact_person_phone" class="form-control" value="{{ old('contact_person_phone', $apiary->contact_person_phone) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact Person Email</label>
                    <input type="email" name="contact_person_email" class="form-control" value="{{ old('contact_person_email', $apiary->contact_person_email) }}">
                </div>
            </div>
        </fieldset>

        <fieldset class="border rounded p-3 mb-3">
            <legend class="fs-6">Location</legend>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" class="form-control" value="{{ old('latitude', $apiary->latitude) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" class="form-control" value="{{ old('longitude', $apiary->longitude) }}">
                </div>
            </div>
        </fieldset>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $apiary->description) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.apiaries.show', $apiary) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection

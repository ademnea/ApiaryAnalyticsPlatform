@extends('layouts.app')

@section('title', 'Register Apiary')
@section('page-title', 'Register Apiary')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.apiaries.index') }}">Apiaries</a></li>
    <li class="breadcrumb-item active" aria-current="page">Register</li>
@endsection

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">Apiary Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.apiaries.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Apiary Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Country of Deployment *</label>
                    <select name="country" class="form-select @error('country') is-invalid @enderror" required>
                        <option value="">— Select —</option>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" {{ old('country') == $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Managing Farmer</label>
                <select name="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror">
                    <option value="">— None —</option>
                    @foreach($farmers as $farmer)
                        <option value="{{ $farmer->id }}" {{ old('farmer_id') == $farmer->id ? 'selected' : '' }}>
                            {{ $farmer->full_name }}
                        </option>
                    @endforeach
                </select>
                @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Register Apiary</button>
                <a href="{{ route('admin.apiaries.index') }}" class="btn btn-outline-forest">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

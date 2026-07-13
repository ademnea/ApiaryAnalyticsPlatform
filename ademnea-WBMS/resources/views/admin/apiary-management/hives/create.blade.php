@extends('layouts.app')

@section('content')
<h1 class="h4 mb-3">Register Hive</h1>

<form method="POST" action="{{ route('admin.hives.store') }}">
    @csrf

    <div class="mb-3">
        <label for="apiary_id" class="form-label">Apiary *</label>
        <select name="apiary_id" id="apiary_id" class="form-select @error('apiary_id') is-invalid @enderror" required>
            <option value="">— Select apiary —</option>
            @foreach($apiaries as $apiary)
                <option value="{{ $apiary->id }}" @selected(old('apiary_id') == $apiary->id)>
                    {{ $apiary->name }} ({{ $apiary->country }})
                </option>
            @endforeach
        </select>
        @error('apiary_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @include('admin.apiary-management.hives._form', ['apiary' => null, 'hive' => null])

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Register Hive</button>
        <a href="{{ route('admin.hives.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

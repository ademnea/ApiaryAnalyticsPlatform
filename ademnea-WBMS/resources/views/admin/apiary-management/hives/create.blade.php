@extends('layouts.app')

@section('content')
<h1 class="h4 mb-3">Register Hive — {{ $apiary->name }}</h1>

<form method="POST" action="{{ route('admin.hives.store', $apiary) }}">
    @csrf
    @include('admin.apiary-management.hives._form', ['apiary' => $apiary])

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Register Hive</button>
        <a href="{{ route('admin.apiaries.show', $apiary) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

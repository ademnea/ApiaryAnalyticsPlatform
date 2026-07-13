@extends('admin.layout')

@section('content')
<h1 class="h4 mb-3">Edit Hive: {{ $hive->display_name }} <small class="text-muted">({{ $hive->hybrid_identifier }})</small></h1>

@if ($errors->any())
    <div class="alert alert-danger">Please fix the errors below.</div>
@endif

<form method="POST" action="{{ route('admin.hives.update', $hive) }}">
    @csrf
    @method('PUT')
    @include('admin.apiary-management.hives._form', ['hive' => $hive, 'apiary' => $hive->apiary])

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.hives.show', $hive) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3">Edit Farmer — {{ $farmer->full_name }}</h1>

    <form method="POST" action="{{ route('admin.farmers.update', $farmer) }}">
        @csrf
        @method('PUT')
        @include('admin.apiary-management.farmers._form', ['statuses' => $statuses ?? ['Active', 'Inactive', 'Suspended']])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.farmers.show', $farmer) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

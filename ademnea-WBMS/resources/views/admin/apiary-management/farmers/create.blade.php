@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3">Register New Farmer</h1>

    <form method="POST" action="{{ route('admin.farmers.store') }}">
        @csrf
        @include('admin.apiary-management.farmers._form')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Register Farmer</button>
            <a href="{{ route('admin.farmers.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

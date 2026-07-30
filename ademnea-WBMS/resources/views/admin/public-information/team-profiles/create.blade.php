@extends('layouts.app')

@section('title', 'Add Team Member')

@section('content')

<div class="container-fluid py-4">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.team-profiles.index') }}">
                    Team Profile Management
                </a>
            </li>

            <li class="breadcrumb-item active">
                Add Team Member
            </li>
        </ol>
    </nav>

    {{-- Page Header --}}
    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            Add Team Member
        </h2>

        <p class="text-muted mb-0">
            Create a new public team member profile for the AdEMNEA website.
        </p>

    </div>

    <form
        action="{{ route('admin.team-profiles.store') }}"
        method="POST"
        enctype="multipart/form-data">

        @include('admin.public-information.team-profiles._form')

    </form>

</div>

@endsection
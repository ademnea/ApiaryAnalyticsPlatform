@extends('layouts.app')

@section('title', 'Edit Team Member')

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

                Edit Team Member

            </li>

        </ol>
    </nav>


    {{-- Page Header --}}
    <div class="mb-4">

        <h2 class="fw-bold mb-1">

            Edit Team Member

        </h2>

        <p class="text-muted mb-0">

            Update this team member profile.

        </p>

    </div>


    <form
        action="{{ route('admin.team-profiles.update', $teamProfile) }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf

        @method('PUT')

        @include('admin.public-information.team-profiles._form')

    </form>

</div>

@endsection
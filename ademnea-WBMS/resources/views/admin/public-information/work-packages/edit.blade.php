@extends('layouts.app')

@section('title', 'Edit Work Package')

@section('content')

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                Edit Work Package

            </h2>

            <p class="text-muted mb-0">

                Update the selected work package.

            </p>

        </div>

        <a
            href="{{ route('admin.work-packages.index') }}"
            class="btn btn-outline-secondary rounded-pill">

            <i class="bi bi-arrow-left me-2"></i>

            Back

        </a>

    </div>


    @if ($errors->any())

        <div class="alert alert-danger shadow-sm">

            <strong>Please correct the following errors:</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route('admin.work-packages.update', $workPackage) }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf
        @method('PUT')

        @include('admin.public-information.work-packages._form')

    </form>

</div>

@endsection
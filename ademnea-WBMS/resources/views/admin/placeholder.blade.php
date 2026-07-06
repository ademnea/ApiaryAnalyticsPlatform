@extends('layouts.app')

@section('title', $title ?? 'Admin')

@section('content')
    <div class="page-header">
        <h1>{{ $title ?? 'Placeholder' }}</h1>
        @if(isset($subtitle))
            <p class="breadcrumb">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="page-content">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">This page is a placeholder for the <strong>{{ $title ?? 'admin area' }}</strong>. Implement the actual UI and controller logic here.</p>
            </div>
        </div>
    </div>
@endsection

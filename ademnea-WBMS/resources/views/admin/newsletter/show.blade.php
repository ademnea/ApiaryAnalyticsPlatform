@extends('layouts.app')

@section('title', $newsletter->title)

@section('content')
<div class="container-fluid"><div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3">{{ $newsletter->title }}</h1><a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
@if($newsletter->image_url)<img src="{{ $newsletter->image_url }}" alt="{{ $newsletter->title }}" class="img-fluid rounded mb-4">@endif
<p class="lead">{{ $newsletter->description }}</p><hr><div>{!! $newsletter->sanitized_content !!}</div>
</div></div></div><div class="col-lg-4"><div class="card"><div class="card-body"><p><strong>Status:</strong> {{ $newsletter->is_published ? 'Published' : 'Draft' }}</p><p><strong>Views:</strong> {{ $newsletter->view_count }}</p><p><strong>Created by:</strong> {{ $newsletter->creator->name ?? 'Unknown' }}</p><p><strong>Created:</strong> {{ $newsletter->created_at->format('M d, Y H:i') }}</p><a href="{{ route('admin.newsletter.edit', $newsletter) }}" class="btn btn-warning w-100"><i class="bi bi-pencil"></i> Edit Newsletter</a></div></div></div></div></div>
@endsection

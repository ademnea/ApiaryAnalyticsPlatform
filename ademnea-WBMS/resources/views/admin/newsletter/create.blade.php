@extends('layouts.app')

@section('title', 'Create Newsletter')

@section('content')
<div class="container-fluid"><div class="row mb-4"><div class="col-md-8"><h1 class="h3">Create Newsletter</h1></div></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body">
<form action="{{ route('admin.newsletter.store') }}" method="POST" enctype="multipart/form-data">@csrf
<div class="mb-3"><label class="form-label" for="title">Title</label><input id="title" name="title" class="form-control" value="{{ old('title') }}" required></div>
<div class="mb-3"><label class="form-label" for="description">Description</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="500" required>{{ old('description') }}</textarea></div>
<div class="mb-3"><label class="form-label" for="content">Content</label><textarea id="content" name="content" class="form-control" rows="12" required>{{ old('content') }}</textarea></div>
<div class="mb-3"><label class="form-label" for="image">Image</label><input id="image" name="image" type="file" class="form-control" accept="image/*" required><small class="text-muted">JPG, PNG, WEBP, or GIF, maximum 5 MB.</small></div>
<button class="btn btn-primary"><i class="bi bi-check-circle"></i> Create Newsletter</button> <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary">Cancel</a>
</form></div></div></div>
@endsection

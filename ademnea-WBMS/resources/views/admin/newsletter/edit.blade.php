@extends('layouts.app')

@section('title', 'Edit Newsletter')

@section('content')
<div class="container-fluid"><h1 class="h3 mb-4">Edit Newsletter</h1>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body"><form action="{{ route('admin.newsletter.update', $newsletter) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
<div class="mb-3"><label class="form-label" for="title">Title</label><input id="title" name="title" class="form-control" value="{{ old('title', $newsletter->title) }}" required></div>
<div class="mb-3"><label class="form-label" for="description">Description</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="500" required>{{ old('description', $newsletter->description) }}</textarea></div>
<div class="mb-3"><label class="form-label" for="content">Content</label><textarea id="content" name="content" class="form-control" rows="12" required>{{ old('content', $newsletter->content) }}</textarea></div>
@if($newsletter->image_url)<div class="mb-3"><img src="{{ $newsletter->image_url }}" alt="{{ $newsletter->title }}" class="img-thumbnail" style="max-width:220px"></div>@endif
<div class="mb-3"><label class="form-label" for="image">Replace image</label><input id="image" name="image" type="file" class="form-control" accept="image/*"></div>
<button class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button> <a href="{{ route('admin.newsletter.show', $newsletter) }}" class="btn btn-secondary">Cancel</a>
</form></div></div></div>
@endsection

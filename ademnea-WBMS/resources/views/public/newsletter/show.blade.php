@extends('layouts.public')

@section('title', $newsletter->title)

@section('content')
<div class="container"><a href="{{ route('public.newsletters.index') }}" class="btn btn-link px-0 mb-3"><i class="bi bi-arrow-left"></i> All newsletters</a><article><h1 class="display-5 mb-2">{{ $newsletter->title }}</h1><p class="text-muted">Published {{ $newsletter->published_at?->format('M d, Y') }} - {{ $newsletter->view_count }} views</p>@if($newsletter->image_url)<img src="{{ $newsletter->image_url }}" alt="{{ $newsletter->title }}" class="img-fluid rounded mb-4">@endif<h2 class="h4">{{ $newsletter->description }}</h2><div class="mt-4">{!! $newsletter->sanitized_content !!}</div></article>@if($related->isNotEmpty())<hr class="my-5"><h2 class="h4 mb-4">Related newsletters</h2><div class="row">@foreach($related as $item)<div class="col-md-4 mb-3"><a href="{{ route('public.newsletters.show', $item->slug) }}" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h3 class="h6 text-dark">{{ $item->title }}</h3><p class="small text-muted mb-0">{{ $item->excerpt }}</p></div></div></a></div>@endforeach</div>@endif</div>
@endsection

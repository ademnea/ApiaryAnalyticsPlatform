@extends('layouts.public')

@section('title', 'Newsletters')

@section('content')
<div class="container-fluid"><div class="mb-5"><h1 class="display-5">Newsletters</h1><p class="lead text-muted">Project updates, announcements, research findings, and community news.</p></div><div class="row">
@forelse($newsletters as $newsletter)<div class="col-md-6 col-lg-4 mb-4"><a href="{{ route('public.newsletters.show', $newsletter->slug) }}" class="text-decoration-none"><div class="card h-100 shadow-sm">@if($newsletter->image_url)<img src="{{ $newsletter->image_url }}" class="card-img-top" alt="{{ $newsletter->title }}" style="height:200px;object-fit:cover">@else<div class="bg-light d-flex align-items-center justify-content-center" style="height:200px"><i class="bi bi-envelope-paper text-muted" style="font-size:3rem"></i></div>@endif<div class="card-body"><h5 class="card-title text-dark">{{ $newsletter->title }}</h5><p class="card-text text-muted">{{ $newsletter->description }}</p></div><div class="card-footer bg-white"><small class="text-muted">{{ $newsletter->published_at?->format('M d, Y') }} · {{ $newsletter->view_count }} views</small></div></div></a></div>
@empty<div class="col-12"><div class="alert alert-info text-center py-5">No newsletters available at this time.</div></div>@endforelse
</div><div class="d-flex justify-content-center mt-4">{{ $newsletters->links() }}</div></div>
@endsection

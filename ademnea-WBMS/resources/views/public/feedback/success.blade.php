@extends('layouts.app')

@section('title', 'Feedback Submitted')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-success">
                <div class="card-body text-center py-5">
                    <div class="display-1 mb-3">✓</div>
                    <h2 class="h4 mb-3">Thank you for your feedback</h2>
                    <p class="text-muted mb-4">Your message has been received and will be reviewed by our team shortly.</p>
                    <a href="{{ route('public.feedback.create') }}" class="btn btn-primary">Submit another message</a>
                </div>
            </div>

            @if($feedback && $feedback->admin_response)
                <div class="card shadow-sm border-info mt-3">
                    <div class="card-body text-start">
                        <h5 class="card-title">Administrator Response</h5>
                        <p class="mb-0">{{ $feedback->admin_response }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

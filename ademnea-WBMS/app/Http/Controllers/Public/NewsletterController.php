<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Services\NewsletterService;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $service)
    {
    }

    public function index(): View
    {
        $newsletters = $this->service->listPublishedNewsletters(20);
        return view('public.newsletter.index', compact('newsletters'));
    }

    public function show(Newsletter $newsletter): View
    {
        abort_unless($newsletter->is_published, 404);
        $newsletter->incrementViewCount();
        $related = $this->service->getRelatedNewsletters($newsletter);

        return view('public.newsletter.show', compact('newsletter', 'related'));
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\View\View;

class PublicationController extends Controller
{
    protected PublicationService $service;

    public function __construct(PublicationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display published publications list (public)
     */
    public function index(): View
    {
        $publications = $this->service->listPublishedPublications(20);
        $years = Publication::published()
            ->pluck('publication_year')
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        return view('public.publications.index', compact('publications', 'years'));
    }

    /**
     * Show publication details (public)
     */
    public function show(Publication $publication): View
    {
        // Only show published publications to public
        if (!$publication->is_published) {
            abort(404);
        }

        return view('public.publications.show', compact('publication'));
    }

    /**
     * Download publication attachment (public)
     */
    public function download(Publication $publication)
    {
        // Verify publication exists and has attachment
        if (!$publication->hasAttachment()) {
            abort(404, 'Attachment not found');
        }

        try {
            return $this->service->getAttachmentStream($publication);
        } catch (\Exception $e) {
            abort(404, 'File not found');
        }
    }
}

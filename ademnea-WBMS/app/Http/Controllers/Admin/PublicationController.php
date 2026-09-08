<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    protected PublicationService $service;

    public function __construct(PublicationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a paginated list of all publications (admin)
     */
    public function index(): View
    {
        $publications = $this->service->listPublicationsForAdmin(20);
        return view('admin.publications.index', compact('publications'));
    }

    /**
     * Show the publication creation form
     */
    public function create(): View
    {
        return view('admin.publications.create');
    }

    /**
     * Store a newly created publication in storage
     */
    public function store(StorePublicationRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $publication = $this->service->createPublication(
                $data,
                $request->file('attachment'),
                $request->file('image')
            );

            return redirect()
                ->route('admin.publications.show', $publication->id)
                ->with('success', 'Publication created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create publication: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified publication details (admin view)
     */
    public function show(Publication $publication): View
    {
        return view('admin.publications.show', compact('publication'));
    }

    /**
     * Show the form for editing the specified publication
     */
    public function edit(Publication $publication): View
    {
        return view('admin.publications.edit', compact('publication'));
    }

    /**
     * Update the specified publication in storage
     */
    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        try {
            $publication = $this->service->updatePublication(
                $publication,
                $request->validated(),
                $request->file('attachment'),
                $request->file('image')
            );

            return redirect()
                ->route('admin.publications.show', $publication->id)
                ->with('success', 'Publication updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update publication: ' . $e->getMessage());
        }
    }

    /**
     * Delete the specified publication from storage
     */
    public function destroy(Publication $publication)
    {
        try {
            $this->service->deletePublication($publication);
            return redirect()
                ->route('admin.publications.index')
                ->with('success', 'Publication deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete publication: ' . $e->getMessage());
        }
    }

    /**
     * Publish the specified publication
     */
    public function publish(Publication $publication)
    {
        try {
            $this->service->publishPublication($publication);
            return redirect()
                ->back()
                ->with('success', 'Publication published successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to publish publication: ' . $e->getMessage());
        }
    }

    /**
     * Unpublish the specified publication
     */
    public function unpublish(Publication $publication)
    {
        try {
            $this->service->unpublishPublication($publication);
            return redirect()
                ->back()
                ->with('success', 'Publication unpublished successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to unpublish publication: ' . $e->getMessage());
        }
    }

    /**
     * Search publications by keyword
     */
    public function search(Request $request): View
    {
        $query = $request->input('q');
        $publications = $query
            ? $this->service->searchPublications($query, 20)
            : collect([]);

        return view('admin.publications.search', compact('publications', 'query'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsletterRequest;
use App\Http\Requests\UpdateNewsletterRequest;
use App\Models\Newsletter;
use App\Services\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $service)
    {
    }

    public function index(Request $request): View
    {
        $newsletters = $this->service->listNewslettersForAdmin(15, $request->string('q')->trim()->value() ?: null);
        return view('admin.newsletter.index', compact('newsletters'));
    }

    public function create(): View
    {
        return view('admin.newsletter.create');
    }

    public function store(StoreNewsletterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $newsletter = $this->service->createNewsletter($data, $request->file('image'));

        return redirect()->route('admin.newsletter.show', $newsletter)->with('success', 'Newsletter created successfully.');
    }

    public function show(Newsletter $newsletter): View
    {
        return view('admin.newsletter.show', compact('newsletter'));
    }

    public function edit(Newsletter $newsletter): View
    {
        return view('admin.newsletter.edit', compact('newsletter'));
    }

    public function update(UpdateNewsletterRequest $request, Newsletter $newsletter): RedirectResponse
    {
        $newsletter = $this->service->updateNewsletter($newsletter, $request->validated(), $request->file('image'));
        return redirect()->route('admin.newsletter.show', $newsletter)->with('success', 'Newsletter updated successfully.');
    }

    public function destroy(Newsletter $newsletter): RedirectResponse
    {
        $this->service->deleteNewsletter($newsletter);
        return redirect()->route('admin.newsletter.index')->with('success', 'Newsletter deleted successfully.');
    }

    public function restore(int $newsletter): RedirectResponse
    {
        $record = Newsletter::withTrashed()->findOrFail($newsletter);
        $this->service->restoreNewsletter($record);
        return redirect()->route('admin.newsletter.index')->with('success', 'Newsletter restored successfully.');
    }

    public function publish(Newsletter $newsletter): RedirectResponse
    {
        $this->service->publishNewsletter($newsletter);
        return back()->with('success', 'Newsletter published successfully.');
    }

    public function unpublish(Newsletter $newsletter): RedirectResponse
    {
        $this->service->unpublishNewsletter($newsletter);
        return back()->with('success', 'Newsletter unpublished successfully.');
    }
}

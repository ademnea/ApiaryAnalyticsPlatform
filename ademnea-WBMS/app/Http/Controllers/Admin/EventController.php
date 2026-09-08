<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\AddEventPhotosRequest;
use App\Models\Event;
use App\Models\EventPhoto;
use App\Services\EventService;
use Illuminate\View\View;

class EventController extends Controller
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a paginated list of all events (admin)
     */
    public function index(): View
    {
        $events = $this->service->listEventsForAdmin(12);
        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the event creation form
     */
    public function create(): View
    {
        return view('admin.events.create');
    }

    /**
     * Store a newly created event in storage
     */
    public function store(StoreEventRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $event = $this->service->createEvent(
                $data,
                $request->file('photos', [])
            );

            return redirect()
                ->route('admin.events.show', $event->id)
                ->with('success', 'Event created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create event: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified event details (admin view)
     */
    public function show(Event $event): View
    {
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event
     */
    public function edit(Event $event): View
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update the specified event in storage
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        try {
            $event = $this->service->updateEvent(
                $event,
                $request->validated(),
                $request->file('photos', [])
            );

            return redirect()
                ->route('admin.events.show', $event->id)
                ->with('success', 'Event updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update event: ' . $e->getMessage());
        }
    }

    /**
     * Delete the specified event from storage
     */
    public function destroy(Event $event)
    {
        try {
            $this->service->deleteEvent($event);
            return redirect()
                ->route('admin.events.index')
                ->with('success', 'Event deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete event: ' . $e->getMessage());
        }
    }

    /**
     * Publish the specified event
     */
    public function publish(Event $event)
    {
        try {
            $this->service->publishEvent($event);
            return redirect()
                ->back()
                ->with('success', 'Event published successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to publish event: ' . $e->getMessage());
        }
    }

    /**
     * Unpublish the specified event
     */
    public function unpublish(Event $event)
    {
        try {
            $this->service->unpublishEvent($event);
            return redirect()
                ->back()
                ->with('success', 'Event unpublished successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to unpublish event: ' . $e->getMessage());
        }
    }

    /**
     * Add photos to an existing event
     */
    public function addPhotos(AddEventPhotosRequest $request, Event $event)
    {
        try {
            $this->service->addPhotosToEvent($event, $request->file('photos', []));
            return redirect()
                ->back()
                ->with('success', 'Photos added successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to add photos: ' . $e->getMessage());
        }
    }

    /**
     * Delete a specific photo from an event
     */
    public function deletePhoto(EventPhoto $photo)
    {
        try {
            $this->service->deleteEventPhoto($photo);
            return redirect()
                ->back()
                ->with('success', 'Photo deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete photo: ' . $e->getMessage());
        }
    }

    /**
     * Reorder photos for an event
     */
    public function reorderPhotos(Event $event)
    {
        try {
            $photoIds = request()->input('photo_ids', []);
            $this->service->reorderPhotos($event, $photoIds);
            return response()->json(['success' => true, 'message' => 'Photos reordered successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reorder photos: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Search events by keyword
     */
    public function search(View $view)
    {
        $query = request()->input('q');
        $events = $query
            ? $this->service->searchEvents($query, 12)
            : collect([]);

        return view('admin.events.search', compact('events', 'query'));
    }
}

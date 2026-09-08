<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
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
     * Display a paginated list of published events
     */
    public function index(): View
    {
        $events = $this->service->listPublishedEvents(15);
        return view('public.events.index', compact('events'));
    }

    /**
     * Display the specified event details (public view)
     */
    public function show(Event $event): View
    {
        // Return 404 if event is not published
        if (!$event->is_published) {
            abort(404);
        }

        return view('public.events.show', compact('event'));
    }

    /**
     * Display upcoming events
     */
    public function upcoming(): View
    {
        $events = $this->service->getUpcomingEvents(15);
        return view('public.events.upcoming', compact('events'));
    }

    /**
     * Display past events
     */
    public function past(): View
    {
        $events = $this->service->getPastEvents(15);
        return view('public.events.past', compact('events'));
    }
}

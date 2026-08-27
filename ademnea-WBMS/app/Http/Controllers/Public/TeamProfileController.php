<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TeamProfile;
use Illuminate\View\View;

class TeamProfileController extends Controller
{
    /**
     * Display all published team profiles publicly.
     */
    public function index(): View
    {
        $teamProfiles = TeamProfile::published()->get();

        return view('public.team.index', compact('teamProfiles'));
    }

    /**
     * Display a single published team profile publicly.
     */
    public function show(TeamProfile $teamProfile): View
    {
        abort_unless($teamProfile->status === 'Published', 404);

        return view('public.team.show', compact('teamProfile'));
    }
}
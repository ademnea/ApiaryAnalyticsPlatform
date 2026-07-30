<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamProfileRequest;
use App\Models\TeamProfile;
use App\Services\TeamProfileService;
use Illuminate\Http\Request;

class TeamProfileController extends Controller
{
    public function __construct(
        private readonly TeamProfileService $teamProfileService
    ) {
    }

    /**
     * Display Team Members
     */
    public function index(Request $request)
    {
        $query = TeamProfile::query()->latest();

        if ($request->filled('search')) {

            $search = '%' . $request->search . '%';

            $query->where(function ($q) use ($search) {

                $q->where('full_name', 'like', $search)
                    ->orWhere('role', 'like', $search)
                    ->orWhere('institution', 'like', $search);
            });
        }

        if ($request->filled('status')) {

            $query->where('status', $request->status);
        }

        $teamProfiles = $query
            ->orderBy('display_order')
            ->paginate(10)
            ->appends(
                $request->only([
                    'search',
                    'status'
                ])
            );

        $stats = [
            'total' => TeamProfile::count(),
            'published' => TeamProfile::where('status', 'Published')->count(),
            'draft' => TeamProfile::where('status', 'Draft')->count(),
            'archived' => TeamProfile::where('status', 'Archived')->count(),
        ];

        return view(
            'admin.public-information.team-profiles.index',
            compact(
                'teamProfiles',
                'stats'
            )
        );
    }

    /**
     * Show Create Form
     */
    public function create()
    {
        return view(
            'admin.public-information.team-profiles.create'
        );
    }

    /**
     * Store Team Member
     */
    public function store(TeamProfileRequest $request)
    {
        $this->teamProfileService->create($request);

        return redirect()
            ->route('admin.team-profiles.index')
            ->with(
                'success',
                'Team member created successfully.'
            );
    }

    /**
     * View Team Member
     */
    public function show(TeamProfile $teamProfile)
    {
        return view(
            'admin.public-information.team-profiles.show',
            compact('teamProfile')
        );
    }

    /**
     * Edit Team Member
     */
    public function edit(TeamProfile $teamProfile)
    {
        return view(
            'admin.public-information.team-profiles.edit',
            compact('teamProfile')
        );
    }

    /**
     * Update Team Member
     */
    public function update(
        TeamProfileRequest $request,
        TeamProfile $teamProfile
    ) {
        $this->teamProfileService->update(
            $request,
            $teamProfile
        );

        return redirect()
            ->route(
                'admin.team-profiles.edit',
                $teamProfile
            )
            ->with(
                'success',
                'Team member updated successfully.'
            );
    }

    /**
     * Delete Team Member
     */
    public function destroy(TeamProfile $teamProfile)
    {
        $this->teamProfileService->delete($teamProfile);

        return redirect()
            ->route('admin.team-profiles.index')
            ->with(
                'success',
                'Team member deleted successfully.'
            );
    }
}
<?php

namespace App\Services;

use App\Http\Requests\TeamProfileRequest;
use App\Models\TeamProfile;
use Illuminate\Support\Facades\Storage;

class TeamProfileService
{
    /**
     * Store a new Team Member
     */
    public function create(TeamProfileRequest $request): TeamProfile
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request
                ->file('profile_photo')
                ->store('team-profiles', 'public');
        }

        return TeamProfile::create($data);
    }

    /**
     * Update Team Member
     */
    public function update(TeamProfileRequest $request, TeamProfile $teamProfile): TeamProfile
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {

            if (
                $teamProfile->profile_photo &&
                Storage::disk('public')->exists($teamProfile->profile_photo)
            ) {
                Storage::disk('public')->delete($teamProfile->profile_photo);
            }

            $data['profile_photo'] = $request
                ->file('profile_photo')
                ->store('team-profiles', 'public');
        }

        $teamProfile->update($data);

        return $teamProfile;
    }

    /**
     * Delete Team Member
     */
    public function delete(TeamProfile $teamProfile): void
    {
        if (
            $teamProfile->profile_photo &&
            Storage::disk('public')->exists($teamProfile->profile_photo)
        ) {
            Storage::disk('public')->delete($teamProfile->profile_photo);
        }

        $teamProfile->delete();
    }
}
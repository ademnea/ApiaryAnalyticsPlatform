<?php

namespace App\Services;

use App\Models\IotHardwareTeam;
use App\Models\IotHardwareTeamMember;
use Illuminate\Database\Eloquent\Collection;

class IotHardwareTeamMemberService
{
    public function register(IotHardwareTeam $team, array $data): IotHardwareTeamMember
    {
        return $team->members()->create([
            'name' => $data['name'],
            'team_role' => $data['team_role'] ?? null,
            'profession' => $data['profession'] ?? null,
            'country' => $data['country'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);
    }

    public function update(IotHardwareTeamMember $member, array $data): IotHardwareTeamMember
    {
        $member->update([
            'name' => $data['name'],
            'team_role' => $data['team_role'] ?? null,
            'profession' => $data['profession'] ?? null,
            'country' => $data['country'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return $member->fresh();
    }

    public function deactivate(IotHardwareTeamMember $member): void
    {
        $member->update(['is_active' => false]);
    }

    public function reactivate(IotHardwareTeamMember $member): void
    {
        $member->update(['is_active' => true]);
    }

    public function listForTeam(IotHardwareTeam $team): Collection
    {
        return $team->members()->orderBy('name')->get();
    }
}
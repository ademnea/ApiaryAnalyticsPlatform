<?php

namespace App\Services;

use App\Models\IotHardwareTeam;
use Illuminate\Database\Eloquent\Collection;

class IotHardwareTeamRegistryService
{
    public function register(array $data): IotHardwareTeam
    {
        return IotHardwareTeam::create([
            'name' => $data['name'],
            'country' => $data['country'],
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'is_active' => true,
        ]);
    }

    public function update(IotHardwareTeam $team, array $data): IotHardwareTeam
    {
        $team->update([
            'name' => $data['name'],
            'country' => $data['country'],
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
        ]);

        return $team->fresh();
    }

    public function deactivate(IotHardwareTeam $team): void
    {
        // Deliberately does NOT cascade to devices under this team.
        // A deactivated team just stops receiving alert dispatches
        // (see alert routing table, §7) — its devices keep operating.
        $team->update(['is_active' => false]);
    }

    public function reactivate(IotHardwareTeam $team): void
    {
        $team->update(['is_active' => true]);
    }

    public function list(): Collection
    {
        return IotHardwareTeam::orderBy('name')->get();
    }
}
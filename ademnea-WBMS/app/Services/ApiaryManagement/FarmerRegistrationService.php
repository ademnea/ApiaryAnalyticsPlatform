<?php

namespace App\Services\ApiaryManagement;

use App\Models\Farmer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FarmerRegistrationService
{
    public function register(array $data): Farmer
    {
        $data['registration_date'] = $data['registration_date'] ?? now();
        $data['status'] = $data['status'] ?? 'Active';

        return Farmer::create($data);
    }

    public function update(Farmer $farmer, array $data): Farmer
    {
        $farmer->update($data);

        return $farmer->fresh();
    }

    public function find(int $farmerId): Farmer
    {
        return Farmer::with(['apiaries' => function ($query) {
            $query->withCount('hives');
        }])->findOrFail($farmerId);
    }

    public function list(array $filters = [])
    {
        $query = Farmer::query();

        if (! empty($filters['country'])) {
            $query->byCountry($filters['country']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'ilike', $term)
                  ->orWhere('last_name', 'ilike', $term)
                  ->orWhere('email', 'ilike', $term)
                  ->orWhere('phone', 'ilike', $term)
                  ->orWhere('national_id', 'ilike', $term);
            });
        }

        return $query->latest()->paginate(15);
    }

    public function delete(Farmer $farmer): bool
    {
        return (bool) $farmer->delete();
    }

    public function restore(Farmer $farmer): bool
    {
        return (bool) $farmer->restore();
    }

    public function deactivate(Farmer $farmer, ?string $reason = null): bool
    {
        return (bool) $farmer->update([
            'status' => 'Inactive',
        ]);
    }
}

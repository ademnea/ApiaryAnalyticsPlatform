<?php

namespace App\Contracts;

use App\Models\Farmer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FarmerRegistryServiceContract
{
    public function register(array $data): Farmer;
    public function update(Farmer $farmer, array $data): Farmer;
    public function find(int $farmerId): Farmer;
    public function list(array $filters = []);
    public function delete(Farmer $farmer): bool;
    public function restore(Farmer $farmer): bool;
    public function deactivate(Farmer $farmer, ?string $reason = null): bool;
}

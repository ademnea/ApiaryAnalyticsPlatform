<?php

namespace App\Contracts;

use App\Models\Apiary;
use App\Models\Farmer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ApiaryRegistryServiceContract
{
    public function register(array $data): Apiary;
    public function createApiary(array $data): Apiary;
    public function updateApiary(Apiary $apiary, array $data): Apiary;
    public function update(Apiary $apiary, array $data): Apiary;
    public function deleteApiary(Apiary $apiary): bool;
    public function getApiaryWithHives(int $apiaryId, array $filters = []): Apiary;
    public function getApiaryStatistics(Apiary $apiary, ?int $year = null): array;
    public function list(array $filters = []): LengthAwarePaginator;
    public function find(int $id): Apiary;
    public function assignableFarmers(): Collection;
    public function deactivate(Apiary $apiary): void;
}

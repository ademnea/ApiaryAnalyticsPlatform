<?php

namespace App\Contracts;

use App\Models\Apiary;
use App\Models\Hive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface HiveRegistryServiceContract
{
    public function register(Apiary $apiary, array $data): Hive;
    public function createHive(Apiary $apiary, array $data): Hive;
    public function generateHiveCode(Apiary $apiary): string;
    public function generateHybridIdentifier(Apiary $apiary): string;
    public function update(Hive $hive, array $data): Hive;
    public function updateHive(Hive $hive, array $data): Hive;
    public function list(?Apiary $apiary = null, array $filters = []): LengthAwarePaginator;
    public function findByCode(string $code): ?Hive;
    public function getHiveWithAllData(int $hiveId): Hive;
    public function deleteHive(Hive $hive): bool;
    public function changeHiveStatus(Hive $hive, string $newStatus, ?string $note, ?int $userId): Hive;
    public function validateStatusTransition(string $currentStatus, string $newStatus): bool;
    public function getHivesByApiary(int $apiaryId, array $filters = []): Collection;
    public function getHiveLocation(int $hiveId): array;
}

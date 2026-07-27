<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveTemperature;
use App\Models\HiveHumidity;
use App\Models\HiveCarbondioxide;
use App\Models\HiveWeight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class SensorDataService
{
    /**
     * Get temperature data for a hive
     */
    public function getTemperatureData(Farmer $farmer, int $hiveId, array $params = []): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        $query = HiveTemperature::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc');

        if (isset($params['start'])) {
            $query->where('created_at', '>=', Carbon::parse($params['start']));
        }

        if (isset($params['end'])) {
            $query->where('created_at', '<=', Carbon::parse($params['end']));
        }

        $perPage = $params['per_page'] ?? 100;

        return $query->paginate($perPage);
    }

    /**
     * Get humidity data for a hive
     */
    public function getHumidityData(Farmer $farmer, int $hiveId, array $params = []): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        $query = HiveHumidity::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc');

        if (isset($params['start'])) {
            $query->where('created_at', '>=', Carbon::parse($params['start']));
        }

        if (isset($params['end'])) {
            $query->where('created_at', '<=', Carbon::parse($params['end']));
        }

        $perPage = $params['per_page'] ?? 100;

        return $query->paginate($perPage);
    }

    /**
     * Get CO2 data for a hive
     */
    public function getCarbonDioxideData(Farmer $farmer, int $hiveId, array $params = []): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        $query = HiveCarbondioxide::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc');

        if (isset($params['start'])) {
            $query->where('created_at', '>=', Carbon::parse($params['start']));
        }

        if (isset($params['end'])) {
            $query->where('created_at', '<=', Carbon::parse($params['end']));
        }

        $perPage = $params['per_page'] ?? 100;

        return $query->paginate($perPage);
    }

    /**
     * Get weight data for a hive
     */
    public function getWeightData(Farmer $farmer, int $hiveId, array $params = []): LengthAwarePaginator
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        $query = HiveWeight::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc');

        if (isset($params['start'])) {
            $query->where('created_at', '>=', Carbon::parse($params['start']));
        }

        if (isset($params['end'])) {
            $query->where('created_at', '<=', Carbon::parse($params['end']));
        }

        $perPage = $params['per_page'] ?? 100;

        return $query->paginate($perPage);
    }

    /**
     * Get latest readings for all sensor types
     */
    public function getLatestReadings(Farmer $farmer, int $hiveId): array
    {
        $this->verifyHiveOwnership($farmer, $hiveId);

        $response = [
            'data_available' => false,
            'temperature' => null,
            'humidity' => null,
            'co2' => null,
            'weight' => null,
        ];

        // Get latest temperature
        $temp = HiveTemperature::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($temp) {
            $response['data_available'] = true;
            $response['temperature'] = [
                'honey_section' => $this->parseZoneValue($temp->record, 0),
                'brood_section' => $this->parseZoneValue($temp->record, 1),
                'exterior' => $this->parseZoneValue($temp->record, 2),
                'recorded_at' => $temp->created_at->toIso8601String(),
            ];
        }

        // Get latest humidity
        $hum = HiveHumidity::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($hum) {
            $response['data_available'] = true;
            $response['humidity'] = [
                'honey_section' => $this->parseZoneValue($hum->record, 0),
                'brood_section' => $this->parseZoneValue($hum->record, 1),
                'exterior' => $this->parseZoneValue($hum->record, 2),
                'recorded_at' => $hum->created_at->toIso8601String(),
            ];
        }

        // Get latest CO2
        $co2 = HiveCarbondioxide::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($co2) {
            $response['data_available'] = true;
            $response['co2'] = [
                'co2_level' => (float) $co2->record,
                'recorded_at' => $co2->created_at->toIso8601String(),
            ];
        }

        // Get latest weight
        $weight = HiveWeight::where('hive_id', $hiveId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($weight) {
            $response['data_available'] = true;
            $response['weight'] = [
                'weight_kg' => (float) $weight->record,
                'recorded_at' => $weight->created_at->toIso8601String(),
            ];
        }

        return $response;
    }

    /**
     * Parse zone value from delimited record string
     * Returns null for sentinel value 2
     */
    private function parseZoneValue(?string $record, int $index): ?float
    {
        if (!$record) {
            return null;
        }

        $parts = explode('*', $record);

        if (!isset($parts[$index])) {
            return null;
        }

        $value = (float) $parts[$index];

        // Sentinel value 2 indicates sensor fault
        if ($value == 2.0) {
            return null;
        }

        return $value;
    }

    /**
     * Verify hive ownership
     */
    private function verifyHiveOwnership(Farmer $farmer, int $hiveId): void
    {
        Hive::where('id', $hiveId)
            ->whereHas('farm', function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->firstOrFail();
    }
}
<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\SensorDataRequest;
use App\Models\Farmer;
use App\Services\Farmer\SensorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SensorController extends Controller
{
    protected SensorDataService $sensorDataService;

    public function __construct(SensorDataService $sensorDataService)
    {
        $this->sensorDataService = $sensorDataService;
    }

    /**
     * Get temperature data for a hive
     */
    public function temperature(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $params = $request->validated();
        $data = $this->sensorDataService->getTemperatureData($farmer, $hiveId, $params);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Get humidity data for a hive
     */
    public function humidity(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $params = $request->validated();
        $data = $this->sensorDataService->getHumidityData($farmer, $hiveId, $params);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Get CO2 data for a hive
     */
    public function carbondioxide(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $params = $request->validated();
        $data = $this->sensorDataService->getCarbonDioxideData($farmer, $hiveId, $params);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Get weight data for a hive
     */
    public function weight(SensorDataRequest $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $params = $request->validated();
        $data = $this->sensorDataService->getWeightData($farmer, $hiveId, $params);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    /**
     * Get latest readings for all sensor types
     */
    public function latest(Request $request, int $hiveId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $data = $this->sensorDataService->getLatestReadings($farmer, $hiveId);

        return response()->json($data);
    }
}
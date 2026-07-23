<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Models\Hive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HiveMapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'sw_lat' => 'required|numeric|between:-90,90',
            'sw_lng' => 'required|numeric|between:-180,180',
            'ne_lat' => 'required|numeric|between:-90,90',
            'ne_lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid bounding box parameters.',
                'details' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $hives = Hive::query()
            ->with(['apiary:id,name,apiary_code,farmer_id'])
            ->withinBounds(
                (float) $validated['sw_lat'],
                (float) $validated['ne_lat'],
                (float) $validated['sw_lng'],
                (float) $validated['ne_lng']
            )
            ->get(['id', 'hybrid_identifier', 'display_name', 'current_status', 'latitude', 'longitude', 'apiary_id']);

        return response()->json([
            'data' => $hives,
        ]);
    }
}

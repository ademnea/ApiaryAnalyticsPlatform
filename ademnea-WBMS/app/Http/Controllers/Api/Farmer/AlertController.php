<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ClampsPageSize;
use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\Farmer\AlertService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UC-FAPI-16: view and dismiss alerts.
 *
 * alerts.farmer_id is a foreign key to farmers.id. It previously received
 * $request->user()->id — a users.id — so the listing and the ownership check
 * both operated on whatever farmer happened to share that number.
 */
class AlertController extends Controller
{
    use ApiResponse, ClampsPageSize, ResolvesFarmer;

    public function __construct(
        private readonly AlertService $alertService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $alerts = $this->alertService->fetchForFarmer(
            $this->farmerId($request),
            $this->pageSize($request, 15)
        );

        return $this->success($alerts);
    }

    public function markRead(Request $request, int $alertId): JsonResponse
    {
        $alert = Alert::find($alertId);

        if (! $alert) {
            return $this->notFound('Alert not found.');
        }

        $ok = $this->alertService->markRead($alert, $this->farmerId($request));

        if (! $ok) {
            return $this->forbidden('Access denied.');
        }

        return $this->success(['alert_id' => $alertId, 'is_read' => true], 'Alert marked as read.');
    }
}

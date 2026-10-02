<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\Farmer\AlertService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REQ-F-FAPI-25, 26: the farmer's alerts.
 */
class AlertController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(private readonly AlertService $alertService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $alerts = $this->alertService->fetchForFarmer(
            $this->currentFarmer($request)->id,
            min((int) $request->input('per_page', 15), 100)
        );

        return $this->paginated($alerts);
    }

    public function markRead(Request $request, int $alertId): JsonResponse
    {
        $alert = Alert::find($alertId);
        if (! $alert) {
            return $this->notFound('Alert not found.');
        }

        if (! $this->alertService->markRead($alert, $this->currentFarmer($request)->id)) {
            return $this->forbidden('This alert does not belong to you.');
        }

        return $this->success(['alert_id' => $alert->id, 'is_read' => true], 'Alert marked as read.');
    }
}

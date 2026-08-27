<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\Alert\RegisterDeviceTokenRequest;
use App\Models\Alert;
use App\Models\Farmer;
use App\Services\Farmer\AlertService;
use App\Services\Farmer\FarmerAuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AlertService $alertService,
        private readonly FarmerAuditService $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $alerts = $this->alertService->fetchForFarmer(
            $request->user()->id,
            (int) $request->input('per_page', 15)
        );

        return $this->success($alerts);
    }

    public function markRead(Request $request, int $alertId): JsonResponse
    {
        $alert = Alert::find($alertId);

        if (!$alert) {
            return $this->notFound('Alert not found.');
        }

        $ok = $this->alertService->markRead($alert, $request->user()->id);

        if (!$ok) {
            return $this->forbidden('This alert does not belong to you.');
        }

        return $this->success(['alert_id' => $alertId, 'is_read' => true], 'Alert marked as read.');
    }

    public function storeDeviceToken(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $farmer = $request->user();

        $farmer->update(['fcm_token' => $request->input('fcm_token')]);

        $this->audit->log($farmer->id, 'device_token_registered', $farmer->id);

        return $this->success(null, 'Device token registered.');
    }
}

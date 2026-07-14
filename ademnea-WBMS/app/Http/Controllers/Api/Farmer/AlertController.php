<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\Farmer\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Get all alerts for the authenticated farmer
     */
    public function index(Request $request): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 25);
        $alerts = $this->alertService->getAlerts($farmer, $perPage);

        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    /**
     * Mark an alert as read
     */
    public function markAsRead(Request $request, int $alertId): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $alert = $this->alertService->markAsRead($farmer, $alertId);

        return response()->json([
            'message' => 'Alert marked as read.',
            'data' => $alert,
        ]);
    }
}
use App\Http\Requests\Farmer\Alert\RegisterDeviceTokenRequest;
use App\Models\Alert;
use App\Services\Farmer\AlertService;
use App\Services\Farmer\FarmerAuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Routes:
 *   GET   /api/v1/farmer/alerts                      REQ-F-FAPI-25
 *   PATCH /api/v1/farmer/alerts/{alert_id}/read      REQ-F-FAPI-26
 *   POST  /api/v1/farmer/device-token                REQ-F-FAPI-27
 */
class AlertController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AlertService $alertService,
        private readonly FarmerAuditService $audit
    ) {}

    /** REQ-F-FAPI-25 */
    public function index(Request $request): JsonResponse
    {
        $alerts = $this->alertService->fetchForFarmer(
            $request->user()->id,
            (int) $request->input('per_page', 15)
        );

        return $this->success($alerts);
    }

    /** REQ-F-FAPI-26 */
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

    /** REQ-F-FAPI-27 — upsert FCM token, never log the token value */
    public function storeDeviceToken(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $farmer = $request->user();

        $farmer->update(['fcm_token' => $request->input('fcm_token')]);

        $this->audit->log($farmer->id, 'device_token_registered', $farmer->id);

        return $this->success(null, 'Device token registered.');
    }
}

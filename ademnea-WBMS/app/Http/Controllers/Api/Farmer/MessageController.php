<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\SubmitMessageRequest;
use App\Services\Farmer\FarmerHiveAccessService;
use App\Services\Farmer\FarmerMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REQ-F-FAPI-31, 32: farmer-to-admin messages.
 */
class MessageController extends Controller
{
    use ApiResponse;
    use ResolvesFarmer;

    public function __construct(
        private readonly FarmerMessageService $messageService,
        private readonly FarmerHiveAccessService $hiveAccess,
    ) {
    }

    public function store(SubmitMessageRequest $request): JsonResponse
    {
        $farmer = $this->currentFarmer($request);
        $data = $request->validated();

        // A message may only reference one of the farmer's own hives.
        if (! empty($data['hive_id'])) {
            $this->hiveAccess->findOwnedHive($farmer, (int) $data['hive_id']);
        }

        $message = $this->messageService->submit($farmer->id, $data);

        return $this->created([
            'id' => $message->id,
            'subject' => $message->subject,
            'status' => $message->status,
            'created_at' => $message->created_at,
        ], 'Message sent to admin successfully.');
    }

    public function index(Request $request): JsonResponse
    {
        $messages = $this->messageService->listForFarmer(
            $this->currentFarmer($request)->id,
            min((int) $request->input('per_page', 15), 100)
        );

        return $this->paginated($messages);
    }
}

<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Api\Farmer\Concerns\ClampsPageSize;
use App\Http\Controllers\Api\Farmer\Concerns\ResolvesFarmer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\SubmitMessageRequest;
use App\Services\Farmer\FarmerHiveAccessService;
use App\Services\Farmer\FarmerMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Routes:
 *   POST /api/v1/farmer/messages     REQ-F-FAPI-31
 *   GET  /api/v1/farmer/messages     REQ-F-FAPI-32
 *
 * farmer_messages.farmer_id is a foreign key to farmers.id. Passing the
 * users.id here did not just mis-filter the listing, it stamped new messages
 * with the wrong owner, so the admin inbox attributed them to another farmer.
 */
class MessageController extends Controller
{
    use ApiResponse, ClampsPageSize, ResolvesFarmer;

    public function __construct(
        private readonly FarmerMessageService $messageService,
        private readonly FarmerHiveAccessService $hiveAccess,
    ) {}

    /** REQ-F-FAPI-31 */
    public function store(SubmitMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        // exists:hives only proves the hive exists; it must also be this farmer's.
        if (! empty($data['hive_id'])) {
            $this->hiveAccess->findOwnedHive($this->farmer($request), (int) $data['hive_id']);
        }

        $message = $this->messageService->submit($this->farmerId($request), $data);

        return $this->created([
            'id'         => $message->id,
            'subject'    => $message->subject,
            'status'     => $message->status,
            'created_at' => $message->created_at,
        ], 'Message sent to admin successfully.');
    }

    /** REQ-F-FAPI-32 */
    public function index(Request $request): JsonResponse
    {
        $messages = $this->messageService->listForFarmer(
            $this->farmerId($request),
            $this->pageSize($request, 15)
        );

        return $this->success($messages);
    }
}

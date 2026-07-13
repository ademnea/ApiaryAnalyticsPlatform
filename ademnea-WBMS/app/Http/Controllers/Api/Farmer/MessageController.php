<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\Message\SubmitMessageRequest;
use App\Services\Farmer\FarmerMessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Routes:
 *   POST /api/v1/farmer/messages     REQ-F-FAPI-31
 *   GET  /api/v1/farmer/messages     REQ-F-FAPI-32
 */
class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FarmerMessageService $messageService
    ) {}

    /** REQ-F-FAPI-31 */
    public function store(SubmitMessageRequest $request): JsonResponse
    {
        $message = $this->messageService->submit(
            $request->user()->id,
            $request->validated()
        );

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
            $request->user()->id,
            (int) $request->input('per_page', 15)
        );

        return $this->success($messages);
    }
}

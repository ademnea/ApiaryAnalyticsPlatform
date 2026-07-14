<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Farmer\SubmitMessageRequest;
use App\Models\Farmer;
use App\Services\Farmer\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Submit a message to admin
     */
    public function store(SubmitMessageRequest $request): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $data = $request->validated();
        $message = $this->messageService->submitMessage($farmer, $data);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been submitted successfully.',
            'data' => $message,
        ], 201);
    }

    /**
     * Get all messages for the authenticated farmer
     */
    public function index(Request $request): JsonResponse
    {
        $farmer = Farmer::where('user_id', $request->user()->id)->firstOrFail();

        $perPage = $request->input('per_page', 25);
        $messages = $this->messageService->getMessages($farmer, $perPage);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }
}
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

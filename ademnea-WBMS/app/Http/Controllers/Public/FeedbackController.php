<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Models\FeedbackAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(): View
    {
        $categories = \App\Models\FeedbackCategory::pluck('name','id');
        return view('public.feedback.feedback', compact('categories'));
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['submitted_at'] = now();

        $feedback = Feedback::create([
            'feedback_category_id' => $data['feedback_category_id'] ?? null,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'organization' => $data['organization'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'submitted_at' => $data['submitted_at'],
        ]);

        $disk = env('FEEDBACK_FILES_DISK', config('filesystems.default'));

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = Storage::disk($disk)->putFile('feedback/attachments', $file);
                $feedback->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                ]);
            }
        }

        return redirect()->route('public.feedback.success')
            ->with('success', 'Thanks for your feedback.')
            ->with('feedback', $feedback);
    }

    public function success(): View
    {
        return view('public.feedback.success', [
            'feedback' => session('feedback'),
        ]);
    }
}

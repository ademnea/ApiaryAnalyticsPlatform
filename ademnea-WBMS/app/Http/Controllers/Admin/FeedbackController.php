<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFeedbackStatusRequest;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = Feedback::query()->with('category');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', $term)
                  ->orWhere('full_name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('message', 'like', $term)
                  ->orWhere('organization', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('feedback_category_id')) {
            $query->where('feedback_category_id', $request->feedback_category_id);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        $feedbackList = $query->latest()->paginate(12)->appends($request->query());
        $categories = \App\Models\FeedbackCategory::pluck('name','id');

        return view('admin.feedback.index', compact('feedbackList','categories'));
    }

    public function show(Feedback $feedback): View
    {
        $feedback->load('attachments','category');
        return view('admin.feedback.show', compact('feedback'));
    }

    public function update(UpdateFeedbackStatusRequest $request, Feedback $feedback)
    {
        $feedback->update(['status' => $request->status]);
        return back()->with('success', 'Status updated.');
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return redirect()->route('admin.feedback.index')->with('success', 'Feedback deleted.');
    }
}

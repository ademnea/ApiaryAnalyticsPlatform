<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScholarshipRequest;
use App\Http\Requests\UpdateScholarshipRequest;
use App\Models\Scholarship;
use App\Models\ScholarshipAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scholarship::query();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', $search)
                    ->orWhere('institution', 'like', $search)
                    ->orWhere('country', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $scholarships = $query->withCount('attachments')
            ->latest()
            ->paginate(12)
            ->appends($request->only(['search', 'country', 'category', 'status']));

        $countries = Scholarship::query()->select('country')->distinct()->pluck('country');
        $categories = Scholarship::query()->select('category')->distinct()->pluck('category');

        $stats = [
            'totalScholarships' => Scholarship::count(),
            'activeScholarships' => Scholarship::where('status', 'active')->count(),
            'expiringSoon' => Scholarship::where('status', 'active')
                ->whereBetween('application_deadline', [now(), now()->addDays(30)])
                ->count(),
            'expiredScholarships' => Scholarship::where('status', 'expired')->count(),
        ];

        return view('admin.scholarships.index', compact('scholarships', 'countries', 'categories', 'stats'));
    }

    public function create(): View
    {
        return view('admin.scholarships.create');
    }

    public function store(StoreScholarshipRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('scholarships/banners', 'public');
        }

        $scholarship = Scholarship::create($data);

        if ($request->hasFile('attachment_files')) {
            $this->storeAttachments($scholarship, $request->file('attachment_files'));
        }

        return redirect()->route('admin.scholarship.index')
            ->with('success', 'Scholarship created successfully.');
    }

    public function show(Scholarship $scholarship): View
    {
        $scholarship->load('attachments');

        return view('admin.scholarships.show', compact('scholarship'));
    }

    public function edit(Scholarship $scholarship): View
    {
        $scholarship->load('attachments');

        return view('admin.scholarships.edit', compact('scholarship'));
    }

    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('banner_image')) {
            if ($scholarship->banner_image) {
                Storage::disk('public')->delete($scholarship->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('scholarships/banners', 'public');
        }

        $scholarship->update($data);

        if ($request->hasFile('attachment_files')) {
            $this->storeAttachments($scholarship, $request->file('attachment_files'));
        }

        return redirect()->route('admin.scholarship.edit', $scholarship)
            ->with('success', 'Scholarship updated successfully.');
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        if ($scholarship->banner_image) {
            Storage::disk('public')->delete($scholarship->banner_image);
        }

        foreach ($scholarship->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $scholarship->delete();

        return redirect()->route('admin.scholarship.index')
            ->with('success', 'Scholarship deleted successfully.');
    }

    protected function storeAttachments(Scholarship $scholarship, array $files): void
    {
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('scholarships/attachments', 'public');
            $scholarship->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
            ]);
        }
    }
}

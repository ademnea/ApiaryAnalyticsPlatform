<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkPackageRequest;
use App\Models\WorkPackage;
use App\Services\WorkPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkPackageController extends Controller
{
    public function __construct(
        private readonly WorkPackageService $workPackageService
    ) {
    }

    /**
     * Display all work packages.
     */
    public function index(Request $request): View
    {
        $query = WorkPackage::query();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('wp_number', 'like', $search)
                    ->orWhere('title', 'like', $search)
                    ->orWhere('lead', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $workPackages = $query
            ->orderBy('display_order')
            ->latest()
            ->paginate(10)
            ->appends($request->only(['search', 'status']));

        $stats = [
            'total' => WorkPackage::count(),
            'published' => WorkPackage::where('status', 'Published')->count(),
            'draft' => WorkPackage::where('status', 'Draft')->count(),
            'archived' => WorkPackage::where('status', 'Archived')->count(),
        ];

        return view(
            'admin.public-information.work-packages.index',
            compact('workPackages', 'stats')
        );
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('admin.public-information.work-packages.create');
    }

    /**
     * Store a new work package.
     */
    public function store(WorkPackageRequest $request): RedirectResponse
    {
        $this->workPackageService->create($request);

        return redirect()
            ->route('admin.work-packages.index')
            ->with('success', 'Work Package created successfully.');
    }

    /**
     * Display a work package.
     */
    public function show(WorkPackage $workPackage): View
    {
        return view(
            'admin.public-information.work-packages.show',
            compact('workPackage')
        );
    }

    /**
     * Show edit form.
     */
    public function edit(WorkPackage $workPackage): View
    {
        return view(
            'admin.public-information.work-packages.edit',
            compact('workPackage')
        );
    }

    /**
     * Update a work package.
     */
    public function update(
        WorkPackageRequest $request,
        WorkPackage $workPackage
    ): RedirectResponse {
        $this->workPackageService->update($request, $workPackage);

        return redirect()
            ->route('admin.work-packages.edit', $workPackage)
            ->with('success', 'Work Package updated successfully.');
    }

    /**
     * Delete a work package.
     */
    public function destroy(WorkPackage $workPackage): RedirectResponse
    {
        $this->workPackageService->delete($workPackage);

        return redirect()
            ->route('admin.work-packages.index')
            ->with('success', 'Work Package deleted successfully.');
    }
}
<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WorkPackage;
use Illuminate\View\View;

class WorkPackageController extends Controller
{
    /**
     * Display all published work packages publicly.
     */
    public function index(): View
    {
        $workPackages = WorkPackage::published()
            ->orderBy('display_order')
            ->latest()
            ->paginate(9);

        return view('public.work-packages.index', compact('workPackages'));
    }

    /**
     * Display a single published work package publicly.
     */
    public function show(WorkPackage $workPackage): View
    {
        abort_unless($workPackage->status === WorkPackage::STATUS_PUBLISHED, 404);

        return view('public.work-packages.show', compact('workPackage'));
    }
}
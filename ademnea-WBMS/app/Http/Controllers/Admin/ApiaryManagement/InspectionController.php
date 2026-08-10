<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Http\Requests\ApiaryManagement\StoreInspectionRequest;
use App\Http\Requests\ApiaryManagement\UpdateInspectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(Request $request): View
    {
        $inspections = Inspection::query()
            ->with(['hive', 'inspector'])
            ->latest()
            ->paginate(20);

        return view('admin.apiary-management.inspections.index', compact('inspections'));
    }

    public function create(): View
    {
        return view('admin.apiary-management.inspections.create');
    }

    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        Inspection::create($request->validated());

        return redirect()
            ->route('admin.inspections.index')
            ->with('success', 'Inspection record created successfully.');
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['hive', 'inspector']);

        return view('admin.apiary-management.inspections.show', compact('inspection'));
    }

    public function edit(Inspection $inspection): View
    {
        return view('admin.apiary-management.inspections.edit', compact('inspection'));
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): RedirectResponse
    {
        $inspection->update($request->validated());

        return redirect()
            ->route('admin.inspections.show', $inspection)
            ->with('success', 'Inspection record updated successfully.');
    }

    public function destroy(Inspection $inspection): RedirectResponse
    {
        $inspection->delete();

        return redirect()
            ->route('admin.inspections.index')
            ->with('success', 'Inspection record removed.');
    }
}

<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Hive;
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
        return view('admin.apiary-management.inspections.create', [
            'hives' => $this->hivesForSelection(),
        ]);
    }

    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        $inspection = Inspection::create($request->validated());
        $this->syncHiveLastInspectionDate($inspection->hive);

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
        return view('admin.apiary-management.inspections.edit', [
            'inspection' => $inspection,
            'hives' => $this->hivesForSelection(),
        ]);
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): RedirectResponse
    {
        $previousHive = $inspection->hive;
        $inspection->update($request->validated());
        $this->syncHiveLastInspectionDate($previousHive);
        $this->syncHiveLastInspectionDate($inspection->fresh()->hive);

        return redirect()
            ->route('admin.inspections.show', $inspection)
            ->with('success', 'Inspection record updated successfully.');
    }

    public function destroy(Inspection $inspection): RedirectResponse
    {
        $hive = $inspection->hive;
        $inspection->delete();
        $this->syncHiveLastInspectionDate($hive);

        return redirect()
            ->route('admin.inspections.index')
            ->with('success', 'Inspection record removed.');
    }

    private function hivesForSelection()
    {
        return Hive::query()
            ->with('apiary')
            ->orderBy('hybrid_identifier')
            ->orderBy('display_name')
            ->get();
    }

    private function syncHiveLastInspectionDate(?Hive $hive): void
    {
        if (! $hive) {
            return;
        }

        $hive->update([
            'last_inspection_date' => $hive->inspections()->max('inspected_at'),
        ]);
    }
}

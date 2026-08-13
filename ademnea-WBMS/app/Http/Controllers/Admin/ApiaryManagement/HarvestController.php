<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Models\HarvestRecord;
use App\Models\Hive;
use App\Http\Requests\ApiaryManagement\StoreHarvestRequest;
use App\Http\Requests\ApiaryManagement\UpdateHarvestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HarvestController extends Controller
{
    public function index(Request $request): View
    {
        $harvests = HarvestRecord::query()
            ->with(['hive', 'harvester'])
            ->latest()
            ->paginate(20);

        return view('admin.apiary-management.harvests.index', compact('harvests'));
    }

    public function create(): View
    {
        return view('admin.apiary-management.harvests.create', [
            'hives' => $this->hivesForSelection(),
        ]);
    }

    public function store(StoreHarvestRequest $request): RedirectResponse
    {
        HarvestRecord::create($request->validated());

        return redirect()
            ->route('admin.harvests.index')
            ->with('success', 'Harvest record created successfully.');
    }

    public function show(HarvestRecord $harvest): View
    {
        $harvest->load(['hive', 'harvester']);

        return view('admin.apiary-management.harvests.show', compact('harvest'));
    }

    public function edit(HarvestRecord $harvest): View
    {
        return view('admin.apiary-management.harvests.edit', [
            'harvest' => $harvest,
            'hives' => $this->hivesForSelection(),
        ]);
    }

    public function update(UpdateHarvestRequest $request, HarvestRecord $harvest): RedirectResponse
    {
        $harvest->update($request->validated());

        return redirect()
            ->route('admin.harvests.show', $harvest)
            ->with('success', 'Harvest record updated successfully.');
    }

    public function destroy(HarvestRecord $harvest): RedirectResponse
    {
        $harvest->delete();

        return redirect()
            ->route('admin.harvests.index')
            ->with('success', 'Harvest record removed.');
    }

    private function hivesForSelection()
    {
        return Hive::query()
            ->with('apiary')
            ->orderBy('hybrid_identifier')
            ->orderBy('display_name')
            ->get();
    }
}

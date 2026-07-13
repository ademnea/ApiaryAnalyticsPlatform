<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Exceptions\ApiaryManagement\InvalidHiveStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiaryManagement\ChangeHiveStatusRequest;
use App\Http\Requests\ApiaryManagement\StoreHiveRequest;
use App\Http\Requests\ApiaryManagement\UpdateHiveRequest;
use App\Models\Apiary;
use App\Models\Hive;
use App\Services\ApiaryManagement\HiveRegistrationService;
use App\Services\ApiaryManagement\HiveStatusChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HiveController extends Controller
{
    public function __construct(
        private readonly HiveRegistrationService $registrationService,
        private readonly HiveStatusChangeService $statusService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $hives = Hive::query()
            ->when($request->filled('apiary_id'), fn ($q) => $q->byApiary((int) $request->input('apiary_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('current_status', $request->string('status')))
            ->when($request->filled('needs_inspection'), fn ($q) => $q->needingInspection())
            ->with('apiary')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $apiaries = Apiary::where('status', 'Active')->get();
        $statuses = ['Active', 'Inactive', 'Under Inspection', 'Queenless', 'Absconded', 'Decommissioned'];

        return view('admin.apiary-management.hives.index', compact('hives', 'apiaries', 'statuses'));
    }

    public function create(?Apiary $apiary = null): View
    {
        if ($apiary === null) {
            $apiaries = Apiary::where('status', 'Active')->get();

            return view('admin.apiary-management.hives.select-apiary', compact('apiaries'));
        }

        return view('admin.apiary-management.hives.create', compact('apiary'));
    }

    public function store(StoreHiveRequest $request, Apiary $apiary): RedirectResponse
    {
        $hive = $this->registrationService->register($apiary, $request->validated());

        return redirect()
            ->route('admin.hives.show', $hive)
            ->with('success', "Hive \"{$hive->hybrid_identifier}\" registered.");
    }

    public function show(Hive $hive): View
    {
        $hive = $this->registrationService->getHiveWithAllData($hive->id);

        return view('admin.apiary-management.hives.show', compact('hive'));
    }

    public function edit(Hive $hive): View
    {
        return view('admin.apiary-management.hives.edit', compact('hive'));
    }

    public function update(UpdateHiveRequest $request, Hive $hive): RedirectResponse
    {
        $hive = $this->registrationService->updateHive($hive, $request->validated());

        return redirect()
            ->route('admin.hives.show', $hive)
            ->with('success', 'Hive details updated.');
    }

    public function updateStatus(ChangeHiveStatusRequest $request, Hive $hive): RedirectResponse
    {
        try {
            $hive = $this->statusService->updateStatus(
                $hive,
                $request->validated('status'),
                auth()->user(),
                $request->validated('change_notes')
            );
        } catch (InvalidHiveStatusTransitionException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.hives.show', $hive)
            ->with('success', "Hive status changed to \"{$hive->current_status}\".");
    }

    public function destroy(Hive $hive): RedirectResponse
    {
        $this->registrationService->deleteHive($hive);

        return redirect()
            ->route('admin.hives.index')
            ->with('success', 'Hive removed.');
    }
}

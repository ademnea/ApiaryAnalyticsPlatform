<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiaryManagement\StoreApiaryRequest;
use App\Http\Requests\ApiaryManagement\UpdateApiaryRequest;
use App\Models\Apiary;
use App\Services\ApiaryManagement\ApiaryRegistrationService;
use Illuminate\Http\Request;

class ApiaryController extends Controller
{
    private ApiaryRegistrationService $service;

    public function __construct(ApiaryRegistrationService $service)
    {
        $this->service = $service;

        $this->middleware('auth');
        $this->middleware('permission:manage-apiaries');
    }

    /**
     * GET /admin/apiaries
     */
    public function index(Request $request)
    {
        $filters = $request->only(['country', 'status', 'managing_entity', 'farmer_id', 'unassigned', 'all']);
        $apiaries = $this->service->list($filters);

        return view('admin.apiary-management.apiaries.index', [
            'apiaries'  => $apiaries,
            'countries' => ['Uganda', 'South Sudan', 'Tanzania'],
            'statuses'  => ['active', 'inactive', 'decommissioned'],
        ]);
    }

    /**
     * GET /admin/apiaries/create
     */
    public function create()
    {
        return view('admin.apiary-management.apiaries.create', [
            'countries' => ['Uganda', 'South Sudan', 'Tanzania'],
            'farmers'   => $this->service->assignableFarmers(),
        ]);
    }

    /**
     * POST /admin/apiaries
     */
    public function store(StoreApiaryRequest $request)
    {
        try {
            $apiary = $this->service->register($request->validated());

            return redirect()->route('admin.apiaries.show', $apiary)
                ->with('success', 'Apiary created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * GET /admin/apiaries/{apiary}
     */
    public function show(Apiary $apiary)
    {
        $apiary = $this->service->find($apiary->id);

        return view('admin.apiary-management.apiaries.show', ['apiary' => $apiary]);
    }

    /**
     * GET /admin/apiaries/{apiary}/edit
     */
    public function edit(Apiary $apiary)
    {
        return view('admin.apiary-management.apiaries.edit', [
            'apiary'    => $apiary,
            'countries' => ['Uganda', 'South Sudan', 'Tanzania'],
            'farmers'   => $this->service->assignableFarmers(),
        ]);
    }

    /**
     * PUT /admin/apiaries/{apiary}
     */
    public function update(UpdateApiaryRequest $request, Apiary $apiary)
    {
        try {
            $apiary = $this->service->update($apiary, $request->validated());

            return redirect()->route('admin.apiaries.show', $apiary)
                ->with('success', 'Apiary updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * PATCH /admin/apiaries/{apiary}/deactivate
     */
    public function deactivate(Apiary $apiary)
    {
        $this->service->deactivate($apiary);

        return redirect()->route('admin.apiaries.index')->with('success', 'Apiary deactivated successfully.');
    }

    /**
     * DELETE /admin/apiaries/{apiary}
     */
    public function destroy(Apiary $apiary)
    {
        $apiary->delete();

        return redirect()->route('admin.apiaries.index')->with('success', 'Apiary deleted successfully.');
    }
}
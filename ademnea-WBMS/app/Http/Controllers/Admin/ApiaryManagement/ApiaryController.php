<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Contracts\ApiaryRegistryServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiaryManagement\StoreApiaryRequest;
use App\Http\Requests\ApiaryManagement\UpdateApiaryRequest;
use App\Models\Apiary;
use Illuminate\Http\Request;

class ApiaryController extends Controller
{
    public function __construct(private ApiaryRegistryServiceContract $service)
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-apiaries');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['country', 'status', 'managing_entity', 'farmer_id', 'unassigned', 'all']);
        $apiaries = $this->service->list($filters);

        return view('admin.apiary-management.apiaries.index', [
            'apiaries'  => $apiaries,
            'countries' => config('countries'),
            'statuses'  => ['active', 'inactive', 'decommissioned'],
        ]);
    }

    public function create()
    {
        return view('admin.apiary-management.apiaries.create', [
            'countries' => config('countries'),
            'farmers'   => $this->service->assignableFarmers(),
        ]);
    }

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

    public function show(Apiary $apiary)
    {
        $apiary = $this->service->find($apiary->id);

        return view('admin.apiary-management.apiaries.show', ['apiary' => $apiary]);
    }

    public function edit(Apiary $apiary)
    {
        return view('admin.apiary-management.apiaries.edit', [
            'apiary'    => $apiary,
            'countries' => config('countries'),
            'farmers'   => $this->service->assignableFarmers(),
        ]);
    }

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

    public function deactivate(Apiary $apiary)
    {
        $this->service->deactivate($apiary);

        return redirect()->route('admin.apiaries.index')->with('success', 'Apiary deactivated successfully.');
    }

    public function destroy(Apiary $apiary)
    {
        $apiary->delete();

        return redirect()->route('admin.apiaries.index')->with('success', 'Apiary deleted successfully.');
    }
}

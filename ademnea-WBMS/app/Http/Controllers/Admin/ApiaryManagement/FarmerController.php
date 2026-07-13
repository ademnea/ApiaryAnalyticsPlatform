<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FarmerRequest;
use App\Services\ApiaryManagement\FarmerRegistrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerController extends Controller
{
    public function __construct(private readonly FarmerRegistrationService $farmerService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $farmers = $this->farmerService->list($request->only([
            'country', 'status', 'search',
        ]));

        $countries = ['UG', 'SS', 'TZ'];
        $statuses = ['Active', 'Inactive', 'Suspended'];

        return view('admin.apiary-management.farmers.index', compact('farmers', 'countries', 'statuses'));
    }

    public function create(): View
    {
        $countries = ['UG', 'SS', 'TZ'];

        return view('admin.apiary-management.farmers.create', compact('countries'));
    }

    public function store(FarmerStoreRequest $request): RedirectResponse
    {
        $farmer = $this->farmerService->register($request->validated());

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', "Farmer \"{$farmer->full_name}\" registered.");
    }

    public function show(Farmer $farmer): View
    {
        $farmer = $this->farmerService->find($farmer->id);

        return view('admin.apiary-management.farmers.show', compact('farmer'));
    }

    public function edit(Farmer $farmer): View
    {
        $countries = ['UG', 'SS', 'TZ'];

        return view('admin.apiary-management.farmers.edit', compact('farmer', 'countries'));
    }

    public function update(FarmerUpdateRequest $request, Farmer $farmer): RedirectResponse
    {
        $farmer = $this->farmerService->update($farmer, $request->validated());

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', 'Farmer profile updated.');
    }

    public function destroy(Farmer $farmer): RedirectResponse
    {
        $this->farmerService->delete($farmer);

        return redirect()
            ->route('admin.farmers.index')
            ->with('success', 'Farmer removed.');
    }

    public function restore(Farmer $farmer): RedirectResponse
    {
        $this->farmerService->restore($farmer);

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', 'Farmer restored.');
    }
}

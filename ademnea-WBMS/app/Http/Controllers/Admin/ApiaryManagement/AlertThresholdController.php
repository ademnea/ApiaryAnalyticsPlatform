<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiaryManagement\StoreAlertThresholdRequest;
use App\Http\Requests\ApiaryManagement\UpdateAlertThresholdRequest;
use App\Models\AlertThreshold;
use App\Models\Hive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertThresholdController extends Controller
{
    public function index(Request $request): View
    {
        $thresholds = AlertThreshold::query()
            ->with('hive')
            ->latest()
            ->paginate(20);

        return view('admin.apiary-management.alert-thresholds.index', compact('thresholds'));
    }

    public function create(): View
    {
        $hives = Hive::orderBy('display_name')->get();

        return view('admin.apiary-management.alert-thresholds.create', compact('hives'));
    }

    public function store(StoreAlertThresholdRequest $request): RedirectResponse
    {
        AlertThreshold::create($request->validated());

        return redirect()
            ->route('admin.alert-thresholds.index')
            ->with('success', 'Alert threshold created successfully.');
    }

    public function edit(AlertThreshold $threshold): View
    {
        $hives = Hive::orderBy('display_name')->get();

        return view('admin.apiary-management.alert-thresholds.edit', compact('threshold', 'hives'));
    }

    public function update(UpdateAlertThresholdRequest $request, AlertThreshold $threshold): RedirectResponse
    {
        $threshold->update($request->validated());

        return redirect()
            ->route('admin.alert-thresholds.index')
            ->with('success', 'Alert threshold updated successfully.');
    }

    public function destroy(AlertThreshold $threshold): RedirectResponse
    {
        $threshold->delete();

        return redirect()
            ->route('admin.alert-thresholds.index')
            ->with('success', 'Alert threshold removed.');
    }
}

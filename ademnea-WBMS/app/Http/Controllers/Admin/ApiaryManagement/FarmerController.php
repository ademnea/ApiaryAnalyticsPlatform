<?php

namespace App\Http\Controllers\Admin\ApiaryManagement;

use App\Contracts\FarmerRegistryServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiaryManagement\FarmerStoreRequest;
use App\Http\Requests\ApiaryManagement\FarmerUpdateRequest;
use App\Models\Farmer;
use App\Models\FarmerMessage;
use App\Services\ApiaryManagement\FarmerApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Note: store() deliberately creates a farmer WITHOUT a login account. This
 * is the registry path — recording a farmer the project already works with,
 * who may never use the mobile app. If they later self-register with the
 * same email, AuthService adopts this row rather than creating a duplicate.
 * Provisioning a farmer WITH a login is done from /admin/users/create.
 */
class FarmerController extends Controller
{
    public function __construct(
        private readonly FarmerRegistryServiceContract $farmerService,
        private readonly FarmerApprovalService $approvals,
    ) {
    }

    public function index(Request $request): View
    {
        $farmers = $this->farmerService->list($request->only([
            'country', 'status', 'search',
        ]));

        $countries = config('countries');
        $statuses = ['Active', 'Inactive', 'Suspended'];

        return view('admin.apiary-management.farmers.index', compact('farmers', 'countries', 'statuses'));
    }

    public function create(): View
    {
        $countries = config('countries');

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
        $countries = config('countries');

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

    public function pending(Request $request): View
    {
        $farmers = Farmer::query()
            ->pending()
            // Eager loaded so the view can show whether the row came from a
            // self-registration (has a login) or the admin registry (does not)
            // without an N+1.
            ->with('user')
            ->orderByDesc('registration_date')
            ->paginate(20);

        return view('admin.apiary-management.farmers.pending', compact('farmers'));
    }

    public function approve(Request $request, Farmer $farmer): RedirectResponse
    {
        // Optional, and must stay optional: the pending screen can approve
        // without choosing a role, in which case the base farmer role applies.
        $validated = $request->validate([
            'role' => ['nullable', 'in:farmer,farmer-write'],
        ]);

        $this->approvals->approve($farmer, $validated['role'] ?? 'farmer');

        return redirect()
            ->route('admin.farmers.pending')
            ->with('success', "Farmer \"{$farmer->full_name}\" approved.");
    }

    public function reject(Request $request, Farmer $farmer): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->approvals->reject($farmer, $validated['reason'] ?? null);

        return redirect()
            ->route('admin.farmers.pending')
            ->with('success', "Farmer \"{$farmer->full_name}\" rejected.");
    }

    public function messages(Request $request): View
    {
        $status = $request->input('status');

        $messages = FarmerMessage::query()
            ->with(['farmer', 'hive'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $statuses = ['sent', 'read', 'resolved'];

        return view('admin.apiary-management.farmers.messages', compact('messages', 'statuses'));
    }

    public function showMessage(FarmerMessage $farmerMessage): View
    {
        $farmerMessage->load(['farmer', 'hive']);

        if ($farmerMessage->status === 'sent') {
            $farmerMessage->update(['status' => 'read']);
        }

        return view('admin.apiary-management.farmers.show-message', compact('farmerMessage'));
    }

    public function resolveMessage(FarmerMessage $farmerMessage): RedirectResponse
    {
        $farmerMessage->update(['status' => 'resolved']);

        return redirect()
            ->route('admin.farmers.messages')
            ->with('success', 'Message marked as resolved.');
    }
}

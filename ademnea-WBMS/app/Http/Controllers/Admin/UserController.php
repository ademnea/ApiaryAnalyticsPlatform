<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display paginated, searchable, filterable user list (REQ 9).
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        // Search by name or email (REQ 9.3)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role (REQ 9.4) — join via Spatie pivot tables
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Filter by status (REQ 9.5)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Order by newest first, paginate 25 per page (REQ 9.2), preserve query string (REQ 9.7)
        $users = $query->orderBy('created_at', 'desc')
                       ->paginate(25)
                       ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show user creation form (REQ 6.1).
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user (REQ 6.2–6.5).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            // unique rule scoped to non-deleted rows only (users uses SoftDeletes)
            'email'    => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
        ]);

        $plainPassword = $validated['password'];

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($plainPassword),
            'status'    => 'active',
            'is_active' => true,
        ]);

        $user->syncRoles($validated['roles']);

        // Dispatch welcome email (REQ 6.4); log + warn on failure (REQ 6.5)
        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $plainPassword));
        } catch (\Throwable $e) {
            Log::warning("WelcomeUserMail failed for user [{$user->id}]: " . $e->getMessage());

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.')
                ->with('warning', 'Welcome email could not be sent. Please notify the user manually.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Show user edit form (REQ 7.1, 13.1).
     */
    public function edit(int $id): View
    {
        $user  = User::findOrFail($id);
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update user details (REQ 7.2–7.6, 13.2–13.4).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Self-edit guards (REQ 7.5, 7.6)
        if ($id === auth()->id()) {
            $currentRoles = $user->getRoleNames()->toArray();
            $incomingRoles = $request->input('roles', []);
            sort($currentRoles);
            sort($incomingRoles);
            if ($incomingRoles !== $currentRoles) {
                return back()->withErrors(['roles' => 'You cannot modify your own roles via the admin UI.']);
            }
            if ($request->filled('status') && $request->input('status') !== $user->status) {
                return back()->withErrors(['status' => 'You cannot modify your own account status via the admin UI.']);
            }
        }

        $rules = [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];

        // Password is optional on edit (REQ 7.3, 7.4)
        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8'];
        }

        $validated = $request->validate($rules);

        // Non-super-admin cannot assign super-admin role (REQ 13.4)
        if (in_array('super-admin', $validated['roles'] ?? []) && ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only a super-admin can assign the super-admin role.');
        }

        $payload = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->input('password'));
        }

        $user->update($payload);
        $user->syncRoles($validated['roles']);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Activate a user account (REQ 8.1).
     */
    public function activate(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active', 'is_active' => true]);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been activated.");
    }

    /**
     * Suspend a user account (REQ 8.2–8.4).
     */
    public function suspend(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Cannot suspend own account (REQ 8.4)
        if ($id === auth()->id()) {
            return back()->withErrors(['suspend' => 'You cannot suspend your own account.']);
        }

        // Cannot suspend last super-admin (REQ 8.3)
        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return back()->withErrors(['suspend' => 'The last super-admin account cannot be suspended.']);
        }

        $user->update(['status' => 'suspended', 'is_active' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been suspended.");
    }

    /**
     * Soft-delete a user account (REQ 8.5–8.7).
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Cannot delete own account (REQ 8.7)
        if ($id === auth()->id()) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        // Cannot delete last super-admin (REQ 8.6)
        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return back()->withErrors(['delete' => 'The last super-admin account cannot be deleted.']);
        }

        $user->delete(); // SoftDeletes

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been deleted.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * UserController
 *
 * Thin HTTP layer — handles only request validation, calling UserService,
 * and returning responses/redirects.
 *
 * All business logic lives in App\Services\Admin\UserService.
 *
 * REQ-F-UADM-01 to 04 — User lifecycle management
 */
class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Display paginated, searchable, filterable user list (REQ-F-UADM-04).
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate(25)
                       ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show user creation form (REQ-F-UADM-01).
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user (REQ-F-UADM-01).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
        ]);

        ['user' => $user, 'emailFailed' => $emailFailed] = $this->userService->createUser($validated);

        if ($emailFailed) {
            return redirect()->route('admin.users.index')
                ->with('success', "User {$user->name} created successfully.")
                ->with('warning', 'Welcome email could not be sent. Please notify the user manually.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully. Welcome email sent.");
    }

    /**
     * Show user edit form (REQ-F-UADM-02).
     */
    public function edit(int $id): View
    {
        $user  = User::findOrFail($id);
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update user details (REQ-F-UADM-02).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Self-edit guard — delegate to service
        $selfEditError = $this->userService->validateSelfEdit(
            $user,
            $request->input('roles', []),
            auth()->id()
        );

        if ($selfEditError) {
            return back()->withErrors(['roles' => $selfEditError]);
        }

        $rules = [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'roles'   => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8'];
        }

        $validated = $request->validate($rules);

        // Super-admin assignment guard — delegate to service
        if (! $this->userService->canAssignSuperAdmin($validated['roles'], auth()->user())) {
            abort(403, 'Only a super-admin can assign the super-admin role.');
        }

        $this->userService->updateUser(
            $user,
            $validated,
            $request->filled('password') ? $request->input('password') : null
        );

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Activate a user account (REQ-F-UADM-03).
     */
    public function activate(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->userService->activateUser($user);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been activated.");
    }

    /**
     * Suspend a user account (REQ-F-UADM-03).
     */
    public function suspend(int $id): RedirectResponse
    {
        $user  = User::findOrFail($id);
        $error = $this->userService->suspendUser($user, auth()->id());

        if ($error) {
            return back()->withErrors(['suspend' => $error]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been suspended.");
    }

    /**
     * Soft-delete a user account (REQ-F-UADM-03).
     */
    public function destroy(int $id): RedirectResponse
    {
        $user  = User::findOrFail($id);
        $error = $this->userService->deleteUser($user, auth()->id());

        if ($error) {
            return back()->withErrors(['delete' => $error]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been deleted.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RoleController
 *
 * Manages Spatie roles and permission assignments.
 * All routes protected by 'permission:manage-roles' middleware at route-group level.
 *
 * REQ 10 — Role CRUD
 * REQ 11 — Permission assignment via checklist
 * REQ 12 — Permissions are seeder-defined only (no UI create/delete)
 */
class RoleController extends Controller
{
    /** System permissions defined in SuperAdminSeeder — the authoritative list. */
    private array $systemPermissions = [
        // User & access management
        'manage-users',
        'manage-roles',

        // Apiary & hive management
        'manage-apiaries',
        'manage-hives',
        'manage-iot-devices',
        'view-hive-data',

        // Farmer management
        'manage-farmers',
        'approve-farmer-registrations',

        // Monitoring & anomaly
        'view-monitoring-dashboard',
        'view-device-fleet',
        'view-anomaly-analytics',

        // Content management
        'manage-newsletter',
        'manage-publications',
        'manage-events',
        'manage-gallery',
        'manage-scholarship',
        'manage-work-packages',
        'manage-team-profiles',

        // Communication
        'manage-feedback',
        'manage-farmer-messages',

        // Reports
        'generate-reports',
    ];

    /** Permission categories for grouped display in the permissions view. */
    private array $permissionGroups = [
        'User & Access Management' => [
            'manage-users',
            'manage-roles',
        ],
        'Apiary & Hive Management' => [
            'manage-apiaries',
            'manage-hives',
            'manage-iot-devices',
            'view-hive-data',
        ],
        'Farmer Management' => [
            'manage-farmers',
            'approve-farmer-registrations',
        ],
        'Monitoring & Anomaly' => [
            'view-monitoring-dashboard',
            'view-device-fleet',
            'view-anomaly-analytics',
        ],
        'Content Management' => [
            'manage-newsletter',
            'manage-publications',
            'manage-events',
            'manage-gallery',
            'manage-scholarship',
            'manage-work-packages',
            'manage-team-profiles',
        ],
        'Communication' => [
            'manage-feedback',
            'manage-farmer-messages',
        ],
        'Reports' => [
            'generate-reports',
        ],
    ];

    /** List all roles (REQ 10.1). */
    public function index(): View
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /** Show the create-role form. */
    public function create(): View
    {
        return view('admin.roles.create');
    }

    /** Store a new role (REQ 10.2, 10.3). */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        // Block creating a role named 'super-admin' via the UI (REQ 10.7)
        if (strtolower($request->name) === 'super-admin') {
            return back()->withErrors(['name' => 'The super-admin role cannot be created via the admin UI.']);
        }

        Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role \"{$request->name}\" created successfully.");
    }

    /** Show the rename form (REQ 10.4). */
    public function edit(int $id): View
    {
        $role = Role::findOrFail($id);
        return view('admin.roles.edit', compact('role'));
    }

    /** Rename a role (REQ 10.4). */
    public function rename(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $id],
        ]);

        $role->update(['name' => $request->name]);

        return redirect()->route('admin.roles.index')
            ->with('success', "Role renamed to \"{$request->name}\" successfully.");
    }

    /** Delete a role (REQ 10.5, 10.6, 10.7). */
    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        // Cannot delete super-admin role (REQ 10.7)
        if ($role->name === 'super-admin') {
            return back()->withErrors(['delete' => 'The super-admin role cannot be deleted.']);
        }

        // Cannot delete if users are assigned (REQ 10.6)
        if ($role->users()->count() > 0) {
            return back()->withErrors(['delete' => "The \"{$role->name}\" role cannot be deleted while users are assigned to it."]);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role \"{$role->name}\" deleted successfully.");
    }

    /** Show permission assignment form (REQ 11.1, 11.2). */
    public function showPermissions(int $id): View
    {
        $role        = Role::findOrFail($id);
        $permissions = Permission::whereIn('name', $this->systemPermissions)
                                 ->where('guard_name', 'web')
                                 ->orderBy('name')
                                 ->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        $permissionGroups = $this->permissionGroups;

        return view('admin.roles.permissions', compact('role', 'permissions', 'rolePermissions', 'permissionGroups'));
    }

    /** Sync permissions on a role (REQ 11.3, 11.4). */
    public function syncPermissions(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        // Cannot modify super-admin permissions (REQ 11.4)
        if ($role->name === 'super-admin') {
            return back()->withErrors(['permissions' => 'The super-admin role permissions cannot be modified via the admin UI.']);
        }

        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('admin.roles.permissions', $id)
            ->with('success', "Permissions for \"{$role->name}\" updated successfully.");
    }
}

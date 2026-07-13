@extends('layouts.app')

@section('title', 'Role Management')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-shield-lock me-2"></i>Role Management</h1>
    <p class="breadcrumb">Create and manage platform roles and their permissions.</p>
</div>

<div class="page-content">

    {{-- Flash: success --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Inline field errors (e.g. delete guards) --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ---- Roles table ---- --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-table me-2"></i>Roles
                <span class="badge bg-secondary ms-1" style="font-weight:500;">{{ $roles->count() }}</span>
            </span>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-honey btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Create Role
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th class="text-center">User Count</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            {{-- Role Name --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($role->name === 'super-admin')
                                        <i class="bi bi-shield-fill-check text-warning" title="Protected system role"></i>
                                    @else
                                        <i class="bi bi-shield text-muted"></i>
                                    @endif
                                    <span class="fw-medium">{{ $role->name }}</span>
                                    @if ($role->name === 'super-admin')
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.7rem;">system</span>
                                    @endif
                                </div>
                            </td>

                            {{-- User Count --}}
                            <td class="text-center">
                                <span class="badge rounded-pill"
                                      style="background:var(--clr-forest-pale);color:var(--clr-forest);font-weight:500;min-width:2rem;">
                                    {{ $role->users_count }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="text-end" style="white-space:nowrap;">

                                {{-- Permissions --}}
                                <a href="{{ route('admin.roles.permissions', $role->id) }}"
                                   class="btn btn-outline-primary btn-sm me-1"
                                   title="Manage permissions">
                                    <i class="bi bi-key"></i>
                                    <span class="d-none d-md-inline ms-1">Permissions</span>
                                </a>

                                {{-- Rename --}}
                                <a href="{{ route('admin.roles.edit', $role->id) }}"
                                   class="btn btn-outline-secondary btn-sm me-1"
                                   title="Rename role">
                                    <i class="bi bi-pencil"></i>
                                    <span class="d-none d-md-inline ms-1">Rename</span>
                                </a>

                                {{-- Delete — hidden for super-admin --}}
                                @if ($role->name !== 'super-admin')
                                    <form method="POST"
                                          action="{{ route('admin.roles.delete', $role->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete role \"{{ addslashes($role->name) }}\"? This cannot be undone.')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Delete role">
                                            <i class="bi bi-trash3"></i>
                                            <span class="d-none d-md-inline ms-1">Delete</span>
                                        </button>
                                    </form>
                                @else
                                    {{-- Lock icon placeholder for super-admin --}}
                                    <span class="btn btn-sm btn-outline-secondary disabled"
                                          title="The super-admin role cannot be deleted">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                @endif

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-shield-x fs-4 d-block mb-1"></i>
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- .page-content --}}

@endsection

@extends('layouts.app')

@section('title', 'Manage Permissions — ' . $role->name)

@section('content')

<div class="page-header">
    <h1>
        <i class="bi bi-key me-2"></i>Manage Permissions
        <span class="text-muted fw-normal">—</span>
        <span style="color:var(--clr-forest);">{{ $role->name }}</span>
    </h1>
    <p class="breadcrumb">
        <a href="{{ route('admin.roles.index') }}">Role Management</a>
        <span class="mx-1 text-muted">/</span>Permissions
    </p>
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

    @php $isSuperAdmin = $role->name === 'super-admin'; @endphp

    {{-- Super-admin read-only notice --}}
    @if ($isSuperAdmin)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-shield-fill-check flex-shrink-0 fs-5"></i>
            <div>
                <strong>Super-Admin Role</strong> — This role always has all permissions.
                Permissions cannot be modified via the admin UI to protect platform integrity.
            </div>
        </div>
    @endif

    {{-- Permission form (or read-only for super-admin) --}}
    <form method="POST"
          action="{{ route('admin.roles.permissions.sync', $role->id) }}"
          id="permissions-form">
        @csrf

        <div class="row g-3">

            @foreach ($permissionGroups as $groupName => $groupPermissions)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header py-2" style="background:var(--clr-forest-pale);">
                            <span class="fw-semibold" style="color:var(--clr-forest);font-size:0.85rem;">
                                <i class="bi bi-folder2 me-1"></i>{{ $groupName }}
                            </span>
                        </div>
                        <div class="card-body py-2">
                            @foreach ($groupPermissions as $permName)
                                @php
                                    $checked  = in_array($permName, $rolePermissions);
                                    $exists   = $permissions->contains('name', $permName);
                                @endphp
                                @if ($exists)
                                    <div class="form-check py-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permissions[]"
                                            id="perm_{{ Str::slug($permName) }}"
                                            value="{{ $permName }}"
                                            {{ $isSuperAdmin || $checked ? 'checked' : '' }}
                                            {{ $isSuperAdmin ? 'disabled' : '' }}
                                        >
                                        <label class="form-check-label" for="perm_{{ Str::slug($permName) }}"
                                               style="font-size:0.875rem;">
                                            {{ $permName }}
                                        </label>
                                    </div>
                                @else
                                    {{-- Permission not yet seeded — show greyed out --}}
                                    <div class="form-check py-1 opacity-50">
                                        <input class="form-check-input" type="checkbox" disabled>
                                        <label class="form-check-label" style="font-size:0.875rem;">
                                            {{ $permName }}
                                            <small class="text-muted">(not seeded)</small>
                                        </label>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

        </div>{{-- .row --}}

        {{-- Action buttons --}}
        <div class="d-flex gap-2 mt-4">
            @if (! $isSuperAdmin)
                <button type="submit" class="btn btn-honey">
                    <i class="bi bi-check-lg me-1"></i>Save Permissions
                </button>
            @else
                <button type="button" class="btn btn-honey" disabled>
                    <i class="bi bi-lock-fill me-1"></i>Save Permissions
                </button>
            @endif
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Roles
            </a>
        </div>

    </form>

</div>{{-- .page-content --}}

@endsection

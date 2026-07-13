@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>User Management</h1>
    <p class="breadcrumb">Manage platform accounts, roles, and account status.</p>
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

    {{-- Flash: warning (e.g. email send failure) --}}
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Inline field errors (e.g. suspend / delete guards) --}}
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

    {{-- ---- Filter bar ---- --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-12 col-md-5">
                    <label for="search" class="form-label fw-semibold mb-1" style="font-size:0.78rem;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            placeholder="Name or email…"
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>

                {{-- Role filter --}}
                <div class="col-6 col-md-2">
                    <label for="role" class="form-label fw-semibold mb-1" style="font-size:0.78rem;">Role</label>
                    <select id="role" name="role" class="form-select form-select-sm">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status filter --}}
                <div class="col-6 col-md-2">
                    <label for="status" class="form-label fw-semibold mb-1" style="font-size:0.78rem;">Status</label>
                    <select id="status" name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-honey btn-sm ms-auto">
                        <i class="bi bi-person-plus me-1"></i>Add User
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- ---- Users table ---- --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table me-2"></i>Users
                <span class="badge bg-secondary ms-1" style="font-weight:500;">{{ $users->total() }}</span>
            </span>
            <small class="text-muted fw-normal">
                Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            {{-- Name --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;background:var(--clr-forest-pale);border-radius:50%;
                                                display:flex;align-items:center;justify-content:center;
                                                font-size:0.75rem;font-weight:600;color:var(--clr-forest);flex-shrink:0;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span class="fw-medium">{{ $u->name }}</span>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="text-muted">{{ $u->email }}</td>

                            {{-- Role badge --}}
                            <td>
                                @foreach ($u->roles as $r)
                                    <span class="badge rounded-pill"
                                          style="background:var(--clr-forest-pale);color:var(--clr-forest);font-weight:500;">
                                        {{ $r->name }}
                                    </span>
                                @endforeach
                            </td>

                            {{-- Status badge --}}
                            <td>
                                @php
                                    $badgeMap = [
                                        'active'    => 'badge-active',
                                        'pending'   => 'badge-pending',
                                        'suspended' => 'badge-offline',
                                        'rejected'  => 'badge-offline',
                                    ];
                                    $cls = $badgeMap[$u->status] ?? 'badge-pending';
                                @endphp
                                <span class="badge rounded-pill {{ $cls }}">
                                    {{ ucfirst($u->status) }}
                                </span>
                            </td>

                            {{-- Created --}}
                            <td class="text-muted" style="white-space:nowrap;">
                                {{ $u->created_at->format('d M Y') }}
                            </td>

                            {{-- Actions --}}
                            <td class="text-end" style="white-space:nowrap;">

                                {{-- Edit --}}
                                <a href="{{ route('admin.users.edit', $u->id) }}"
                                   class="btn btn-outline-secondary btn-sm me-1"
                                   title="Edit user">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Activate (only when not active) --}}
                                @if ($u->status !== 'active')
                                    <form method="POST"
                                          action="{{ route('admin.users.activate', $u->id) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-success me-1"
                                                title="Activate account">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Suspend (only when active and not own account) --}}
                                @if ($u->status === 'active' && $u->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('admin.users.suspend', $u->id) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-warning me-1"
                                                title="Suspend account">
                                            <i class="bi bi-pause-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Delete (not own account) --}}
                                @if ($u->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('admin.users.delete', $u->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete {{ addslashes($u->name) }}? This cannot be undone.')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Delete account">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                @endif

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-person-x fs-4 d-block mb-1"></i>
                                No users found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="card-footer d-flex justify-content-center py-2">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>{{-- .page-content --}}

@endsection

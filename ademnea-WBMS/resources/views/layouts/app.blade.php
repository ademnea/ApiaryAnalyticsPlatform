<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: true }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — AdEMNEA Beehive Monitoring</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Google Fonts: Inter (body) + Space Grotesk (headings) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ================================================================
           ADEMNEA COLOR SYSTEM
           Rationale:
           - Forest Green (#1B4332) primary: bees live in green ecosystems;
             green signals "healthy" in monitoring contexts (traffic-light UX).
             Dark forest tone reads professional against dark sidebars.
           - Amber/Gold (#D4A017): the color of honey — the product, the
             goal, the reward. Used as the accent/highlight to keep bees
             and agriculture front-of-mind without being decorative.
           - Near-black (#0D1B12): sidebar/header — a deep forest dark that
             unifies the green family without the flatness of pure black.
           - Warm white (#F8FAF7): content area background — a very faint
             green tint that prevents stark contrast fatigue on data-heavy
             monitoring pages.
           - Alert red/amber/green follow Bootstrap but are tinted toward
             the palette for cohesion.
        ================================================================ */

        :root {
            --clr-forest:       #1B4332;
            --clr-forest-mid:   #2D6A4F;
            --clr-forest-light: #40916C;
            --clr-forest-pale:  #D8F3DC;
            --clr-honey:        #D4A017;
            --clr-honey-light:  #F8C93A;
            --clr-dark:         #0D1B12;
            --clr-sidebar-bg:   #0F2519;
            --clr-sidebar-hover:#1B4332;
            --clr-sidebar-active:#2D6A4F;
            --clr-sidebar-text: #B7D5C4;
            --clr-sidebar-icon: #74B895;
            --clr-canvas:       #F8FAF7;
            --clr-card:         #FFFFFF;
            --clr-border:       #E0EDE5;
            --clr-muted:        #6B7F74;

            --font-display: 'Space Grotesk', sans-serif;
            --font-body:    'Inter', sans-serif;

            --sidebar-width: 260px;
            --topbar-height: 56px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            font-size: 0.875rem;
            background-color: var(--clr-canvas);
            color: #1a2e1f;
            line-height: 1.6;
        }

        /* ---- TOPBAR -------------------------------------------------- */
        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--topbar-height);
            background: var(--clr-dark);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            z-index: 1040;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            width: var(--sidebar-width);
            flex-shrink: 0;
        }

        .topbar-brand .brand-icon {
            width: 30px;
            height: 30px;
            background: var(--clr-forest-mid);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .topbar-brand .brand-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .topbar-brand .brand-sub {
            font-size: 0.62rem;
            color: var(--clr-honey);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            display: block;
            line-height: 1;
            margin-top: 1px;
        }

        .topbar-toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.1rem;
            padding: 0.25rem 0.5rem;
            margin-right: 0.75rem;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .topbar-toggle:hover { background: rgba(255,255,255,0.08); }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .topbar-icon-btn {
            background: none;
            border: none;
            color: #adc8b7;
            font-size: 1rem;
            padding: 0.4rem 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: color 0.15s, background 0.15s;
        }
        .topbar-icon-btn:hover { color: #fff; background: rgba(255,255,255,0.08); }

        .topbar-badge {
            position: absolute;
            top: 4px; right: 4px;
            width: 8px; height: 8px;
            background: var(--clr-honey);
            border-radius: 50%;
            border: 1px solid var(--clr-dark);
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .topbar-user:hover { background: rgba(255,255,255,0.08); }

        .topbar-avatar {
            width: 30px; height: 30px;
            background: var(--clr-forest-mid);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
        }

        .topbar-username {
            font-size: 0.8rem;
            font-weight: 500;
            color: #d0e8d9;
        }

        /* ---- SIDEBAR -------------------------------------------------- */
        .sidebar {
            position: fixed;
            top: var(--topbar-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--clr-sidebar-bg);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1030;
            transition: transform 0.25s ease, width 0.25s ease;
            scrollbar-width: thin;
            scrollbar-color: #2D6A4F transparent;
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #2D6A4F; border-radius: 2px; }

        .sidebar[x-show] { display: block !important; } /* override Alpine default */

        /* Sidebar section headers */
        .sidebar-section {
            padding: 1.25rem 1rem 0.35rem;
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #4a7a5d;
        }

        /* Nav item (no dropdown) */
        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.5rem 1rem;
            color: var(--clr-sidebar-text);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 400;
            border-radius: 0;
            transition: background 0.15s, color 0.15s;
            border-left: 3px solid transparent;
        }
        .nav-item-link:hover {
            background: var(--clr-sidebar-hover);
            color: #fff;
            border-left-color: var(--clr-forest-light);
        }
        .nav-item-link.active {
            background: var(--clr-sidebar-active);
            color: #fff;
            border-left-color: var(--clr-honey);
            font-weight: 500;
        }
        .nav-item-link i {
            font-size: 0.95rem;
            color: var(--clr-sidebar-icon);
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }
        .nav-item-link.active i { color: var(--clr-honey); }

        /* Dropdown trigger */
        .nav-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.5rem 1rem;
            color: var(--clr-sidebar-text);
            font-size: 0.82rem;
            font-weight: 400;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s;
            user-select: none;
        }
        .nav-dropdown-trigger:hover {
            background: var(--clr-sidebar-hover);
            color: #fff;
            border-left-color: var(--clr-forest-light);
        }
        .nav-dropdown-trigger.open {
            background: rgba(45,106,79,0.4);
            color: #fff;
            border-left-color: var(--clr-forest-light);
        }
        .nav-dropdown-trigger i.nav-icon {
            font-size: 0.95rem;
            color: var(--clr-sidebar-icon);
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }
        .nav-dropdown-trigger.open i.nav-icon { color: var(--clr-honey); }

        .nav-dropdown-label { flex: 1; }

        .nav-chevron {
            font-size: 0.7rem;
            transition: transform 0.2s;
            color: #4a7a5d;
        }
        .nav-dropdown-trigger.open .nav-chevron { transform: rotate(90deg); }

        /* Dropdown children */
        .nav-dropdown-children {
            background: rgba(0,0,0,0.15);
            border-left: 2px solid #1B4332;
            margin-left: 1.5rem;
        }
        .nav-dropdown-children a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.38rem 0.9rem 0.38rem 0.75rem;
            color: #8db8a0;
            font-size: 0.79rem;
            text-decoration: none;
            transition: color 0.15s, background 0.15s;
        }
        .nav-dropdown-children a:hover { color: #fff; background: rgba(45,106,79,0.3); }
        .nav-dropdown-children a.active { color: var(--clr-honey); font-weight: 500; }
        .nav-dropdown-children a i {
            font-size: 0.8rem;
            width: 14px;
            text-align: center;
            opacity: 0.7;
        }

        /* ---- MAIN CONTENT --------------------------------------------- */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.25s ease;
        }
        .main-wrapper.sidebar-collapsed { margin-left: 0; }

        .page-header {
            padding: 1.25rem 1.5rem 0;
        }
        .page-header h1 {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 600;
            color: #1a2e1f;
            margin: 0 0 0.1rem;
        }
        .breadcrumb {
            font-size: 0.75rem;
            color: var(--clr-muted);
            margin: 0;
        }
        .breadcrumb-item a { color: var(--clr-forest-mid); text-decoration: none; }
        .breadcrumb-item a:hover { text-decoration: underline; }

        .page-content {
            padding: 1.25rem 1.5rem 2rem;
        }

        /* ---- CARDS ---------------------------------------------------- */
        .card {
            border: 1px solid var(--clr-border);
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--clr-border);
            border-radius: 10px 10px 0 0 !important;
            padding: 0.85rem 1.25rem;
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a2e1f;
        }

        /* Stat cards */
        .stat-card {
            background: #fff;
            border: 1px solid var(--clr-border);
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(27,67,50,0.1); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-icon.green  { background: var(--clr-forest-pale); color: var(--clr-forest); }
        .stat-icon.honey  { background: #FFF3CD; color: #856404; }
        .stat-icon.blue   { background: #D0E4FF; color: #0057b8; }
        .stat-icon.red    { background: #FFE0E0; color: #b30000; }

        .stat-value {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2e1f;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.75rem;
            color: var(--clr-muted);
            margin-top: 0.15rem;
        }

        /* ---- ALERTS --------------------------------------------------- */
        .alert-ademnea {
            border-left: 4px solid var(--clr-honey);
            background: #FFFBF0;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            font-size: 0.82rem;
        }

        /* ---- TABLES --------------------------------------------------- */
        .table th {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--clr-muted);
            border-bottom: 2px solid var(--clr-border);
            background: #FAFCFA;
        }
        .table td { vertical-align: middle; font-size: 0.82rem; }

        /* ---- STATUS BADGES -------------------------------------------- */
        .badge-active   { background: #D8F3DC; color: #1B4332; }
        .badge-pending  { background: #FFF3CD; color: #664D03; }
        .badge-offline  { background: #FFE0E0; color: #7F1D1D; }
        .badge-warning  { background: #FFF3CD; color: #664D03; }

        /* ---- BUTTONS -------------------------------------------------- */
        .btn-primary {
            background: var(--clr-forest-mid);
            border-color: var(--clr-forest-mid);
            font-size: 0.82rem;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: var(--clr-forest);
            border-color: var(--clr-forest);
        }
        .btn-honey {
            background: var(--clr-honey);
            border-color: var(--clr-honey);
            color: #1a1a1a;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .btn-honey:hover {
            background: var(--clr-honey-light);
            border-color: var(--clr-honey-light);
            color: #1a1a1a;
        }
        .btn-outline-forest {
            border-color: var(--clr-forest-mid);
            color: var(--clr-forest-mid);
            font-size: 0.82rem;
        }
        .btn-outline-forest:hover {
            background: var(--clr-forest-mid);
            color: #fff;
        }

        /* ---- FLASH MESSAGES ------------------------------------------- */
        .flash-message {
            position: fixed;
            top: calc(var(--topbar-height) + 12px);
            right: 1.25rem;
            z-index: 9999;
            min-width: 280px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        /* ---- FOOTER --------------------------------------------------- */
        .admin-footer {
            border-top: 1px solid var(--clr-border);
            padding: 0.75rem 1.5rem;
            font-size: 0.72rem;
            color: var(--clr-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-footer .honey-dot {
            display: inline-block;
            width: 8px; height: 8px;
            background: var(--clr-honey);
            border-radius: 50%;
            margin-right: 0.4rem;
        }

        /* ---- RESPONSIVE ----------------------------------------------- */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .topbar-brand { width: auto; }
            .topbar-brand .brand-sub { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ============================================================ TOPBAR == --}}
<header class="topbar">

    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="topbar-brand">
        <div class="brand-icon">🐝</div>
        <div>
            <div class="brand-name">AdEMNEA</div>
            <span class="brand-sub">Beehive Monitor</span>
        </div>
    </a>

    {{-- Sidebar toggle --}}
    <button class="topbar-toggle" @click="sidebarOpen = !sidebarOpen" title="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    {{-- Search --}}
    <div class="d-none d-md-flex align-items-center ms-2"
         style="background:rgba(255,255,255,0.07);border-radius:8px;padding:0.3rem 0.7rem;gap:0.4rem;flex:0 1 260px;">
        <i class="bi bi-search" style="color:#4a7a5d;font-size:0.8rem;"></i>
        <input type="text" placeholder="Search modules…"
               style="background:none;border:none;outline:none;color:#c8e6d5;font-size:0.8rem;width:100%;"
               hx-get="/admin/search"
               hx-trigger="keyup changed delay:400ms"
               hx-target="#search-results"
               name="q">
    </div>

    {{-- Right controls --}}
    <div class="topbar-right">

        {{-- Alerts bell --}}
        <button class="topbar-icon-btn" title="System alerts">
            <i class="bi bi-bell"></i>
            @if(isset($unreadAlerts) && $unreadAlerts > 0)
                <span class="topbar-badge"></span>
            @endif
        </button>

        {{-- Feedback badge --}}
        <button class="topbar-icon-btn" title="Pending feedback">
            <i class="bi bi-chat-dots"></i>
        </button>

        {{-- Divider --}}
        <div style="width:1px;height:24px;background:rgba(255,255,255,0.1);margin:0 0.25rem;"></div>

        {{-- User dropdown (Bootstrap) --}}
        <div class="dropdown">
            <div class="topbar-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="list-style:none;">
                <div class="topbar-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <span class="topbar-username d-none d-sm-inline">
                    {{ Str::limit(auth()->user()->name ?? 'Admin', 14) }}
                </span>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                style="min-width:180px;font-size:0.82rem;border-color:var(--clr-border);">
                <li>
                    <a class="dropdown-item" href="{{ route('admin.profile') }}">
                        <i class="bi bi-person me-2 text-muted"></i>My Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit">
                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>
</header>

{{-- ============================================================ SIDEBAR == --}}
<nav class="sidebar" x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="transform -translate-x-full" x-transition:enter-end="transform translate-x-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-x-0"
     x-transition:leave-end="transform -translate-x-full">

    {{-- ---- SECTION: OVERVIEW ---- --}}
    <div class="sidebar-section">Overview</div>

    <a href="{{ route('admin.dashboard') }}"
       class="nav-item-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </a>

    {{-- ---- SECTION: APIARIES & HIVES ---- --}}
    <div class="sidebar-section">Apiaries & Hives</div>

    {{-- Apiary Management --}}
    <div x-data="{ open: {{ request()->routeIs('admin.apiaries.*') || request()->routeIs('admin.hives.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-building nav-icon"></i>
            <span class="nav-dropdown-label">Apiary Management</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.apiaries.index') }}"
               class="{{ request()->routeIs('admin.apiaries.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Apiaries
            </a>
            <a href="{{ route('admin.apiaries.create') }}"
               class="{{ request()->routeIs('admin.apiaries.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Register Apiary
            </a>
        </div>
    </div>

    {{-- Hive Management --}}
    <div x-data="{ open: {{ request()->routeIs('admin.hives.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-hexagon nav-icon"></i>
            <span class="nav-dropdown-label">Hive Management</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.hives.index') }}"
               class="{{ request()->routeIs('admin.hives.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Hives
            </a>
             <a href="{{ route('admin.hives.create') }}"
                class="{{ request()->routeIs('admin.hives.create*') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Register Hive
            </a>
            <a href="{{ route('admin.hives.map') }}"
               class="{{ request()->routeIs('admin.hives.map') ? 'active' : '' }}">
                <i class="bi bi-geo-alt"></i> Hive Map
            </a>
            <a href="{{ route('admin.inspections.index') }}"
               class="{{ request()->routeIs('admin.inspections.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Inspections
            </a>
            <a href="{{ route('admin.harvests.index') }}"
               class="{{ request()->routeIs('admin.harvests.*') ? 'active' : '' }}">
                <i class="bi bi-droplet"></i> Harvest Records
            </a>
            <a href="{{ route('admin.alert-thresholds.index') }}"
               class="{{ request()->routeIs('admin.alert-thresholds.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Alert Thresholds
            </a>
        </div>
    </div>

    {{-- ---- SECTION: IOT & MONITORING ---- --}}
    <div class="sidebar-section">IoT & Monitoring</div>

    {{-- IoT Devices --}}
    <div x-data="{ open: {{ request()->routeIs('admin.devices.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-cpu nav-icon"></i>
            <span class="nav-dropdown-label">IoT Devices</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.devices.index') }}"
               class="{{ request()->routeIs('admin.devices.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> Device Registry
            </a>
            <a href="{{ route('admin.devices.create') }}"
               class="{{ request()->routeIs('admin.devices.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Register Device
            </a>
            <a href="{{ route('admin.devices.fleet') }}"
               class="{{ request()->routeIs('admin.devices.fleet') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap"></i> Fleet Health
            </a>
        </div>
    </div>

    {{-- Sensor Monitoring --}}
    <div x-data="{ open: {{ request()->routeIs('admin.monitoring.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-activity nav-icon"></i>
            <span class="nav-dropdown-label">Sensor Monitoring</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.monitoring.temperature') }}"
               class="{{ request()->routeIs('admin.monitoring.temperature') ? 'active' : '' }}">
                <i class="bi bi-thermometer-half"></i> Temperature
            </a>
            <a href="{{ route('admin.monitoring.humidity') }}"
               class="{{ request()->routeIs('admin.monitoring.humidity') ? 'active' : '' }}">
                <i class="bi bi-droplet-half"></i> Humidity
            </a>
            <a href="{{ route('admin.monitoring.weight') }}"
               class="{{ request()->routeIs('admin.monitoring.weight') ? 'active' : '' }}">
                <i class="bi bi-speedometer"></i> Hive Weight
            </a>
            <a href="{{ route('admin.monitoring.co2') }}"
               class="{{ request()->routeIs('admin.monitoring.co2') ? 'active' : '' }}">
                <i class="bi bi-wind"></i> CO₂ Levels
            </a>
            <a href="{{ route('admin.monitoring.audio') }}"
               class="{{ request()->routeIs('admin.monitoring.audio') ? 'active' : '' }}">
                <i class="bi bi-mic"></i> Audio
            </a>
            <a href="{{ route('admin.monitoring.video') }}"
               class="{{ request()->routeIs('admin.monitoring.video') ? 'active' : '' }}">
                <i class="bi bi-camera-video"></i> Video
            </a>
            <a href="{{ route('admin.monitoring.photos') }}"
               class="{{ request()->routeIs('admin.monitoring.photos') ? 'active' : '' }}">
                <i class="bi bi-images"></i> Photos
            </a>
        </div>
    </div>

    {{-- Anomaly Detection --}}
    <div x-data="{ open: {{ request()->routeIs('admin.anomaly.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-graph-up-arrow nav-icon"></i>
            <span class="nav-dropdown-label">Anomaly Detection</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.anomaly.dashboard') }}"
               class="{{ request()->routeIs('admin.anomaly.dashboard') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i> Anomaly Dashboard
            </a>
            <a href="{{ route('admin.anomaly.analytics') }}"
               class="{{ request()->routeIs('admin.anomaly.analytics') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Analytics
            </a>
            <a href="{{ route('admin.anomaly.models') }}"
               class="{{ request()->routeIs('admin.anomaly.models') ? 'active' : '' }}">
                <i class="bi bi-robot"></i> ML Models
            </a>
        </div>
    </div>

    {{-- Reports --}}
    <div x-data="{ open: {{ request()->routeIs('admin.reports.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            <span class="nav-dropdown-label">Reports</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.reports.health') }}">
                <i class="bi bi-heart-pulse"></i> Colony Health
            </a>
            <a href="{{ route('admin.reports.production') }}">
                <i class="bi bi-droplet"></i> Honey Production
            </a>
            <a href="{{ route('admin.reports.sensor-trends') }}">
                <i class="bi bi-graph-up"></i> Sensor Trends
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    <a href="{{ route('admin.alerts.index') }}"
       class="nav-item-link {{ request()->routeIs('admin.alerts.*') ? 'active' : '' }}">
        <i class="bi bi-bell-fill"></i>
        System Alerts
        @if(isset($activeAlertsCount) && $activeAlertsCount > 0)
            <span class="badge ms-auto" style="background:var(--clr-honey);color:#1a1a1a;font-size:0.65rem;">
                {{ $activeAlertsCount }}
            </span>
        @endif
    </a>

    {{-- ---- SECTION: FARMERS ---- --}}
    <div class="sidebar-section">Farmers</div>

    <div x-data="{ open: {{ request()->routeIs('admin.farmers.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-people nav-icon"></i>
            <span class="nav-dropdown-label">Farmer Management</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.farmers.index') }}"
               class="{{ request()->routeIs('admin.farmers.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Farmers
            </a>
            <a href="{{ route('admin.farmers.create') }}"
               class="{{ request()->routeIs('admin.farmers.create') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Register Farmer
            </a>
            <a href="{{ route('admin.farmers.pending') }}"
               class="{{ request()->routeIs('admin.farmers.pending') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Pending Approvals
            </a>
            <a href="{{ route('admin.farmers.messages') }}"
               class="{{ request()->routeIs('admin.farmers.messages') ? 'active' : '' }}">
                <i class="bi bi-envelope"></i> Farmer Messages
            </a>
        </div>
    </div>

    {{-- ---- SECTION: WEBSITE CONTENT ---- --}}
    <div class="sidebar-section">Website Content</div>

    {{-- Newsletter --}}
    <div x-data="{ open: {{ request()->routeIs('admin.newsletter.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-newspaper nav-icon"></i>
            <span class="nav-dropdown-label">Newsletter</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.newsletter.index') }}"
               class="{{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Articles
            </a>
            <a href="{{ route('admin.newsletter.create') }}"
               class="{{ request()->routeIs('admin.newsletter.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> New Article
            </a>
        </div>
    </div>

    {{-- Publications --}}
    <div x-data="{ open: {{ request()->routeIs('admin.publications.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-journal-richtext nav-icon"></i>
            <span class="nav-dropdown-label">Publications</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.publications.index') }}"
               class="{{ request()->routeIs('admin.publications.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Publications
            </a>
            <a href="{{ route('admin.publications.create') }}"
               class="{{ request()->routeIs('admin.publications.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Add Publication
            </a>
        </div>
    </div>

    {{-- Events --}}
    <div x-data="{ open: {{ request()->routeIs('admin.events.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-calendar-event nav-icon"></i>
            <span class="nav-dropdown-label">Events</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.events.index') }}"
               class="{{ request()->routeIs('admin.events.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Events
            </a>
            <a href="{{ route('admin.events.create') }}"
               class="{{ request()->routeIs('admin.events.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Add Event
            </a>
        </div>
    </div>

    {{-- Gallery --}}
    <div x-data="{ open: {{ request()->routeIs('admin.gallery.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-images nav-icon"></i>
            <span class="nav-dropdown-label">Gallery</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.gallery.index') }}"
               class="{{ request()->routeIs('admin.gallery.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Galleries
            </a>
            <a href="{{ route('admin.gallery.create') }}"
               class="{{ request()->routeIs('admin.gallery.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Add Gallery
            </a>
        </div>
    </div>

    {{-- Scholarship --}}
    <div x-data="{ open: {{ request()->routeIs('admin.scholarship.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-mortarboard nav-icon"></i>
            <span class="nav-dropdown-label">Scholarships</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.scholarship.index') }}"
               class="{{ request()->routeIs('admin.scholarship.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Scholarships
            </a>
            <a href="{{ route('admin.scholarship.create') }}"
               class="{{ request()->routeIs('admin.scholarship.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Add Scholarship
            </a>
        </div>
    </div>

    {{-- Work Packages --}}
    <div x-data="{ open: {{ request()->routeIs('admin.workpackages.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-box-seam nav-icon"></i>
            <span class="nav-dropdown-label">Work Packages</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.workpackages.index') }}"
               class="{{ request()->routeIs('admin.workpackages.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Work Packages
            </a>
            <a href="{{ route('admin.workpackages.create') }}"
               class="{{ request()->routeIs('admin.workpackages.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Add Work Package
            </a>
        </div>
    </div>

    {{-- Team Profiles --}}
    <div x-data="{ open: {{ request()->routeIs('admin.team.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-person-badge nav-icon"></i>
            <span class="nav-dropdown-label">Team Profiles</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.team.index') }}"
               class="{{ request()->routeIs('admin.team.index') ? 'active' : '' }}">
                <i class="bi bi-list-ul"></i> All Members
            </a>
            <a href="{{ route('admin.team.create') }}"
               class="{{ request()->routeIs('admin.team.create') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Add Member
            </a>
        </div>
    </div>

    {{-- ---- SECTION: COMMUNICATION ---- --}}
    <div class="sidebar-section">Communication</div>

    <div x-data="{ open: {{ request()->routeIs('admin.feedback.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-chat-square-text nav-icon"></i>
            <span class="nav-dropdown-label">Feedback</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.feedback.index') }}"
               class="{{ request()->routeIs('admin.feedback.index') ? 'active' : '' }}">
                <i class="bi bi-inbox"></i> All Feedback
            </a>
        </div>
    </div>

    {{-- ---- SECTION: SYSTEM ---- --}}
    <div class="sidebar-section">System</div>

    {{-- User Management --}}
    <div x-data="{ open: {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'true' : 'false' }} }">
        <div class="nav-dropdown-trigger" :class="open ? 'open' : ''" @click="open = !open">
            <i class="bi bi-shield-lock nav-icon"></i>
            <span class="nav-dropdown-label">User Management</span>
            <i class="bi bi-chevron-right nav-chevron"></i>
        </div>
        <div class="nav-dropdown-children" x-show="open" x-collapse>
            <a href="{{ route('admin.users.index') }}"
               class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                <i class="bi bi-people"></i> All Users
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="{{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Add User
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="{{ request()->routeIs('admin.roles.index') ? 'active' : '' }}">
                <i class="bi bi-key"></i> Roles & Permissions
            </a>
        </div>
    </div>

    {{-- Bottom spacer --}}
    <div style="height: 2rem;"></div>

</nav>

{{-- ============================================================ MAIN == --}}
<div class="main-wrapper" :class="{ 'sidebar-collapsed': !sidebarOpen }">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flash-message alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="flash-message alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="flash-message alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Page header --}}
    <div class="page-header">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                @yield('breadcrumbs')
            </ol>
        </nav>
    </div>

    {{-- Page content --}}
    <div class="page-content">
        @yield('content')
    </div>

    {{-- Footer --}}
    <footer class="admin-footer">
        <span>
            <span class="honey-dot"></span>
            AdEMNEA Beehive Monitoring System &mdash; NORHED II / Makerere University
        </span>
        <span>v1.1 &bull; {{ date('Y') }}</span>
    </footer>

</div>

{{-- Search results dropdown (htmx target) --}}
<div id="search-results"></div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.0/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.3/dist/htmx.min.js"></script>
{{-- Chart.js for monitoring pages --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

{{-- CSRF header for htmx --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('htmx:configRequest', function (evt) {
            evt.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        });
    });
</script>

{{-- Persist sidebar scroll position across full-page navigations --}}
<script>
    (function () {
        var KEY = 'sidebarScrollTop';
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Restore immediately so there's no visible jump.
        var saved = sessionStorage.getItem(KEY);
        if (saved !== null) {
            sidebar.scrollTop = parseInt(saved, 10);
        }

        // Save whenever the user scrolls the sidebar.
        sidebar.addEventListener('scroll', function () {
            sessionStorage.setItem(KEY, sidebar.scrollTop);
        }, { passive: true });
    })();
</script>

@stack('scripts')
</body>
</html>
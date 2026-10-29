<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Civil Indent Portal')</title>

    <style>
        :root {
            /* Brand & Key Surfaces */
            --primary: #16324F;
            --primary-hover: #102438;
            --primary-light: #F0F4F8;
            --secondary: #0F766E;
            --secondary-hover: #0D655E;
            --secondary-light: #F0FDFA;

            /* Canvas & Surfaces */
            --bg-page: #F8FAFC;
            --surface: #FFFFFF;
            --surface-subtle: #F1F5F9;
            --border: #E2E8F0;
            --border-hover: #CBD5E1;

            /* Typography */
            --text-primary: #1E293B;
            --text-muted: #64748B;
            --text-light: #94A3B8;

            /* Semantic Feedback */
            --success: #15803D;
            --success-bg: #F0FDF4;
            --success-border: #BBF7D0;
            --warning: #B45309;
            --warning-bg: #FFFBEB;
            --warning-border: #FDE68A;
            --danger: #B91C1C;
            --danger-bg: #FEF2F2;
            --danger-border: #FECACA;
            --info: #0284C7;
            --info-bg: #F0F9FF;
            --info-border: #BAE6FD;

            /* Backward compatibility aliases */
            --bg-color: var(--bg-page);
            --card-bg: var(--surface);
            --text-color: var(--text-primary);
            --border-color: var(--border);

            /* Elevation & Radius */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-xs: 0 1px 2px 0 rgba(15, 23, 42, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(15, 23, 42, 0.08), 0 1px 2px -1px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.05);

            --sidebar-width: 250px;
            --topbar-height: 60px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.5;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* App Shell Layout */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .app-sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary);
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 0.2s ease;
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #FFFFFF;
            text-decoration: none;
            letter-spacing: -0.01em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand svg {
            width: 22px;
            height: 22px;
            color: var(--secondary);
            flex-shrink: 0;
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .sidebar-group-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94A3B8;
            font-weight: 700;
            padding: 0 10px;
            margin-bottom: 6px;
        }

        .sidebar-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #E2E8F0;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.15s ease;
        }

        .sidebar-link svg {
            width: 18px;
            height: 18px;
            opacity: 0.75;
            flex-shrink: 0;
            transition: opacity 0.15s ease;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #FFFFFF;
        }

        .sidebar-link:hover svg {
            opacity: 1;
        }

        .sidebar-link.active {
            background-color: var(--secondary);
            color: #FFFFFF;
            font-weight: 600;
        }

        .sidebar-link.active svg {
            opacity: 1;
        }

        .sidebar-badge {
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 9999px;
        }

        .sidebar-link.active .sidebar-badge {
            background-color: #FFFFFF;
            color: var(--secondary);
        }

        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.75rem;
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Top Bar */
        .app-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .app-topbar {
            height: var(--topbar-height);
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-toggle-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
            cursor: pointer;
        }

        .topbar-context {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--text-primary);
            padding: 4px 10px 4px 6px;
            border-radius: 9999px;
            background-color: var(--surface-subtle);
            border: 1px solid var(--border);
        }

        .user-chip-avatar {
            width: 28px;
            height: 28px;
            border-radius: 9999px;
            background: var(--primary);
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }

        .user-chip-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-chip-name {
            font-weight: 600;
            font-size: 0.8125rem;
        }

        .user-chip-role {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Content Container */
        .app-content {
            flex: 1;
            width: 100%;
            max-width: 1360px;
            margin: 0 auto;
            padding: 24px 28px;
        }

        /* Standalone Container for Unauthenticated Views */
        .guest-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Reusable Standardized Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1.25;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #FFFFFF;
            box-shadow: var(--shadow-xs);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: #FFFFFF;
            box-shadow: var(--shadow-xs);
        }

        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }

        .btn-outline {
            background-color: var(--surface);
            border-color: var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background-color: var(--surface-subtle);
            border-color: var(--border-hover);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.775rem;
        }

        .btn-danger {
            background-color: var(--danger);
            color: #FFFFFF;
        }

        .btn-danger:hover {
            background-color: #991b1b;
        }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 20px 24px;
        }

        /* Flash Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success-border);
        }

        .alert-danger {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }

        .alert-warning {
            background-color: var(--warning-bg);
            color: var(--warning);
            border: 1px solid var(--warning-border);
        }

        /* Modern Pagination */
        .pagination-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.85rem;
            width: 100%;
        }

        .pagination-info {
            color: var(--text-muted);
            font-size: 0.8125rem;
        }

        .pagination-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 4px;
            margin: 0;
            padding: 0;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .page-link:hover {
            background-color: var(--surface-subtle);
            border-color: var(--border-hover);
            color: var(--secondary);
        }

        .page-item.active .page-link {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: #FFFFFF;
            font-weight: 600;
        }

        .page-item.disabled .page-link {
            color: var(--text-light);
            background-color: var(--surface-subtle);
            border-color: var(--border);
            cursor: not-allowed;
            pointer-events: none;
        }

        nav[role="navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }

        /* Responsive Backdrop & Drawer on Mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.4);
            z-index: 95;
            backdrop-filter: blur(2px);
        }

        @media (max-width: 900px) {
            .app-sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-open .app-sidebar {
                transform: translateX(0);
            }

            body.sidebar-open .sidebar-backdrop {
                display: block;
            }

            .app-main {
                margin-left: 0;
            }

            .sidebar-toggle-btn {
                display: inline-flex;
            }

            .app-content {
                padding: 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @auth
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="app-shell">
            {{-- Enterprise Collapsible Sidebar --}}
            <aside class="app-sidebar" id="appSidebar">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>Civil Indent Portal</span>
                </a>

                <div class="sidebar-content">
                    {{-- Workflow Section --}}
                    <div>
                        <div class="sidebar-group-title">Requisitions</div>
                        <ul class="sidebar-nav">
                            <li>
                                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    </svg>
                                    <span>Dashboard</span>
                                </a>
                            </li>

                            @php
                                $navWithMeCount = $withMeCount ?? \App\Models\Indent::withMe(Auth::user()->cpf)->count();
                            @endphp
                            <li>
                                <a href="{{ route('indents.inbox') }}" class="sidebar-link {{ request()->routeIs('indents.inbox') || (request()->routeIs('indents.index') && in_array(request('tab'), ['with-me', 'holder'])) ? 'active' : '' }}">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    <span>Work Queue</span>
                                    @if($navWithMeCount > 0)
                                        <span class="sidebar-badge">{{ $navWithMeCount }}</span>
                                    @endif
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('indents.index') }}" class="sidebar-link {{ request()->routeIs('indents.index') && !in_array(request('tab'), ['with-me', 'holder']) && !request()->routeIs('indents.inbox') ? 'active' : '' }}">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 17.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                    <span>All Indents</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('indents.create') }}" class="sidebar-link {{ request()->routeIs('indents.create') ? 'active' : '' }}">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <span>Raise New Indent</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Admin Section --}}
                    @if(Auth::user()->isSuperAdmin() || Gate::allows('viewAny', \App\Models\RoleAssignment::class))
                        <div>
                            <div class="sidebar-group-title">System & Administration</div>
                            <ul class="sidebar-nav">
                                @if(Auth::user()->isSuperAdmin())
                                    <li>
                                        <a href="{{ route('admin.areas.index') }}" class="sidebar-link {{ request()->routeIs('admin.areas.*') || request()->routeIs('admin.installations.*') ? 'active' : '' }}">
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                            </svg>
                                            <span>Master Data</span>
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                            </svg>
                                            <span>Roles Architecture</span>
                                        </a>
                                    </li>
                                @endif

                                @can('viewAny', \App\Models\RoleAssignment::class)
                                    <li>
                                        <a href="{{ route('admin.users-roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.users-roles.*') ? 'active' : '' }}">
                                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                            </svg>
                                            <span>Users & Roles</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="sidebar-footer">
                    <span>CPF: <code>{{ Auth::user()->cpf }}</code></span>
                    <!-- <span>Mode: {{ strtoupper(config('auth.auth_mode', 'DEV')) }}</span> -->
                </div>
            </aside>

            {{-- Main Application Area --}}
            <div class="app-main">
                {{-- Compact Top Bar --}}
                <header class="app-topbar">
                    <div class="topbar-left">
                        <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle navigation">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                        <span class="topbar-context">
                            @yield('title', 'Civil Indent Portal')
                        </span>
                    </div>

                    <div class="topbar-right">
                        @php
                            $user = Auth::user();
                            $nameParts = array_values(array_filter(explode(' ', trim($user->name ?? ''))));
                            $initials = !empty($nameParts[0]) ? mb_substr($nameParts[0], 0, 1) : '';
                            if (count($nameParts) > 1) {
                                $initials .= mb_substr(end($nameParts), 0, 1);
                            }
                        @endphp

                        <div class="user-chip">
                            <div class="user-chip-avatar">{{ strtoupper($initials ?: 'U') }}</div>
                            <div class="user-chip-info">
                                <span class="user-chip-name">{{ $user->name }}</span>
                                <span class="user-chip-role">{{ $user->designation }}</span>
                            </div>
                        </div>

                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm" title="Sign out of system">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </header>

                {{-- Page Main Content --}}
                <main class="app-content">
                    @if(session('status'))
                        <div class="alert alert-success">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>{{ session('status') }}</div>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <div>
                                <strong>Please correct the following errors:</strong>
                                <ul style="margin-left: 20px; margin-top: 6px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <script>
            (function() {
                var toggleBtn = document.getElementById('sidebarToggle');
                var backdrop = document.getElementById('sidebarBackdrop');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function() {
                        document.body.classList.toggle('sidebar-open');
                    });
                }
                if (backdrop) {
                    backdrop.addEventListener('click', function() {
                        document.body.classList.remove('sidebar-open');
                    });
                }
            })();
        </script>
    @else
        <div class="guest-container">
            @yield('content')
        </div>
    @endauth

    @stack('scripts')
</body>

</html>

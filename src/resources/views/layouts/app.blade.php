<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Civil Indent Portal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-border: #f87171;
            --success: #16a34a;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Red DEV MODE Banner */
        .dev-banner {
            background-color: #dc2626;
            color: #ffffff;
            padding: 10px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            letter-spacing: 0.025em;
        }

        .dev-banner code {
            background: rgba(0, 0, 0, 0.25);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 700;
            color: #fef08a;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 16px;
            flex: 1;
        }

        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-outline {
            background-color: transparent;
            border-color: var(--border-color);
            color: var(--text-color);
        }

        .btn-outline:hover {
            background-color: #f1f5f9;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }

        .alert-danger {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
        }

        .alert-success {
            background-color: #f0fdf4;
            color: var(--success);
            border: 1px solid #86efac;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px;
        }

        /* Pagination Styling */
        .pagination-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.875rem;
            width: 100%;
        }

        .pagination-info {
            color: var(--text-muted);
            font-size: 0.825rem;
        }

        .pagination-info strong {
            color: var(--text-color);
        }

        .pagination-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 4px;
            margin: 0;
            padding: 0;
        }

        .page-item {
            display: inline-block;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.825rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .page-link:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: var(--primary);
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            font-weight: 600;
        }

        .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f8fafc;
            border-color: var(--border-color);
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Fallback for unstyled SVG icons in default pagination */
        nav[role="navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- @if(config('auth.auth_mode') === 'dev')
        <div class="dev-banner" id="dev-mode-banner">
            ⚠️ <strong>DEV MODE ACTIVE:</strong> Authenticating via <code>DevAuthenticator</code>. Fixed password: <code>{{ config('auth.dev_password', 'secret123') }}</code> works for any seeded CPF.
        </div>
    @endif -->

    @auth
        <nav class="navbar">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                🏗️ Civil Indent Portal
            </a>
            <div class="navbar-user">
                @php
                    $navWithMeCount = \App\Models\Indent::withMe(Auth::user()->cpf)->count();
                @endphp
                <a href="{{ route('indents.inbox') }}"
                    class="btn {{ request()->routeIs('indents.inbox') || (request()->routeIs('indents.index') && (request('tab') === 'with-me' || request('tab') === 'holder')) ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: 0.825rem; display: inline-flex; align-items: center; gap: 6px;">
                    📥 With Me
                    @if($navWithMeCount > 0)
                        <span style="background: {{ request()->routeIs('indents.inbox') || (request()->routeIs('indents.index') && (request('tab') === 'with-me' || request('tab') === 'holder')) ? '#ffffff' : '#2563eb' }}; color: {{ request()->routeIs('indents.inbox') || (request()->routeIs('indents.index') && (request('tab') === 'with-me' || request('tab') === 'holder')) ? '#2563eb' : '#ffffff' }}; font-weight: 700; border-radius: 9999px; padding: 1px 7px; font-size: 0.72rem;">
                            {{ $navWithMeCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('indents.index') }}"
                    class="btn {{ request()->routeIs('indents.index') && !in_array(request('tab'), ['with-me', 'holder']) && !request()->routeIs('indents.inbox') ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: 0.825rem;">
                    📋 All Indents
                </a>
                @if(Auth::user()->isSuperAdmin())
                    <a href="{{ route('admin.areas.index') }}"
                        class="btn {{ request()->routeIs('admin.areas.*') || request()->routeIs('admin.installations.*') ? 'btn-primary' : 'btn-outline' }}"
                        style="padding: 6px 12px; font-size: 0.825rem;">
                        🗺️ Master Data
                    </a>
                    <a href="{{ route('admin.roles.index') }}"
                        class="btn {{ request()->routeIs('admin.roles.*') ? 'btn-primary' : 'btn-outline' }}"
                        style="padding: 6px 12px; font-size: 0.825rem;">
                        🛡️ Roles (§3)
                    </a>
                @endif
                @can('viewAny', \App\Models\RoleAssignment::class)
                    <a href="{{ route('admin.users-roles.index') }}"
                        class="btn {{ request()->routeIs('admin.users-roles.*') ? 'btn-primary' : 'btn-outline' }}"
                        style="padding: 6px 12px; font-size: 0.825rem;">
                        👥 Users & Roles
                    </a>
                @endcan
                <span style="font-size: 0.875rem; color: var(--text-muted);">
                    Logged in as <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->designation }})
                </span>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="padding: 6px 12px;">Sign Out</button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="container">
        @if(session('status'))
            <div class="alert alert-success">
                ✅ {{ session('status') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul style="margin-left: 20px; margin-top: 6px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

</body>

</html>
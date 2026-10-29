@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            margin-bottom: 22px;
            box-shadow: var(--shadow-xs);
            flex-wrap: wrap;
        }

        .hero-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hero-avatar {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary) 0%, #1E3A5F 100%);
            color: #FFFFFF;
            font-size: 1.35rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            letter-spacing: 0.05em;
        }

        .dashboard-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 3px;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .hero-meta {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .meta-dot {
            color: #CBD5E1;
        }

        /* Actionable KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-xs);
            text-decoration: none;
            color: inherit;
            transition: all 0.15s ease;
            border-left: 4px solid var(--border);
        }

        .kpi-card:hover {
            box-shadow: var(--shadow-sm);
            transform: translateY(-1px);
        }

        .kpi-work-queue { border-left-color: var(--secondary); }
        .kpi-created { border-left-color: var(--primary); }
        .kpi-drafts { border-left-color: var(--warning); }
        .kpi-flagged { border-left-color: var(--danger); }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .kpi-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .kpi-val {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
        }

        .kpi-desc {
            font-size: 0.775rem;
            color: var(--text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Section Cards */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xs);
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .mini-table th {
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 10px 14px;
        }

        .mini-table td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: var(--text-primary);
        }

        .mini-table tr:hover td {
            background: var(--surface-subtle);
        }

        .mini-table tr:last-child td {
            border-bottom: none;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }

        .profile-item {
            background: var(--surface-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 10px 14px;
        }

        .profile-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }

        .profile-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }
    </style>
@endpush

@section('content')
    @php
        $parts = array_values(array_filter(explode(' ', trim($user->name))));
        $firstInitial = !empty($parts[0]) ? mb_substr($parts[0], 0, 1) : '';
        $surnameInitial = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : (mb_strlen($parts[0] ?? '') > 1 ? mb_substr($parts[0], 1, 1) : '');
        $heroInitials = strtoupper($firstInitial . $surnameInitial);
    @endphp

    {{-- Welcome Hero --}}
    <div class="dashboard-hero">
        <div class="hero-left">
            <div class="hero-avatar">{{ $heroInitials }}</div>
            <div>
                <h1 class="dashboard-title">Welcome back, {{ $user->name }}</h1>
                <p class="hero-meta">
                    <span>{{ $user->designation }}</span>
                    <span class="meta-dot">&bull;</span>
                    <span>{{ $user->location }}</span>
                    <span class="meta-dot">&bull;</span>
                    <span>CPF: <strong><code>{{ $user->cpf }}</code></strong></span>
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('indents.inbox') }}" class="btn btn-outline btn-sm">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <span>Work Queue ({{ $workQueueCount }})</span>
            </a>
            <a href="{{ route('indents.create') }}" class="btn btn-secondary btn-sm">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Raise New Indent</span>
            </a>
        </div>
    </div>

    {{-- Actionable KPI Metric Cards --}}
    <div class="kpi-grid">
        <a href="{{ route('indents.inbox') }}" class="kpi-card kpi-work-queue">
            <div class="kpi-top">
                <div class="kpi-label">Work Queue</div>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--secondary)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>
            <div class="kpi-val" style="color: var(--secondary);">{{ $workQueueCount }}</div>
            <div class="kpi-desc">
                <span>Awaiting your review or action</span>
                <strong>&rarr;</strong>
            </div>
        </a>

        <a href="{{ route('indents.index', ['tab' => 'my']) }}" class="kpi-card kpi-created">
            <div class="kpi-top">
                <div class="kpi-label">Created by Me</div>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--primary)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 17.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
            <div class="kpi-val" style="color: var(--primary);">{{ $myCreatedCount }}</div>
            <div class="kpi-desc">
                <span>Total requisitions raised</span>
                <strong>&rarr;</strong>
            </div>
        </a>

        <a href="{{ route('indents.index', ['tab' => 'drafts']) }}" class="kpi-card kpi-drafts">
            <div class="kpi-top">
                <div class="kpi-label">My Drafts</div>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--warning)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </div>
            <div class="kpi-val" style="color: var(--warning);">{{ $myDraftsCount }}</div>
            <div class="kpi-desc">
                <span>Unsubmitted drafts (editable)</span>
                <strong>&rarr;</strong>
            </div>
        </a>

        <a href="{{ route('indents.index', ['tab' => 'flagged']) }}" class="kpi-card kpi-flagged">
            <div class="kpi-top">
                <div class="kpi-label">Flagged / Rejected</div>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--danger)" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 00-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                </svg>
            </div>
            <div class="kpi-val" style="color: {{ $flaggedCount > 0 ? 'var(--danger)' : 'var(--text-muted)' }};">{{ $flaggedCount }}</div>
            <div class="kpi-desc">
                <span>{{ $flaggedCount > 0 ? 'Requires your rectification' : 'No rejected items' }}</span>
                <strong>&rarr;</strong>
            </div>
        </a>
    </div>

    {{-- Primary Section: Action Required in Work Queue --}}
    <div class="section-card">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--secondary)" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Action Required in Your Work Queue</span>
                </h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 2px;">
                    Requisitions currently held by your CPF awaiting your review, forward, or execution.
                </p>
            </div>
            @if($workQueueCount > 5)
                <a href="{{ route('indents.inbox') }}" class="btn btn-outline btn-sm">
                    View All ({{ $workQueueCount }}) &rarr;
                </a>
            @endif
        </div>

        @if($pendingWorkQueue->isNotEmpty())
            <div class="table-responsive">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Indent #</th>
                            <th>Work Title & Category</th>
                            <th>Installation</th>
                            <th>Priority</th>
                            <th>State & Status</th>
                            <th>Required By</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingWorkQueue as $indent)
                            <tr>
                                <td style="white-space: nowrap;">
                                    <a href="{{ route('indents.show', $indent) }}" style="font-family: monospace; font-weight: 700; color: var(--primary); text-decoration: none;">
                                        {{ $indent->number }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('indents.show', $indent) }}" style="font-weight: 600; color: var(--text-primary); text-decoration: none;">
                                        {{ $indent->title }}
                                    </a>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $indent->work_type }}</div>
                                </td>
                                <td style="white-space: nowrap;">
                                    {{ $indent->installation?->name ?? '—' }}
                                </td>
                                <td style="white-space: nowrap;">
                                    <x-priority-badge :priority="$indent->priority" />
                                </td>
                                <td style="white-space: nowrap;">
                                    <x-status-badge :indent="$indent" :status="$indent->status" :isDraft="$indent->is_draft" :isRejected="$indent->is_rejected" />
                                </td>
                                <td style="white-space: nowrap; font-size: 0.8rem;">
                                    {{ $indent->required_by ? $indent->required_by->format('d M Y') : '—' }}
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="{{ route('indents.show', $indent) }}" class="btn btn-secondary btn-sm">
                                        Review &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 28px 20px; text-align: center; color: var(--text-muted); background: var(--surface-subtle); border-radius: var(--radius-md);">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; color: var(--secondary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 2px;">Your Work Queue is Clear</div>
                <p style="font-size: 0.8rem; margin: 0;">No civil indents are currently awaiting your action.</p>
            </div>
        @endif
    </div>

    {{-- Secondary Section: Recent Requisitions Created by User --}}
    @if($recentMyIndents->isNotEmpty())
        <div class="section-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--primary)" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span>Recent Requisitions Created by You</span>
                    </h2>
                </div>
                <a href="{{ route('indents.index', ['tab' => 'my']) }}" class="btn btn-outline btn-sm">
                    View All ({{ $myCreatedCount }}) &rarr;
                </a>
            </div>

            <div class="table-responsive">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Indent #</th>
                            <th>Work Title</th>
                            <th>Installation</th>
                            <th>State & Status</th>
                            <th>Current Holder</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentMyIndents as $indent)
                            <tr>
                                <td style="white-space: nowrap;">
                                    <a href="{{ route('indents.show', $indent) }}" style="font-family: monospace; font-weight: 700; color: var(--primary); text-decoration: none;">
                                        {{ $indent->number }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('indents.show', $indent) }}" style="font-weight: 600; color: var(--text-primary); text-decoration: none;">
                                        {{ $indent->title }}
                                    </a>
                                </td>
                                <td style="white-space: nowrap;">
                                    {{ $indent->installation?->name ?? '—' }}
                                </td>
                                <td style="white-space: nowrap;">
                                    <x-status-badge :indent="$indent" :status="$indent->status" :isDraft="$indent->is_draft" :isRejected="$indent->is_rejected" />
                                </td>
                                <td style="white-space: nowrap;">
                                    @if($indent->is_draft)
                                        <span style="color: var(--text-muted); font-style: italic;">Draft</span>
                                    @else
                                        <code>{{ $indent->current_holder_cpf }}</code>
                                    @endif
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="{{ route('indents.show', $indent) }}" class="btn btn-outline btn-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Account Profile Card --}}
    <div class="section-card">
        <h2 class="section-title" style="margin-bottom: 14px;">Your Account Profile</h2>
        <div class="profile-grid">
            <div class="profile-item">
                <div class="profile-label">CPF Number</div>
                <div class="profile-value"><code>{{ $user->cpf }}</code></div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Designation</div>
                <div class="profile-value">{{ $user->designation }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Location / Site</div>
                <div class="profile-value">{{ $user->location }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Email Address</div>
                <div class="profile-value">{{ $user->email ?? 'Not assigned' }}</div>
            </div>
        </div>
    </div>

    {{-- Super Admin Section --}}
    @if($user->isSuperAdmin())
        <div class="section-card" style="border-left: 4px solid var(--secondary);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: var(--primary);">Super Admin Master Control</h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 2px;">
                        Configure organizational Master Data (Areas, Installations) and inspect Section 3 Role Architecture.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('admin.areas.index') }}" class="btn btn-primary btn-sm">
                        Manage Master Data
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline btn-sm">
                        View Roles (§3)
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Dev Mode: Seeded Accounts --}}
    @if(config('auth.auth_mode') === 'dev' && $seededUsers->isNotEmpty())
        <details class="section-card" style="cursor: pointer;">
            <summary style="font-size: 0.9rem; font-weight: 700; color: var(--danger); user-select: none;">
                Dev Mode: Quick Login Accounts ({{ $seededUsers->count() }}) — Click to view
            </summary>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 8px 0 12px;">
                In local dev mode, any of these CPF numbers can log in with password <code>{{ config('auth.dev_password', 'secret123') }}</code>.
            </p>
            <div class="table-responsive">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>CPF</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Location</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seededUsers as $u)
                            <tr style="{{ $u->id === $user->id ? 'background: var(--secondary-light);' : '' }}">
                                <td><strong><code>{{ $u->cpf }}</code></strong></td>
                                <td>{{ $u->name }}</td>
                                <td><span style="font-weight: 600;">{{ $u->designation }}</span></td>
                                <td>{{ $u->location }}</td>
                                <td style="color: var(--text-muted);">{{ $u->email ?? '—' }}</td>
                                <td>
                                    <span style="font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 9999px; {{ $u->active ? 'background: var(--success-bg); color: var(--success);' : 'background: var(--danger-bg); color: var(--danger);' }}">
                                        {{ $u->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
@endsection

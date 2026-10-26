@extends('layouts.app')

@section('title', 'Users & Roles Management - Civil Indent Portal')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">

    <div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color);">👥 Users & Roles Management</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 4px;">
            Search users by CPF or Name, view current assignments, and manage roles per organizational policies.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="#assign-role-card" class="btn btn-primary" onclick="document.getElementById('assign-role-card').scrollIntoView({behavior: 'smooth'})">
            + Assign Role
        </a>
        <a href="#audit-logs-card" class="btn btn-outline" style="background: white;" onclick="document.getElementById('audit-logs-card').scrollIntoView({behavior: 'smooth'})">
            📜 View Audit Logs
        </a>
        @if(Auth::user()->isSuperAdmin())
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline" style="background: white;">
                🛡️ View Role Definitions (§3)
            </a>
        @endif
    </div>
</div>

<!-- Stats Overview -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Total Users</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $totalUsers }}</div>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid var(--success);">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Assigned Roles</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $assignedUsersCount }}</div>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #f59e0b;">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Unassigned / General Users</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $unassignedUsersCount }}</div>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #8b5cf6;">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Your Authority</div>
        <div style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">
            @if($actor->isSuperAdmin())
                <span style="color: #2563eb;">⚡ Super Admin (Full)</span>
            @elseif($actor->hasRole('SM'))
                <span style="color: #059669;">🛡️ SM (SAM & IM in Area)</span>
            @elseif($actor->hasRole('SAM'))
                <span style="color: #7c3aed;">📍 SAM (IM in Area)</span>
            @else
                <span style="color: var(--text-muted);">View Only</span>
            @endif
        </div>
    </div>
</div>

<!-- Policy Rules Callout -->
<div class="card" style="margin-bottom: 24px; background: #f8fafc; border: 1px solid #cbd5e1; border-left: 5px solid #2563eb; padding: 18px 20px;">
    <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
        📜 Enforced Role Assignment Policies & Rules
    </h3>
    <ul style="margin-left: 20px; font-size: 0.85rem; color: #475569; line-height: 1.6;">
        <li><strong>One role per CPF:</strong> Assigning a new role to a CPF that already holds a role will be rejected. Remove the existing role first.</li>
        <li><strong>Single-holder enforcement:</strong> Roles with <code>multi_holder = false</code> (such as SM, SAM, IM, HES) allow only one active user per designated scope.</li>
        <li><strong>Role delegation authority:</strong> Super Admin assigns any role; Surface Manager (SM) assigns SAM and IM in their area; Surface Area Manager (SAM) assigns IM only in their area.</li>
    </ul>
</div>

<!-- Search Bar & Filters -->
<div class="card" style="margin-bottom: 24px; padding: 18px 20px;">
    <form method="GET" action="{{ route('admin.users-roles.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 260px;">
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Search by CPF (e.g. USER001, SAM001), Name, or Email..."
                   class="form-control"
                   style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
            🔍 Search
        </button>
        @if($search !== '')
            <a href="{{ route('admin.users-roles.index') }}" class="btn btn-outline" style="padding: 10px 16px; background: white;">
                ✖ Clear Filter
            </a>
            <span style="font-size: 0.85rem; color: var(--text-muted);">
                Found {{ $users->total() }} matching result(s)
            </span>
        @endif
    </form>
</div>

<!-- Users Table -->
<div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-color); margin-bottom: 32px;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <th style="padding: 14px 20px; font-weight: 600;">User / CPF</th>
                    <th style="padding: 14px 20px; font-weight: 600;">Email & Location</th>
                    <th style="padding: 14px 20px; font-weight: 600;">Current Role</th>
                    <th style="padding: 14px 20px; font-weight: 600;">Scope</th>
                    <th style="padding: 14px 20px; font-weight: 600;">Assigned By</th>
                    <th style="padding: 14px 20px; font-weight: 600; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $assignment = $user->roleAssignment;
                        $role = $assignment?->role;
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; background: white; vertical-align: middle;">
                        <td style="padding: 14px 20px;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 0.925rem;">
                                {{ $user->name }}
                            </div>
                            <div style="margin-top: 3px;">
                                <span style="font-family: monospace; font-size: 0.75rem; background: #e2e8f0; color: #1e293b; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                    {{ $user->cpf }}
                                </span>
                            </div>
                        </td>
                        <td style="padding: 14px 20px; color: var(--text-muted);">
                            <div>{{ $user->email }}</div>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">📍 {{ $user->location ?? 'Headquarters' }}</div>
                        </td>
                        <td style="padding: 14px 20px;">
                            @if($role)
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.8rem;
                                    @if($role->name === 'Super Admin') background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
                                    @elseif($role->name === 'SM') background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
                                    @elseif($role->name === 'SAM') background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe;
                                    @elseif($role->name === 'IM') background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;
                                    @elseif($role->name === 'CE') background: #fefce8; color: #854d0e; border: 1px solid #fef08a;
                                    @else background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;
                                    @endif">
                                    {{ $role->name }}
                                </span>
                            @else
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; color: #64748b; background: #f1f5f9;">
                                    Unassigned / General
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px;">
                            @if($assignment?->installation)
                                <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 500; background: #fff7ed; color: #c2410c; padding: 3px 8px; border-radius: 4px; border: 1px solid #ffedd5;">
                                    🏭 {{ $assignment->installation->name }} ({{ $assignment->installation->code }})
                                </div>
                                @if($assignment->installation->area)
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                        Area: {{ $assignment->installation->area->name }}
                                    </div>
                                @endif
                            @elseif($assignment?->area)
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 500; background: #ecfdf5; color: #047857; padding: 3px 8px; border-radius: 4px; border: 1px solid #d1fae5;">
                                    🗺️ {{ $assignment->area->name }} ({{ $assignment->area->code }})
                                </span>
                            @elseif($role)
                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                    🌐 System-wide
                                </span>
                            @else
                                <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px; font-size: 0.8rem; color: var(--text-muted);">
                            @if($assignment?->assigned_by)
                                <div><strong style="color: #334155;">{{ $assignment->assigner?->name ?? $assignment->assigned_by }}</strong></div>
                                <div style="font-family: monospace; font-size: 0.75rem; color: #94a3b8;">{{ $assignment->assigned_by }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px; text-align: right; white-space: nowrap;">
                            @if($assignment)
                                @if($user->cpf === $actor->cpf)
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;" title="You cannot remove your own role assignment">
                                        🔒 Current User
                                    </span>
                                @elseif($actor->can('delete', $assignment))
                                    <form action="{{ route('admin.users-roles.destroy', $assignment) }}" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to remove the role {{ $role->name }} from {{ $user->name }} ({{ $user->cpf }})?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline" style="color: var(--danger); border-color: #fca5a5; background: #fff5f5; padding: 5px 12px; font-size: 0.8rem;">
                                            🗑️ Remove Role
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Cannot remove</span>
                                @endif
                            @else
                                <button type="button"
                                        class="btn btn-outline"
                                        onclick="prefillAssignment('{{ $user->cpf }}', '{{ addslashes($user->name) }}')"
                                        style="background: white; border-color: var(--primary); color: var(--primary); padding: 5px 12px; font-size: 0.8rem;">
                                    ➕ Assign Role
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 32px; text-align: center; color: var(--text-muted);">
                            No users found matching your search query.
                            @if($search !== '')
                                <div style="margin-top: 8px;">
                                    <a href="{{ route('admin.users-roles.index') }}" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px; background: white;">
                                        Clear Search
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div style="padding: 14px 20px; background: #f8fafc; border-top: 1px solid var(--border-color); width: 100%; box-sizing: border-box;">
            {{ $users->links('vendor.pagination.default', ['itemLabel' => 'users']) }}
        </div>
    @endif
</div>

<!-- Audit Logs Card -->
<div class="card" id="audit-logs-card" style="border: 1px solid var(--border-color); background: white; margin-bottom: 32px; padding: 0; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                📜 Audit Logs
                <span style="font-size: 0.75rem; background: #e2e8f0; color: #334155; padding: 2px 8px; border-radius: 9999px; font-weight: 600;">
                    {{ $auditLogs->total() }} Total Events (7 per page)
                </span>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 2px;">
                Complete chronological history of every role assignment and removal (Who, What, When, Details).
            </p>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 12px 20px; font-weight: 600;">When</th>
                    <th style="padding: 12px 20px; font-weight: 600;">Who (Actor)</th>
                    <th style="padding: 12px 20px; font-weight: 600;">What (Action)</th>
                    <th style="padding: 12px 20px; font-weight: 600;">Target User</th>
                    <th style="padding: 12px 20px; font-weight: 600;">Details & Scope</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr style="border-bottom: 1px solid #f1f5f9; background: white; vertical-align: middle;">
                        <td style="padding: 12px 20px; white-space: nowrap;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 0.85rem;">
                                {{ ($log->when ?? $log->created_at)->format('d M Y, H:i') }}
                            </div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">
                                {{ ($log->when ?? $log->created_at)->diffForHumans() }}
                            </div>
                        </td>
                        <td style="padding: 12px 20px;">
                            <div style="font-weight: 600; color: #0f172a;">
                                {{ $log->actor?->name ?? 'System Actor' }}
                            </div>
                            <span style="font-family: monospace; font-size: 0.75rem; background: #e2e8f0; color: #1e293b; padding: 1px 6px; border-radius: 4px;">
                                {{ $log->who }}
                            </span>
                        </td>
                        <td style="padding: 12px 20px;">
                            @if(strtolower($log->what) === 'assign')
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                    ➕ ASSIGN
                                </span>
                            @elseif(strtolower($log->what) === 'remove')
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                    🗑️ REMOVE
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                    {{ strtoupper($log->what) }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 20px;">
                            <div style="font-weight: 600; color: #0f172a;">
                                {{ $log->details['target_name'] ?? '—' }}
                            </div>
                            <span style="font-family: monospace; font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 1px 6px; border-radius: 4px;">
                                {{ $log->details['target_cpf'] ?? '—' }}
                            </span>
                        </td>
                        <td style="padding: 12px 20px; font-size: 0.825rem; color: #334155;">
                            <div>
                                Role: <strong style="color: #0f172a;">{{ $log->details['role_name'] ?? ('Role #' . ($log->details['role_id'] ?? '')) }}</strong>
                            </div>
                            @if(!empty($log->details['installation_name']))
                                <div style="color: #c2410c; margin-top: 2px;">
                                    🏭 {{ $log->details['installation_name'] }}
                                </div>
                            @elseif(!empty($log->details['area_name']))
                                <div style="color: #047857; margin-top: 2px;">
                                    🗺️ {{ $log->details['area_name'] }}
                                </div>
                            @endif
                            @if(!empty($log->details['from']))
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">
                                    Validity: {{ $log->details['from'] }} @if(!empty($log->details['to'])) to {{ $log->details['to'] }} @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 28px; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                            No audit logs recorded yet. Role assignments and removals will appear here automatically.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($auditLogs->hasPages())
        <div style="padding: 14px 20px; background: #f8fafc; border-top: 1px solid var(--border-color); width: 100%; box-sizing: border-box;">
            {{ $auditLogs->fragment('audit-logs-card')->links('vendor.pagination.default', ['itemLabel' => 'logs']) }}
        </div>
    @endif
</div>


<!-- Assign Role Form Card -->
<div class="card" id="assign-role-card" style="border: 1px solid var(--border-color); background: white;">
    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-color);">
            ➕ Assign Role to User
        </h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 4px;">
            Assign an organizational role to a registered CPF. Scopes and single-holder constraints will be verified automatically.
        </p>
    </div>

    @if($assignableRoles->isEmpty())
        <div class="alert alert-danger" style="margin-bottom: 0;">
            ⚠️ You do not have permission to assign any roles under the current policy rules.
        </div>
    @else
        <form method="POST" action="{{ route('admin.users-roles.store') }}" id="role-assignment-form">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <!-- CPF Field -->
                <div>
                    <label for="cpf" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                        User CPF <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text"
                           id="cpf"
                           name="cpf"
                           required
                           list="unassigned-cpfs"
                           placeholder="Type or select CPF (e.g. USER001)"
                           value="{{ old('cpf') }}"
                           style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; font-family: monospace;">
                    <datalist id="unassigned-cpfs">
                        @foreach($users as $u)
                            @if(!$u->roleAssignment)
                                <option value="{{ $u->cpf }}">{{ $u->name }} ({{ $u->cpf }})</option>
                            @endif
                        @endforeach
                    </datalist>
                    <div id="selected-user-note" style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                        Tip: Click "Assign Role" in the table above to prefill.
                    </div>
                </div>

                <!-- Role Selector -->
                <div>
                    <label for="role_id" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                        Role to Assign <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="role_id"
                            name="role_id"
                            required
                            onchange="handleRoleChange()"
                            style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; background: white;">
                        <option value="">-- Choose Role --</option>
                        @foreach($assignableRoles as $r)
                            <option value="{{ $r->id }}"
                                    data-scope="{{ $r->scope_type }}"
                                    data-multiholder="{{ $r->multi_holder ? '1' : '0' }}"
                                    {{ old('role_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} (Scope: {{ ucfirst($r->scope_type) }}, {{ $r->multi_holder ? 'Multi-holder' : 'Single-holder' }})
                            </option>
                        @endforeach
                    </select>
                    <div id="role-scope-hint" style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                        Select a role to see required operational scope.
                    </div>
                </div>
            </div>

            <!-- Dynamic Scopes Row -->
            <div id="scope-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <!-- Area Scope Dropdown -->
                <div id="area-group" style="display: none;">
                    <label for="area_id" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                        Operational Area <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="area_id"
                            name="area_id"
                            style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; background: white;">
                        <option value="">-- Select Area --</option>
                        @foreach($assignableAreas as $area)
                            <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }} ({{ $area->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Installation Scope Dropdown -->
                <div id="installation-group" style="display: none;">
                    <label for="installation_id" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                        Target Installation <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="installation_id"
                            name="installation_id"
                            style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; background: white;">
                        <option value="">-- Select Installation --</option>
                        @foreach($assignableInstallations as $inst)
                            <option value="{{ $inst->id }}" {{ old('installation_id') == $inst->id ? 'selected' : '' }}>
                                {{ $inst->name }} ({{ $inst->code }}) — {{ $inst->area?->name ?? 'General' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Validity Dates -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label for="from" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                            Valid From
                        </label>
                        <input type="date"
                               id="from"
                               name="from"
                               value="{{ old('from', now()->toDateString()) }}"
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label for="to" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; color: #334155;">
                            Valid To (Optional)
                        </label>
                        <input type="date"
                               id="to"
                               name="to"
                               value="{{ old('to') }}"
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem;">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <button type="reset" class="btn btn-outline" style="background: white;" onclick="resetScopeUI()">
                    Reset Form
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                    💾 Confirm & Assign Role
                </button>
            </div>
        </form>
    @endif
</div>

<script>
    function prefillAssignment(cpf, name) {
        const cpfInput = document.getElementById('cpf');
        const note = document.getElementById('selected-user-note');
        if (cpfInput) {
            cpfInput.value = cpf;
            if (note) {
                note.innerHTML = 'Selected: <strong style="color: #0f172a;">' + name + ' (' + cpf + ')</strong>';
            }
            document.getElementById('assign-role-card').scrollIntoView({ behavior: 'smooth' });
        }
    }

    function handleRoleChange() {
        const roleSelect = document.getElementById('role_id');
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const scope = selectedOption ? selectedOption.getAttribute('data-scope') : null;
        const multiholder = selectedOption ? selectedOption.getAttribute('data-multiholder') : null;

        const areaGroup = document.getElementById('area-group');
        const instGroup = document.getElementById('installation-group');
        const areaSelect = document.getElementById('area_id');
        const instSelect = document.getElementById('installation_id');
        const hint = document.getElementById('role-scope-hint');

        if (!scope) {
            areaGroup.style.display = 'none';
            instGroup.style.display = 'none';
            areaSelect.required = false;
            instSelect.required = false;
            hint.textContent = 'Select a role to see required operational scope.';
            return;
        }

        if (scope === 'area') {
            areaGroup.style.display = 'block';
            instGroup.style.display = 'none';
            areaSelect.required = true;
            instSelect.required = false;
            instSelect.value = '';
            hint.textContent = 'Requires Area selection. ' + (multiholder === '1' ? 'Multiple holders allowed.' : 'Single holder per area.');
        } else if (scope === 'installation') {
            areaGroup.style.display = 'none';
            instGroup.style.display = 'block';
            areaSelect.required = false;
            instSelect.required = true;
            areaSelect.value = '';
            hint.textContent = 'Requires Installation selection. ' + (multiholder === '1' ? 'Multiple engineers allowed (e.g. CE).' : 'Single holder per installation (e.g. IM).');
        } else {
            // scope === 'none'
            areaGroup.style.display = 'none';
            instGroup.style.display = 'none';
            areaSelect.required = false;
            instSelect.required = false;
            areaSelect.value = '';
            instSelect.value = '';
            hint.textContent = 'Organization-wide role (no area/installation scope needed). ' + (multiholder === '1' ? 'Multiple holders allowed.' : 'Single system-wide holder.');
        }
    }

    function resetScopeUI() {
        setTimeout(handleRoleChange, 50);
    }

    // Initialize UI on page load if old input exists
    document.addEventListener('DOMContentLoaded', function () {
        handleRoleChange();
    });
</script>
@endsection

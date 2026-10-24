@extends('layouts.app')

@section('title', 'System Roles (§3) - Civil Indent Portal')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color);">🛡️ System Roles & Scope Types (Spec §3)</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 4px;">
            Core authorization roles seeded per Functional Requirements §3.
        </p>
    </div>
    <div>
        <a href="{{ route('admin.areas.index') }}" class="btn btn-outline" style="background: white;">
            &larr; Manage Master Data
        </a>
    </div>
</div>

<!-- Specification Reference Callout -->
<div class="card" style="margin-bottom: 24px; background: #f0fdf4; border: 1px solid #86efac; border-left: 5px solid #22c55e;">
    <h3 style="font-size: 1rem; font-weight: 700; color: #166534; margin-bottom: 6px;">
        📌 Specification Rules (§3 Role Architecture & Indent Journey)
    </h3>
    <ul style="margin-left: 20px; font-size: 0.875rem; color: #15803d; line-height: 1.6;">
        <li><strong>User (Normal user):</strong> <code>creates the indent</code> (Scope: <code>none</code>).</li>
        <li><strong>IM (Installation Manager):</strong> <code>one per installation</code> (Scope: <code>installation</code>, <code>multi_holder = false</code>).</li>
        <li><strong>SAM (Surface Area Manager):</strong> <code>one per area</code> (Scope: <code>area</code>, <code>multi_holder = false</code>).</li>
        <li><strong>SM (Surface Manager):</strong> <code>assigns SAM and IM</code> (Scope: <code>area</code>, <code>multi_holder = false</code>).</li>
        <li><strong>HES (Head Eng. Services):</strong> <code>reviews, forwards</code> (Scope: <code>none</code>).</li>
        <li><strong>LM Works (Civil Head):</strong> <code>sees all indents</code> (Scope: <code>none</code>).</li>
        <li><strong>ICO (Incharge Civil Ops):</strong> <code>assigns an engineer</code> (Scope: <code>none</code>).</li>
        <li><strong>CE (Civil Engineer):</strong> <code>executes the work</code> (Scope: <code>installation</code>, <code>multi_holder = true</code>).</li>
    </ul>
</div>

@php
    $roleDetails = [
        'User' => ['full_name' => 'Normal user', 'duty' => 'Creates the indent', 'stage' => 'Step 1'],
        'IM' => ['full_name' => 'Installation Manager', 'duty' => 'One per installation (approves plant works)', 'stage' => 'Step 2'],
        'SAM' => ['full_name' => 'Surface Area Manager', 'duty' => 'One per area (area concurrence)', 'stage' => 'Step 3'],
        'SM' => ['full_name' => 'Surface Manager', 'duty' => 'Assigns SAM and IM (asset operations head)', 'stage' => 'Step 4'],
        'HES' => ['full_name' => 'Head Eng. Services', 'duty' => 'Reviews and forwards (central engineering sanction)', 'stage' => 'Step 5'],
        'LM Works' => ['full_name' => 'Civil Head (Line Manager Works)', 'duty' => 'Sees all indents across organization', 'stage' => 'Step 6'],
        'ICO' => ['full_name' => 'Incharge Civil Ops', 'duty' => 'Assigns an executing civil engineer', 'stage' => 'Step 7'],
        'CE' => ['full_name' => 'Civil Engineer', 'duty' => 'Executes the physical civil work at site', 'stage' => 'Step 8'],
        'Super Admin' => ['full_name' => 'Super Administrator', 'duty' => 'Master data & role management', 'stage' => 'Admin'],
    ];
@endphp

<!-- Roles Table -->
<div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-color);">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <th style="padding: 14px 20px; font-weight: 600; width: 60px;">ID</th>
                    <th style="padding: 14px 20px; font-weight: 600; width: 320px;">Role & Official Designation</th>
                    <th style="padding: 14px 20px; font-weight: 600; width: 140px;">Scope Type</th>
                    <th style="padding: 14px 20px; font-weight: 600; width: 140px;">Multi-Holder?</th>
                    <th style="padding: 14px 20px; font-weight: 600;">Active Holders & Assignments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    @php
                        $detail = $roleDetails[$role->name] ?? null;
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; background: white; vertical-align: top;">
                        <td style="padding: 14px 20px; font-family: monospace; color: var(--text-muted); font-weight: 600;">
                            #{{ $role->id }}
                        </td>
                        <td style="padding: 14px 20px;">
                            <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                                <strong style="font-size: 1rem; color: #0f172a;">{{ $role->name }}</strong>
                                <span style="font-weight: 600; color: #1e40af; font-size: 0.875rem;">
                                    — {{ $detail['full_name'] ?? $role->description }}
                                </span>
                                @if($role->name === 'Super Admin')
                                    <span style="font-size: 0.725rem; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px; font-weight: 600;">System Admin</span>
                                @elseif($role->name === 'CE')
                                    <span style="font-size: 0.725rem; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-weight: 600;">Multi-holder</span>
                                @endif
                            </div>
                            @if($detail['duty'] ?? $role->description)
                                <div style="font-size: 0.775rem; color: #64748b; margin-top: 4px;">
                                    <span style="background: #f8fafc; border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                        <strong>Duty:</strong> {{ $detail['duty'] ?? $role->description }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 20px;">
                            @if($role->scope_type === 'installation')
                                <span style="display: inline-block; padding: 3px 10px; background: #e0f2fe; color: #0369a1; border-radius: 999px; font-weight: 600; font-size: 0.775rem;">
                                    🏢 Installation
                                </span>
                            @elseif($role->scope_type === 'area')
                                <span style="display: inline-block; padding: 3px 10px; background: #fef3c7; color: #b45309; border-radius: 999px; font-weight: 600; font-size: 0.775rem;">
                                    🗺️ Area
                                </span>
                            @else
                                <span style="display: inline-block; padding: 3px 10px; background: #f1f5f9; color: #475569; border-radius: 999px; font-weight: 600; font-size: 0.775rem;">
                                    🌐 None (Global)
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px;">
                            @if($role->multi_holder)
                                <span style="display: inline-flex; align-items: center; gap: 4px; color: #15803d; font-weight: 700; background: #dcfce7; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">
                                    ✓ Yes (Multiple)
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 4px; color: #64748b; font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">
                                    ✕ Single Only
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px;">
                            @if($role->assignments->count() > 0)
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    @foreach($role->assignments as $assignment)
                                        <div style="font-size: 0.8rem; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <code>{{ $assignment->cpf }}</code>
                                                <span style="color: var(--text-muted); margin-left: 6px;">
                                                    @if($assignment->installation)
                                                        @ {{ $assignment->installation->name }} ({{ $assignment->installation->code }})
                                                    @elseif($assignment->area)
                                                        @ {{ $assignment->area->name }} ({{ $assignment->area->code }})
                                                    @else
                                                        (Enterprise)
                                                    @endif
                                                </span>
                                            </div>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                From: {{ $assignment->from ? $assignment->from->format('Y-m-d') : 'Indefinite' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.8rem;">No active assignments</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

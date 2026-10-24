@extends('layouts.app')

@section('title', 'Master Data - Civil Indent Portal')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 700; color: var(--text-color);">🗺️ Master Data Management</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 4px;">
            Manage organizational hierarchy: Areas and their subordinate Installations.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('admin.areas.create') }}" class="btn btn-primary">
            + Add New Area
        </a>
        <a href="{{ route('admin.installations.create') }}" class="btn btn-outline" style="background: white;">
            + Add New Installation
        </a>
    </div>
</div>

<!-- Summary Stats Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 16px; border-left: 4px solid var(--primary);">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Total Areas</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $areas->count() }}</div>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid var(--success);">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">Total Installations</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $areas->sum(fn($a) => $a->installations->count()) }}</div>
    </div>
    <div class="card" style="padding: 16px; border-left: 4px solid #8b5cf6;">
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">System Roles (§3)</div>
        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-color); margin-top: 4px;">
            {{ $roles->count() }}
            <a href="{{ route('admin.roles.index') }}" style="font-size: 0.8rem; font-weight: 500; color: var(--primary); text-decoration: none; margin-left: 8px;">View Roles &rarr;</a>
        </div>
    </div>
</div>

<!-- Areas and Installations Accordion / Cards -->
<div style="display: flex; flex-direction: column; gap: 20px;">
    @forelse($areas as $area)
        <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-color);">
            <!-- Area Header -->
            <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="display: inline-block; padding: 4px 10px; background: #e2e8f0; color: #1e293b; border-radius: 6px; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.05em;">
                        {{ $area->code }}
                    </span>
                    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #0f172a;">
                        {{ $area->name }}
                    </h2>
                    <span style="font-size: 0.8rem; color: var(--text-muted); background: white; padding: 2px 8px; border-radius: 999px; border: 1px solid var(--border-color);">
                        {{ $area->installations->count() }} {{ Str::plural('Installation', $area->installations->count()) }}
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="{{ route('admin.installations.create', ['area_id' => $area->id]) }}" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 10px; background: white;">
                        + Add Installation
                    </a>
                    <a href="{{ route('admin.areas.edit', $area) }}" class="btn btn-outline" style="font-size: 0.8rem; padding: 4px 10px; background: white;">
                        Edit Area
                    </a>
                    <form action="{{ route('admin.areas.destroy', $area) }}" method="POST" onsubmit="return confirm('Delete Area \'{{ $area->name }}\' and all its associated installations?');" style="display: inline; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn" style="font-size: 0.8rem; padding: 4px 10px; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border);">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- Installations Table -->
            @if($area->installations->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: white; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                                <th style="padding: 10px 20px; font-weight: 600; width: 140px;">Code</th>
                                <th style="padding: 10px 20px; font-weight: 600;">Installation Name</th>
                                <th style="padding: 10px 20px; font-weight: 600; text-align: right; width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($area->installations as $installation)
                                <tr style="border-bottom: 1px solid #f1f5f9; background: white;">
                                    <td style="padding: 12px 20px; font-family: monospace; font-weight: 600; color: #334155;">
                                        {{ $installation->code }}
                                    </td>
                                    <td style="padding: 12px 20px; font-weight: 500; color: var(--text-color);">
                                        🏢 {{ $installation->name }}
                                    </td>
                                    <td style="padding: 12px 20px; text-align: right;">
                                        <div style="display: inline-flex; gap: 6px;">
                                            <a href="{{ route('admin.installations.edit', $installation) }}" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 8px;">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.installations.destroy', $installation) }}" method="POST" onsubmit="return confirm('Delete installation \'{{ $installation->name }}\'?');" style="display: inline; margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn" style="font-size: 0.75rem; padding: 4px 8px; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border);">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.875rem; background: white;">
                    No installations configured for this area yet.
                    <a href="{{ route('admin.installations.create', ['area_id' => $area->id]) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; margin-left: 6px;">
                        Add the first installation &rarr;
                    </a>
                </div>
            @endif
        </div>
    @empty
        <div class="card" style="text-align: center; padding: 48px;">
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 16px;">
                No areas found in the system.
            </p>
            <a href="{{ route('admin.areas.create') }}" class="btn btn-primary">
                + Create Your First Area
            </a>
        </div>
    @endforelse
</div>
@endsection

@extends('layouts.app')

@section('title', in_array($tab, ['with-me', 'holder']) ? 'Work Queue' : 'Civil Work Indents')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-title {
        font-size: 1.45rem;
        font-weight: 700;
        margin-bottom: 2px;
        color: var(--text-primary);
        letter-spacing: -0.01em;
    }

    .page-subtitle {
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    /* KPI Summary Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-xs);
        border-left: 4px solid var(--border);
    }

    .stat-card-navy { border-left-color: var(--primary); }
    .stat-card-warning { border-left-color: var(--warning); }
    .stat-card-teal { border-left-color: var(--secondary); }
    .stat-card-info { border-left-color: var(--info); }

    .stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .stat-label {
        font-size: 0.72rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    /* Segmented Navigation Tabs */
    .tab-bar {
        display: flex;
        gap: 6px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .tab-link {
        padding: 9px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-muted);
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s ease;
    }

    .tab-link:hover {
        color: var(--text-primary);
    }

    .tab-link.active {
        color: var(--secondary);
        border-bottom-color: var(--secondary);
    }

    .tab-badge {
        font-size: 0.72rem;
        padding: 1px 7px;
        border-radius: 9999px;
        background: var(--surface-subtle);
        color: var(--text-muted);
        font-weight: 700;
    }

    .tab-link.active .tab-badge {
        background: var(--secondary-light);
        color: var(--secondary);
    }

    /* Filters Card */
    .filter-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-xs);
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.85rem;
        color: var(--text-primary);
        background: var(--surface);
        transition: border-color 0.15s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 2px var(--secondary-light);
    }

    /* Enterprise Table Card */
    .table-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    .table-container {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.85rem;
    }

    .data-table th {
        background: var(--surface-subtle);
        border-bottom: 1px solid var(--border);
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 12px 18px;
    }

    .data-table td {
        padding: 13px 18px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        color: var(--text-primary);
    }

    .data-table tr:hover td {
        background-color: var(--surface-subtle);
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            @if(in_array($tab, ['with-me', 'holder']))
                Work Queue
            @else
                Civil Work Indents
            @endif
        </h1>
        <p class="page-subtitle">
            @if(in_array($tab, ['with-me', 'holder']))
                Indents currently assigned to your CPF awaiting review, forwarding, or execution.
            @else
                Track and manage civil requisition lifecycles, approvals, and execution.
            @endif
        </p>
    </div>
    <div>
        <a href="{{ route('indents.create') }}" class="btn btn-secondary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Raise New Indent</span>
        </a>
    </div>
</div>

{{-- Top KPI Statistics Row --}}
<div class="stats-row">
    <div class="stat-card stat-card-navy">
        <div>
            <div class="stat-label">Created by Me</div>
            <div class="stat-val">{{ $myCount }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div>
            <div class="stat-label">My Drafts (Editable)</div>
            <div class="stat-val" style="color: var(--warning);">{{ $myDraftsCount }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-teal">
        <div>
            <div class="stat-label">My Submitted Indents</div>
            <div class="stat-val" style="color: var(--secondary);">{{ $mySubmittedCount }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div>
            <div class="stat-label">Currently Assigned to Me</div>
            <div class="stat-val" style="color: var(--primary);">{{ $holderCount }}</div>
        </div>
    </div>
</div>

{{-- Filter Tabs --}}
<div class="tab-bar">
    <a href="{{ route('indents.index', ['tab' => 'with-me']) }}"
       class="tab-link {{ in_array($tab, ['with-me', 'holder']) ? 'active' : '' }}">
        Work Queue
        <span class="tab-badge">{{ $withMeCount }}</span>
    </a>
    <a href="{{ route('indents.index', ['tab' => 'my']) }}"
       class="tab-link {{ $tab === 'my' ? 'active' : '' }}">
        Created by Me
        <span class="tab-badge">{{ $myCount }}</span>
    </a>
    <a href="{{ route('indents.index', ['tab' => 'drafts']) }}"
       class="tab-link {{ $tab === 'drafts' ? 'active' : '' }}">
        My Drafts
        <span class="tab-badge">{{ $myDraftsCount }}</span>
    </a>
    @if($flaggedCount > 0)
        <a href="{{ route('indents.index', ['tab' => 'flagged']) }}"
           class="tab-link {{ $tab === 'flagged' ? 'active' : '' }}" style="color: var(--danger);">
            Flagged ({{ $flaggedCount }})
        </a>
    @endif
    <a href="{{ route('indents.index', ['tab' => 'all']) }}"
       class="tab-link {{ $tab === 'all' ? 'active' : '' }}">
        All Visible Indents
    </a>
</div>

{{-- Search & Filter Form --}}
<div class="filter-card">
    <form action="{{ route('indents.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div style="flex: 1; min-width: 220px;">
            <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Search Number / Title / Installation
            </label>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Search e.g. CI-2026-0001, GGS-1, Maintenance..."
                   class="form-control">
        </div>

        <div style="min-width: 160px;">
            <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Status
            </label>
            <select name="status" class="form-control">
                <option value="all">All Statuses</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ $status === $st->value ? 'selected' : '' }}>
                        {{ $st->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 160px;">
            <label style="display: block; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Priority
            </label>
            <select name="priority" class="form-control">
                <option value="all">All Priorities</option>
                @foreach($priorities as $pr)
                    <option value="{{ $pr->value }}" {{ $priority === $pr->value ? 'selected' : '' }}>
                        {{ $pr->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">
                Filter
            </button>
            @if($search !== '' || ($status && $status !== 'all') || ($priority && $priority !== 'all'))
                <a href="{{ route('indents.index', ['tab' => $tab]) }}" class="btn btn-outline btn-sm" style="padding: 8px 14px;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Data Table --}}
<div class="table-card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Indent #</th>
                    <th>Work Title & Category</th>
                    <th>Installation & Area</th>
                    <th>Priority</th>
                    <th>State & Status</th>
                    <th>Required By</th>
                    <th>Current Holder</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($indents as $indent)
                    <tr>
                        {{-- Number --}}
                        <td style="white-space: nowrap;">
                            <a href="{{ route('indents.show', $indent) }}" style="font-family: monospace; font-weight: 700; color: var(--primary); text-decoration: none;">
                                {{ $indent->number }}
                            </a>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                {{ $indent->created_at->format('d M Y') }}
                            </div>
                        </td>

                        {{-- Title & Work Type --}}
                        <td style="max-width: 260px;">
                            <a href="{{ route('indents.show', $indent) }}" style="font-weight: 600; color: var(--text-primary); text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $indent->title }}
                            </a>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                {{ $indent->work_type }}
                            </div>
                        </td>

                        {{-- Installation & Area --}}
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $indent->installation?->name ?? '—' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                {{ $indent->installation?->area?->name ?? '—' }}
                            </div>
                        </td>

                        {{-- Priority --}}
                        <td style="white-space: nowrap;">
                            <x-priority-badge :priority="$indent->priority" />
                        </td>

                        {{-- State & Status --}}
                        <td style="white-space: nowrap;">
                            <x-status-badge :indent="$indent" :status="$indent->status" :isDraft="$indent->is_draft" :isRejected="$indent->is_rejected" />
                            @if(!$indent->is_draft && $indent->latestMovement)
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    {{ ucfirst(str_replace('_', ' ', $indent->latestMovement->action)) }}{{ $indent->latestMovement->to_role ? ' (' . $indent->latestMovement->to_role . ')' : '' }}
                                </div>
                            @endif
                        </td>

                        {{-- Required By --}}
                        <td style="white-space: nowrap; font-size: 0.8125rem;">
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $indent->required_by ? $indent->required_by->format('d M Y') : '—' }}
                            </div>
                            @if($indent->required_by && $indent->required_by->isPast() && !$indent->is_draft)
                                <span style="font-size: 0.7rem; color: var(--danger); font-weight: 700;">Overdue</span>
                            @endif
                        </td>

                        {{-- Current Holder --}}
                        <td style="white-space: nowrap;">
                            @if($indent->is_draft)
                                <span style="color: var(--text-muted); font-style: italic;">Creator (Draft)</span>
                            @else
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    {{ $indent->currentHolder?->name ?? $indent->current_holder_cpf }}
                                </div>
                                <span style="font-family: monospace; font-size: 0.72rem; background: var(--surface-subtle); color: var(--text-muted); padding: 1px 6px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                    {{ $indent->current_holder_cpf }}
                                </span>
                                @if(Auth::user()->cpf === $indent->current_holder_cpf)
                                    <span style="font-size: 0.7rem; background: var(--success-bg); color: var(--success); font-weight: 700; padding: 1px 6px; border-radius: var(--radius-sm); margin-left: 4px; border: 1px solid var(--success-border);">
                                        With you
                                    </span>
                                @endif
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                @if(!$indent->is_draft && Auth::user()->cpf === $indent->current_holder_cpf && $indent->status !== \App\Enums\IndentStatus::CLOSED)
                                    <a href="{{ route('indents.show', $indent) }}#forward-form-section"
                                       class="btn btn-secondary btn-sm">
                                        Forward
                                    </a>
                                @endif

                                @if($indent->is_draft && Auth::user()->cpf === $indent->creator_cpf)
                                    <a href="{{ route('indents.edit', $indent) }}"
                                       class="btn btn-outline btn-sm"
                                       style="color: var(--warning); border-color: var(--warning-border); background: var(--warning-bg);">
                                        Edit
                                    </a>

                                    <form action="{{ route('indents.destroy', $indent) }}"
                                          method="POST"
                                          style="display: inline; margin: 0;"
                                          onsubmit="return confirm('Are you sure you want to permanently delete draft indent {{ $indent->number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline btn-sm"
                                                style="color: var(--danger); border-color: var(--danger-border); background: var(--danger-bg);">
                                            Delete
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('indents.show', $indent) }}"
                                   class="btn btn-outline btn-sm">
                                    View
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 48px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">
                                No indents found
                            </div>
                            <p style="font-size: 0.85rem; max-width: 420px; margin: 0 auto 16px;">
                                @if($search !== '')
                                    No records matched your search query "{{ $search }}". Try adjusting your filters.
                                @elseif($tab === 'drafts')
                                    You have no saved draft indents. Click below to start drafting a new requisition.
                                @elseif(in_array($tab, ['with-me', 'holder']))
                                    There are currently no active indents assigned to you.
                                @else
                                    You have not created any civil indents yet. Click below to create your first requisition.
                                @endif
                            </p>
                            <a href="{{ route('indents.create') }}" class="btn btn-secondary btn-sm">
                                Raise New Indent
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($indents->hasPages())
        <div style="padding: 14px 20px; background: var(--surface-subtle); border-top: 1px solid var(--border); width: 100%; box-sizing: border-box;">
            {{ $indents->links('vendor.pagination.default', ['itemLabel' => 'indents']) }}
        </div>
    @endif
</div>
@endsection

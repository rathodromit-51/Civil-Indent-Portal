@extends('layouts.app')

@section('title', 'Indents — Civil Indent Portal')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-color);
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .tab-bar {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .tab-link {
        padding: 10px 18px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-muted);
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s ease;
    }

    .tab-link:hover {
        color: var(--primary);
    }

    .tab-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .tab-badge {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
    }

    .tab-link.active .tab-badge {
        background: #eff6ff;
        color: var(--primary);
        font-weight: 700;
    }

    .filter-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .badge-draft {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .badge-open {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .badge-assigned {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .badge-inprogress {
        background: #f3e8ff;
        color: #6b21a8;
        border: 1px solid #d8b4fe;
    }

    .badge-closed {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .badge-priority-urgent {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .badge-priority-high {
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    .badge-priority-medium {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .badge-priority-low {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            @if(in_array($tab, ['with-me', 'holder']))
                📥 Inbox — With Me
            @else
                📋 Civil Work Indents
            @endif
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem;">
            @if(in_array($tab, ['with-me', 'holder']))
                Indents currently assigned to you awaiting your review, forward action, or execution.
            @else
                Manage and track civil requisition lifecycles, draft submissions, approvals, and on-site execution.
            @endif
        </p>
    </div>
    <a href="{{ route('indents.create') }}" class="btn btn-primary" style="padding: 10px 20px; font-weight: 600;">
        ➕ Raise New Indent
    </a>
</div>

{{-- Top Statistics Row --}}
<div class="stats-row">
    <div class="stat-card">
        <div>
            <div class="stat-label">Created by Me</div>
            <div class="stat-val">{{ $myCount }}</div>
        </div>
        <div style="font-size: 1.8rem;">✍️</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">My Drafts (Editable)</div>
            <div class="stat-val" style="color: #b45309;">{{ $myDraftsCount }}</div>
        </div>
        <div style="font-size: 1.8rem;">📝</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">My Submitted Indents</div>
            <div class="stat-val" style="color: #047857;">{{ $mySubmittedCount }}</div>
        </div>
        <div style="font-size: 1.8rem;">🚀</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Currently Assigned to Me</div>
            <div class="stat-val" style="color: #1d4ed8;">{{ $holderCount }}</div>
        </div>
        <div style="font-size: 1.8rem;">📬</div>
    </div>
</div>

{{-- View Filter Tabs --}}
<div class="tab-bar">
    <a href="{{ route('indents.index', ['tab' => 'with-me']) }}"
       class="tab-link {{ in_array($tab, ['with-me', 'holder']) ? 'active' : '' }}">
        📥 With Me
        <span class="tab-badge" style="{{ $withMeCount > 0 ? 'background: #eff6ff; color: #2563eb; font-weight: 700;' : '' }}">{{ $withMeCount }}</span>
    </a>
    <a href="{{ route('indents.index', ['tab' => 'my']) }}"
       class="tab-link {{ $tab === 'my' ? 'active' : '' }}">
        ✍️ Created by Me
        <span class="tab-badge">{{ $myCount }}</span>
    </a>
    <a href="{{ route('indents.index', ['tab' => 'drafts']) }}"
       class="tab-link {{ $tab === 'drafts' ? 'active' : '' }}">
        📝 My Drafts
        <span class="tab-badge">{{ $myDraftsCount }}</span>
    </a>
    @if($flaggedCount > 0)
        <a href="{{ route('indents.index', ['tab' => 'flagged']) }}"
           class="tab-link {{ $tab === 'flagged' ? 'active' : '' }}" style="color: #dc2626;">
            🚩 Flagged ({{ $flaggedCount }})
        </a>
    @endif
    <a href="{{ route('indents.index', ['tab' => 'all']) }}"
       class="tab-link {{ $tab === 'all' ? 'active' : '' }}">
        🌐 All Visible Indents
    </a>
</div>

{{-- Search and Filter Form --}}
<div class="filter-card">
    <form action="{{ route('indents.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div style="flex: 1; min-width: 220px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Search Number / Title / Installation
            </label>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Search e.g. CI-2026-0001, GGS-1, Maintenance..."
                   style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem;">
        </div>

        <div style="min-width: 160px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Status
            </label>
            <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; background: white;">
                <option value="all">All Statuses</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ $status === $st->value ? 'selected' : '' }}>
                        {{ $st->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 160px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">
                Priority
            </label>
            <select name="priority" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.875rem; background: white;">
                <option value="all">All Priorities</option>
                @foreach($priorities as $pr)
                    <option value="{{ $pr->value }}" {{ $priority === $pr->value ? 'selected' : '' }}>
                        {{ $pr->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                🔍 Filter
            </button>
            @if($search !== '' || ($status && $status !== 'all') || ($priority && $priority !== 'all'))
                <a href="{{ route('indents.index', ['tab' => $tab]) }}" class="btn btn-outline" style="padding: 8px 14px;">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Indents Table Card --}}
<div class="card" style="padding: 0; overflow: hidden; background: white; border: 1px solid var(--border-color);">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <th style="padding: 12px 18px; font-weight: 600;">Indent #</th>
                    <th style="padding: 12px 18px; font-weight: 600;">Work Title & Category</th>
                    <th style="padding: 12px 18px; font-weight: 600;">Installation & Area</th>
                    <th style="padding: 12px 18px; font-weight: 600;">Priority</th>
                    <th style="padding: 12px 18px; font-weight: 600;">State & Status</th>
                    <th style="padding: 12px 18px; font-weight: 600;">Required By</th>
                    <th style="padding: 12px 18px; font-weight: 600;">Holder</th>
                    <th style="padding: 12px 18px; font-weight: 600; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($indents as $indent)
                    <tr style="border-bottom: 1px solid #f1f5f9; background: white; vertical-align: middle;">
                        {{-- Number --}}
                        <td style="padding: 14px 18px; white-space: nowrap;">
                            <a href="{{ route('indents.show', $indent) }}" style="font-family: monospace; font-weight: 700; color: var(--primary); text-decoration: none; font-size: 0.9rem;">
                                {{ $indent->number }}
                            </a>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">
                                {{ $indent->created_at->format('d M Y') }}
                            </div>
                        </td>

                        {{-- Title & Work Type --}}
                        <td style="padding: 14px 18px; max-width: 260px;">
                            <a href="{{ route('indents.show', $indent) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $indent->title }}
                            </a>
                            <div style="font-size: 0.775rem; color: #64748b; margin-top: 2px;">
                                🏷️ {{ $indent->work_type }}
                            </div>
                        </td>

                        {{-- Installation & Area --}}
                        <td style="padding: 14px 18px; font-size: 0.825rem; white-space: nowrap;">
                            <div style="font-weight: 600; color: #1e293b;">
                                🏭 {{ $indent->installation?->name ?? '—' }}
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                🗺️ {{ $indent->installation?->area?->name ?? '—' }}
                            </div>
                        </td>

                        {{-- Priority --}}
                        <td style="padding: 14px 18px; white-space: nowrap;">
                            @php
                                $priorityClass = match($indent->priority->value ?? '') {
                                    'Urgent' => 'badge-priority-urgent',
                                    'High' => 'badge-priority-high',
                                    'Low' => 'badge-priority-low',
                                    default => 'badge-priority-medium',
                                };
                            @endphp
                            <span class="badge {{ $priorityClass }}">
                                {{ $indent->priority->value ?? 'Medium' }}
                            </span>
                        </td>

                        {{-- State & Status --}}
                        <td style="padding: 14px 18px; white-space: nowrap;">
                            @if($indent->is_draft)
                                <span class="badge badge-draft">
                                    📝 Draft (Editable)
                                </span>
                            @else
                                @php
                                    $statusClass = match($indent->status->value ?? '') {
                                        'Open' => 'badge-open',
                                        'Assigned' => 'badge-assigned',
                                        'InProgress' => 'badge-inprogress',
                                        'Closed' => 'badge-closed',
                                        default => 'badge-open',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    🚀 {{ $indent->status->value ?? 'Open' }}
                                </span>
                                @if($indent->is_rejected)
                                    <div style="margin-top: 4px;">
                                        <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.7rem;">
                                            🚩 Flagged (Rejected)
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </td>

                        {{-- Required By --}}
                        <td style="padding: 14px 18px; white-space: nowrap; font-size: 0.825rem;">
                            <div style="font-weight: 600; color: #1e293b;">
                                {{ $indent->required_by ? $indent->required_by->format('d M Y') : '—' }}
                            </div>
                            @if($indent->required_by && $indent->required_by->isPast() && !$indent->is_draft)
                                <span style="font-size: 0.7rem; color: #b91c1c; font-weight: 600;">Overdue</span>
                            @endif
                        </td>

                        {{-- Current Holder --}}
                        <td style="padding: 14px 18px; font-size: 0.825rem; white-space: nowrap;">
                            @if($indent->is_draft)
                                <div style="color: #64748b; font-style: italic;">Creator (Draft)</div>
                            @else
                                <div style="font-weight: 600; color: #0f172a;">
                                    {{ $indent->currentHolder?->name ?? $indent->current_holder_cpf }}
                                </div>
                                <span style="font-family: monospace; font-size: 0.725rem; background: #e2e8f0; color: #1e293b; padding: 1px 6px; border-radius: 4px;">
                                    {{ $indent->current_holder_cpf }}
                                </span>
                                @if(Auth::user()->cpf === $indent->current_holder_cpf)
                                    <span style="font-size: 0.7rem; background: #dcfce7; color: #15803d; font-weight: 700; padding: 1px 6px; border-radius: 4px; margin-left: 4px;">
                                        With you
                                    </span>
                                @endif
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td style="padding: 14px 18px; text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                @if(!$indent->is_draft && Auth::user()->cpf === $indent->current_holder_cpf && $indent->status !== \App\Enums\IndentStatus::CLOSED)
                                    <a href="{{ route('indents.show', $indent) }}#forward-form-section"
                                       class="btn btn-primary"
                                       style="padding: 5px 12px; font-size: 0.775rem; font-weight: 600;">
                                        ⏩ Forward
                                    </a>
                                @endif

                                @if($indent->is_draft && Auth::user()->cpf === $indent->creator_cpf)
                                    <a href="{{ route('indents.edit', $indent) }}"
                                       class="btn btn-outline"
                                       style="padding: 5px 12px; font-size: 0.775rem; color: #b45309; border-color: #fde68a; background: #fffbeb;">
                                        ✏️ Edit Draft
                                    </a>

                                    <form action="{{ route('indents.destroy', $indent) }}"
                                          method="POST"
                                          style="display: inline; margin: 0;"
                                          onsubmit="return confirm('Are you sure you want to permanently delete draft indent {{ $indent->number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline"
                                                style="padding: 5px 10px; font-size: 0.775rem; color: #dc2626; border-color: #fecaca; background: #fef2f2;">
                                            🗑️
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('indents.show', $indent) }}"
                                   class="btn btn-outline"
                                   style="padding: 5px 12px; font-size: 0.775rem; background: white;">
                                    👁️ View
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 48px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 2.5rem; margin-bottom: 12px;">📭</div>
                            <div style="font-size: 1rem; font-weight: 600; color: #334155; margin-bottom: 6px;">
                                No indents found
                            </div>
                            <p style="font-size: 0.85rem; max-width: 400px; margin: 0 auto 16px;">
                                @if($search !== '')
                                    No records matched your search query "{{ $search }}". Try adjusting your filters.
                                @elseif($tab === 'drafts')
                                    You have no saved draft indents. Click below to start drafting a new requisition.
                                @elseif($tab === 'holder')
                                    There are currently no active indents assigned to you.
                                @else
                                    You have not created any civil indents yet. Click below to create your first requisition.
                                @endif
                            </p>
                            <a href="{{ route('indents.create') }}" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.85rem;">
                                ➕ Raise New Indent
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($indents->hasPages())
        <div style="padding: 14px 20px; background: #f8fafc; border-top: 1px solid var(--border-color); width: 100%; box-sizing: border-box;">
            {{ $indents->links('vendor.pagination.default', ['itemLabel' => 'indents']) }}
        </div>
    @endif
</div>
@endsection

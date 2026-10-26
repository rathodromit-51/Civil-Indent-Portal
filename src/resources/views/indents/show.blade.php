@extends('layouts.app')

@section('title', 'Indent ' . $indent->number . ' — Civil Indent Portal')

@push('styles')
<style>
    .show-wrapper {
        max-width: 960px;
        margin: 0 auto 40px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 0.8rem;
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

    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
    }

    .meta-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .meta-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-color);
    }

    .timeline {
        position: relative;
        padding-left: 28px;
        margin-top: 16px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 8px;
        width: 2px;
        background: #e2e8f0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-dot {
        position: absolute;
        left: -28px;
        top: 4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #2563eb;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 1px #cbd5e1;
    }
</style>
@endpush

@section('content')
<div class="show-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
            <a href="{{ route('indents.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
                &larr; Back to Indents
            </a>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <h1 style="font-size: 1.75rem; font-weight: 700; margin: 0; color: #0f172a;">
                    {{ $indent->title }}
                </h1>
                <span style="font-family: monospace; font-size: 1.1rem; font-weight: 700; color: var(--primary); background: #eff6ff; padding: 2px 10px; border-radius: 6px; border: 1px solid #bfdbfe;">
                    {{ $indent->number }}
                </span>
            </div>
        </div>

        <div style="display: flex; gap: 8px; align-items: center;">
            @if($indent->is_draft && Auth::user()->cpf === $indent->creator_cpf)
                <a href="{{ route('indents.edit', $indent) }}"
                   class="btn btn-outline"
                   style="color: #b45309; border-color: #fde68a; background: #fffbeb; font-weight: 600;">
                    ✏️ Edit Draft
                </a>

                <form action="{{ route('indents.destroy', $indent) }}"
                      method="POST"
                      style="display: inline; margin: 0;"
                      onsubmit="return confirm('Permanently delete draft indent {{ $indent->number }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline" style="color: #dc2626; border-color: #fecaca; background: #fef2f2;">
                        🗑️ Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Meta Grid --}}
    <div class="meta-grid">
        <div class="meta-item">
            <span class="meta-label">Lifecycle Status</span>
            <span class="meta-value">
                @if($indent->is_draft)
                    <span class="badge badge-draft">📝 Draft (Editable)</span>
                @else
                    <span class="badge badge-open">🚀 {{ $indent->status->value ?? 'Open' }}</span>
                @endif
            </span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Priority</span>
            <span class="meta-value">
                @php
                    $priorityClass = match($indent->priority->value ?? '') {
                        'Urgent' => 'badge-priority-urgent',
                        'High' => 'badge-priority-high',
                        'Low' => 'badge-priority-low',
                        default => 'badge-priority-medium',
                    };
                @endphp
                <span class="badge {{ $priorityClass }}">
                    ⚡ {{ $indent->priority->value ?? 'Medium' }}
                </span>
            </span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Work Type</span>
            <span class="meta-value">🏷️ {{ $indent->work_type }}</span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Target Installation</span>
            <span class="meta-value">🏭 {{ $indent->installation?->name ?? '—' }} ({{ $indent->installation?->code }})</span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Operational Area</span>
            <span class="meta-value">🗺️ {{ $indent->installation?->area?->name ?? '—' }}</span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Required By Date</span>
            <span class="meta-value">📅 {{ $indent->required_by ? $indent->required_by->format('d M Y') : '—' }}</span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Creator</span>
            <span class="meta-value">
                👤 {{ $indent->creator?->name ?? $indent->creator_cpf }}
                <span style="font-family: monospace; font-size: 0.75rem; color: #64748b;">({{ $indent->creator_cpf }})</span>
            </span>
        </div>

        <div class="meta-item">
            <span class="meta-label">Current Holder</span>
            <span class="meta-value">
                @if($indent->is_draft)
                    <span style="color: #64748b; font-style: italic;">Creator (Draft)</span>
                @else
                    👤 {{ $indent->currentHolder?->name ?? $indent->current_holder_cpf }}
                    <span style="font-family: monospace; font-size: 0.75rem; color: #64748b;">({{ $indent->current_holder_cpf }})</span>
                @endif
            </span>
        </div>
    </div>

    {{-- Technical Scope & Specifications Card --}}
    <div class="card" style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
        <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            📄 Technical Scope of Work
        </h2>
        @if($indent->description)
            <div style="font-size: 0.9rem; line-height: 1.6; color: #334155; white-space: pre-wrap;">{{ $indent->description }}</div>
        @else
            <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">No technical scope provided.</div>
        @endif
    </div>

    {{-- Operational Justification Card --}}
    <div class="card" style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
        <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            💡 Operational & Safety Justification
        </h2>
        @if($indent->justification)
            <div style="font-size: 0.9rem; line-height: 1.6; color: #334155; white-space: pre-wrap;">{{ $indent->justification }}</div>
        @else
            <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">No operational justification specified.</div>
        @endif
    </div>

    {{-- Workflow Movements History Card --}}
    <div class="card" style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; margin: 0;">
                📜 Workflow Movements & Forwarding History
            </h2>
            <span style="font-size: 0.75rem; background: #e2e8f0; color: #334155; padding: 2px 8px; border-radius: 9999px; font-weight: 600;">
                {{ $indent->movements->count() }} Transitions
            </span>
        </div>

        @if($indent->movements->isNotEmpty())
            <div class="timeline">
                @foreach($indent->movements as $mvt)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
                                <span style="font-size: 0.8rem; font-weight: 700; color: #047857; background: #ecfdf5; padding: 2px 8px; border-radius: 4px; border: 1px solid #a7f3d0;">
                                    {{ strtoupper($mvt->action) }}
                                </span>
                                <span style="font-size: 0.75rem; color: #64748b;">
                                    {{ $mvt->created_at->format('d M Y, H:i') }} ({{ $mvt->created_at->diffForHumans() }})
                                </span>
                            </div>
                            <div style="font-size: 0.85rem; color: #1e293b; margin-bottom: 6px;">
                                <strong>From:</strong> {{ $mvt->fromUser?->name ?? $mvt->from }} (<code>{{ $mvt->from }}</code>)
                                &rarr;
                                <strong>To:</strong> {{ $mvt->toUser?->name ?? $mvt->to }} (<code>{{ $mvt->to }}</code>)
                            </div>
                            @if($mvt->remark)
                                <div style="font-size: 0.825rem; color: #475569; background: white; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; margin-top: 6px;">
                                    💬 "{{ $mvt->remark }}"
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="color: #64748b; font-size: 0.85rem; font-style: italic; padding: 12px 0;">
                @if($indent->is_draft)
                    This indent is still in draft state. The first forward movement will be recorded when submitted.
                @else
                    No movement records logged.
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

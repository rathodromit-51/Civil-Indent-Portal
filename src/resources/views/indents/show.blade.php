@extends('layouts.app')

@section('title', 'Indent ' . $indent->number . ' — Civil Indent Portal')

@push('styles')
    <style>
        .show-wrapper {
            max-width: 1200px;
            margin: 0 auto 40px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            background: var(--surface-subtle);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
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
            background: var(--border);
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
            background: var(--primary);
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 1px var(--border);
        }
    </style>
@endpush

@section('content')
    <div class="show-wrapper">
        <div
            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <a href="{{ route('indents.index') }}"
                    style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
                    &larr; Back to Indents
                </a>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <h1 style="font-size: 1.75rem; font-weight: 700; margin: 0; color: #0f172a;">
                        {{ $indent->title }}
                    </h1>
                    <span
                        style="font-family: monospace; font-size: 1rem; font-weight: 700; color: var(--primary); background: var(--surface-subtle); padding: 2px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        {{ $indent->number }}
                    </span>
                </div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                @if($indent->is_draft && Auth::user()->cpf === $indent->creator_cpf)
                    <a href="{{ route('indents.edit', $indent) }}" class="btn btn-outline"
                        style="color: #b45309; border-color: #fde68a; background: #fffbeb; font-weight: 600;">
                        Edit Draft
                    </a>

                    <form action="{{ route('indents.destroy', $indent) }}" method="POST" style="display: inline; margin: 0;"
                        onsubmit="return confirm('Permanently delete draft indent {{ $indent->number }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline"
                            style="color: #dc2626; border-color: #fecaca; background: #fef2f2;">
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Rejection Flag Alert Banner --}}
        @if($indent->is_rejected)
            @php $rejectionMvt = $indent->latestRejectionMovement(); @endphp
            <div
                style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 10px; padding: 18px 22px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.08);">
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div style="flex: 1;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <strong style="color: #991b1b; font-size: 1.05rem;">
                                Indent Flagged & Rejected by
                                {{ $rejectionMvt?->fromUser?->name ?? $rejectionMvt?->from ?? 'Management Authority' }}
                                @if($rRole = $rejectionMvt?->fromUser?->roleAssignment?->role?->name)
                                    <span class="badge"
                                        style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-size: 0.75rem; margin-left: 6px;">
                                        {{ $rRole }}
                                    </span>
                                @endif
                            </strong>
                            @if($rejectionMvt)
                                <span style="font-size: 0.8rem; color: #b91c1c; font-weight: 600;">
                                    {{ $rejectionMvt->created_at->format('d M Y, H:i') }}
                                    ({{ $rejectionMvt->created_at->diffForHumans() }})
                                </span>
                            @endif
                        </div>
                        @if($rejectionMvt?->remark)
                            <div
                                style="margin-top: 10px; background: white; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; color: #7f1d1d; font-size: 0.9rem;">
                                <strong>Mandatory Rejection Reason:</strong> {{ $rejectionMvt->remark }}
                            </div>
                        @endif
                        <p style="color: #b91c1c; font-size: 0.8rem; margin-top: 8px; margin-bottom: 0;">
                            This indent remains active and open for rectification. Please review the comments, update technical
                            scope/attachments if needed, and resubmit.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Meta Grid --}}
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">Lifecycle Status</span>
                <span class="meta-value" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <x-status-badge :status="$indent->status" :isDraft="$indent->is_draft"
                        :isRejected="$indent->is_rejected" />
                </span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Priority</span>
                <span class="meta-value">
                    <x-priority-badge :priority="$indent->priority" />
                </span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Work Type</span>
                <span class="meta-value">{{ $indent->work_type }}</span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Target Installation</span>
                <span class="meta-value">{{ $indent->installation?->name ?? '—' }}
                    ({{ $indent->installation?->code }})</span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Operational Area</span>
                <span class="meta-value">{{ $indent->installation?->area?->name ?? '—' }}</span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Required By Date</span>
                <span class="meta-value">{{ $indent->required_by ? $indent->required_by->format('d M Y') : '—' }}</span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Creator</span>
                <span class="meta-value" style="display: flex; flex-direction: column; gap: 4px;">
                    <div>
                        {{ $indent->creator?->name ?? $indent->creator_cpf }}
                        <span
                            style="font-family: monospace; font-size: 0.75rem; color: #64748b;">({{ $indent->creator_cpf }})</span>
                    </div>
                    @if($cRole = ($indent->creator?->roleAssignment?->role?->name ?? $indent->creator?->designation))
                        <span class="badge"
                            style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.72rem; align-self: flex-start; padding: 1px 8px;">
                            {{ $cRole }}
                        </span>
                    @endif
                </span>
            </div>

            <div class="meta-item">
                <span class="meta-label">Current Holder</span>
                <span class="meta-value" style="display: flex; flex-direction: column; gap: 4px;">
                    @if($indent->is_draft)
                        <span style="color: #64748b; font-style: italic;">Creator (Draft)</span>
                    @else
                        <div>
                            {{ $indent->currentHolder?->name ?? $indent->current_holder_cpf }}
                            <span
                                style="font-family: monospace; font-size: 0.75rem; color: #64748b;">({{ $indent->current_holder_cpf }})</span>
                        </div>
                        @if($hRole = ($indent->currentHolder?->roleAssignment?->role?->name ?? $indent->currentHolder?->designation))
                            <span class="badge"
                                style="background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 0.72rem; align-self: flex-start; padding: 1px 8px;">
                                {{ $hRole }}
                            </span>
                        @endif
                    @endif
                </span>
            </div>
        </div>

        {{-- Technical Scope & Specifications Card --}}
        <div class="card"
            style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 12px;">
                Technical Scope of Work
            </h2>
            @if($indent->description)
                <div style="font-size: 0.9rem; line-height: 1.6; color: #334155; white-space: pre-wrap;">
                    {{ $indent->description }}
                </div>
            @else
                <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">No technical scope provided.</div>
            @endif
        </div>

        {{-- Operational Justification Card --}}
        <div class="card"
            style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 12px;">
                Operational & Safety Justification
            </h2>
            @if($indent->justification)
                <div style="font-size: 0.9rem; line-height: 1.6; color: #334155; white-space: pre-wrap;">
                    {{ $indent->justification }}
                </div>
            @else
                <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">No operational justification specified.
                </div>
            @endif
        </div>

        {{-- Attachments Card --}}
        <div class="card"
            style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;">
                    Attached Documents & Drawings
                </h2>
                <span
                    style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 10px; border-radius: 9999px; font-weight: 700;">
                    {{ $indent->attachments->count() }} Files
                </span>
            </div>

            @if($indent->attachments->isNotEmpty())
                <div style="display: grid; gap: 10px;">
                    @foreach($indent->attachments as $att)
                        @php
                            $ext = strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION) ?: 'FILE');
                        @endphp
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 8px; flex-wrap: wrap; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                <span
                                    style="font-size: 0.72rem; font-weight: 700; font-family: monospace; background: #e2e8f0; color: #334155; padding: 4px 8px; border-radius: 4px; letter-spacing: 0.05em;">{{ $ext }}</span>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; color: #0f172a; font-size: 0.9rem; word-break: break-all;">
                                        {{ $att->original_name }}
                                    </div>
                                    <div
                                        style="font-size: 0.775rem; color: #64748b; display: flex; gap: 12px; margin-top: 2px; flex-wrap: wrap;">
                                        <span><strong>Size:</strong> {{ $att->formatted_size }}</span>
                                        <span>&bull;</span>
                                        <span><strong>Uploaded by:</strong> {{ $att->uploaderUser?->name ?? $att->uploader }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $att->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a href="{{ route('attachments.download', $att) }}" class="btn btn-outline"
                                    style="background: #ffffff; color: var(--primary); border-color: #93c5fd; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    Download
                                </a>

                                @can('delete', $att)
                                    <form action="{{ route('attachments.destroy', $att) }}" method="POST"
                                        style="display: inline; margin: 0;"
                                        onsubmit="return confirm('Delete attachment \'{{ $att->original_name }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline"
                                            style="color: #dc2626; border-color: #fecaca; background: #ffffff; font-size: 0.8rem; padding: 6px 12px;">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="color: #64748b; font-size: 0.85rem; font-style: italic; padding: 12px 0;">
                    No attachments uploaded for this indent.
                </div>
            @endif

            @can('addAttachment', $indent)
                <div style="margin-top: 20px; padding-top: 18px; border-top: 1px dashed var(--border-color);">
                    <form action="{{ route('indents.attachments.store', $indent) }}" method="POST" enctype="multipart/form-data"
                        style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 20px; border-radius: 8px;">
                        @csrf
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                            <span style="font-size: 0.875rem; font-weight: 700; color: #1e293b;">
                                Attach Files to this Indent
                            </span>
                            <span style="font-size: 0.75rem; color: #64748b;">
                                Max 100 MB per file &bull; Allowed: Images, PDF, CAD (DWG/DXF), Office, ZIP, KML
                            </span>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <input type="file" name="attachments[]" id="show-attachments-input" multiple required
                                class="form-control"
                                style="max-width: 420px; background: white; padding: 8px 12px; font-size: 0.85rem;"
                                accept=".jpg,.jpeg,.png,.gif,.svg,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.dwg,.dxf,.kml">

                            <button type="submit" class="btn btn-primary"
                                style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600;">
                                Upload & Attach
                            </button>
                        </div>

                        @error('attachments')
                            <div style="color: #dc2626; font-size: 0.775rem; margin-top: 6px; font-weight: 600;">
                                {{ $message }}
                            </div>
                        @enderror

                        @error('attachments.*')
                            <div style="color: #dc2626; font-size: 0.775rem; margin-top: 6px; font-weight: 600;">
                                {{ $message }}
                            </div>
                        @enderror
                    </form>
                </div>
            @endcan
        </div>

        {{-- Civil Engineer Work Execution Panel (CE only) --}}
        @can('updateStatus', $indent)
            <div id="ce-execution-panel" class="card"
                style="background: #f0fdf4; border: 2px solid #16a34a; padding: 24px; border-radius: 10px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.1);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #bbf7d0; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: #166534; margin: 0;">
                            Site Execution Panel (Civil Engineer)
                        </h2>
                        <p style="font-size: 0.825rem; color: #15803d; margin-top: 4px; margin-bottom: 0;">
                            You are the assigned on-site Civil Engineer (<strong>{{ Auth::user()->name }}</strong>). Manage
                            execution milestones below.
                        </p>
                    </div>
                    <span class="badge"
                        style="background: #16a34a; color: white; padding: 4px 12px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                        Current State:
                        {{ method_exists($indent->status, 'label') ? $indent->status->label() : ($indent->status->value ?? $indent->status ?? 'Open') }}
                    </span>
                </div>

                <form action="{{ route('indents.status.update', $indent) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #14532d; margin-bottom: 8px;">
                            Select Next Milestone Action <span style="color: #dc2626;">*</span>:
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                            @if($indent->status === \App\Enums\IndentStatus::OPEN)
                                <label
                                    style="display: flex; align-items: center; gap: 10px; background: white; border: 2px solid #86efac; border-radius: 8px; padding: 12px 16px; cursor: pointer;">
                                    <input type="radio" name="status" value="Assigned" checked
                                        style="accent-color: #16a34a; width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; color: #166534; font-size: 0.9rem;">1. Accept Indent Work
                                        </div>
                                        <div style="font-size: 0.75rem; color: #4b5563;">Accept task and take site ownership</div>
                                    </div>
                                </label>
                            @endif

                            @if(in_array($indent->status, [\App\Enums\IndentStatus::OPEN, \App\Enums\IndentStatus::ASSIGNED]))
                                <label
                                    style="display: flex; align-items: center; gap: 10px; background: white; border: 2px solid #93c5fd; border-radius: 8px; padding: 12px 16px; cursor: pointer;">
                                    <input type="radio" name="status" value="InProgress" {{ $indent->status === \App\Enums\IndentStatus::ASSIGNED ? 'checked' : '' }}
                                        style="accent-color: #2563eb; width: 18px; height: 18px;">
                                    <div>
                                        <div style="font-weight: 700; color: #1e40af; font-size: 0.9rem;">2. Start Physical Work
                                        </div>
                                        <div style="font-size: 0.75rem; color: #4b5563;">Mobilize contractor & materials on site
                                        </div>
                                    </div>
                                </label>
                            @endif

                            <label
                                style="display: flex; align-items: center; gap: 10px; background: white; border: 2px solid #d8b4fe; border-radius: 8px; padding: 12px 16px; cursor: pointer;">
                                <input type="radio" name="status" value="Closed" {{ $indent->status === \App\Enums\IndentStatus::IN_PROGRESS ? 'checked' : '' }}
                                    style="accent-color: #7c3aed; width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 700; color: #6b21a8; font-size: 0.9rem;">3. Complete & Handover
                                    </div>
                                    <div style="font-size: 0.75rem; color: #4b5563;">Finish physical work and close indent</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #14532d; margin-bottom: 6px;">
                            Work Progress / Site Execution Remarks:
                        </label>
                        <textarea name="remark" rows="3" class="form-control"
                            placeholder="Enter site execution notes, measurements, materials used, inspection results, or completion details..."
                            style="width: 100%; padding: 10px 14px; border: 1px solid #86efac; border-radius: 6px; font-size: 0.875rem; background: white;"></textarea>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #14532d; margin-bottom: 6px;">
                            Attach Inspection Photos, Measurement Sheets or Completion Reports:
                        </label>
                        <input type="file" name="attachments[]" multiple class="form-control"
                            style="width: 100%; padding: 8px 12px; border: 1px solid #86efac; border-radius: 6px; font-size: 0.85rem; background: white;">
                    </div>

                    <button type="submit" class="btn btn-primary"
                        style="background: #16a34a; border-color: #15803d; color: white; padding: 10px 24px; font-size: 0.9rem; font-weight: 700;">
                        Update Execution Status
                    </button>
                </form>
            </div>
        @endcan

        {{-- Workflow Forwarding & Return Action Card (Only current holder can forward) --}}
        @can('forward', $indent)
            <div id="forward-form-section" class="card"
                style="background: white; border: 2px solid #2563eb; padding: 24px; border-radius: 10px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.08);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;">
                            Workflow Actions: Forward or Return
                        </h2>
                        <p style="font-size: 0.825rem; color: #64748b; margin-top: 4px; margin-bottom: 0;">
                            You currently hold this indent (<strong>{{ Auth::user()->name }}</strong>). Choose the next
                            recipient or return to an earlier participant.
                        </p>
                    </div>
                    <div style="display: flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                        <button type="button" id="tab-btn-forward" onclick="switchActionTab('Forward')" class="btn btn-primary"
                            style="padding: 6px 14px; font-size: 0.825rem; font-weight: 600;">
                            Forward Indent
                        </button>
                        @if(isset($earlierParticipants) && $earlierParticipants->isNotEmpty())
                            <button type="button" id="tab-btn-return" onclick="switchActionTab('Return')" class="btn btn-outline"
                                style="padding: 6px 14px; font-size: 0.825rem; font-weight: 600; border-color: transparent;">
                                Return to Earlier ({{ $earlierParticipants->count() }})
                            </button>
                        @endif
                    </div>
                </div>

                <form action="{{ route('indents.forward', $indent) }}" method="POST" id="workflow-action-form">
                    @csrf
                    <input type="hidden" name="action" id="action-input" value="Forward">
                    <input type="hidden" name="recipient_cpf" id="recipient-cpf-input" required
                        value="{{ old('recipient_cpf') }}">

                    {{-- Forward Mode Container --}}
                    <div id="forward-mode-container">
                        @php
                            $nextRole = $indent->getNextExpectedRole();
                        @endphp
                        @if($nextRole)
                            <div
                                style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 0.85rem; color: #1e40af; font-weight: 600;">Next in Workflow
                                        Sequence:</span>
                                    <span class="badge"
                                        style="background: #2563eb; color: #ffffff; padding: 3px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.8rem;">
                                        {{ $nextRole }}
                                    </span>
                                </div>
                                <span style="font-size: 0.75rem; color: #64748b; font-family: monospace;">
                                    USER &rarr; IM &rarr; SAM &rarr; SM &rarr; HES &rarr; LMW &rarr; ICO &rarr; CE
                                </span>
                            </div>
                        @endif

                        <label
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                            Select Recipient (Search by CPF, Name, or Role) <span style="color: #dc2626;">*</span>
                        </label>

                        {{-- Selected Recipient Card (shown when recipient chosen) --}}
                        <div id="selected-recipient-card"
                            style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div>
                                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">
                                        <span id="selected-recipient-name"></span>
                                        <span
                                            style="font-family: monospace; font-size: 0.8rem; background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 4px; margin-left: 6px;"
                                            id="selected-recipient-cpf"></span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #3b82f6; margin-top: 2px;">
                                        <span class="badge"
                                            style="background: #2563eb; color: #ffffff; padding: 1px 8px; font-size: 0.72rem; margin-right: 6px;"
                                            id="selected-recipient-role"></span>
                                        <span id="selected-recipient-scope"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="clearSelectedRecipient()" class="btn btn-outline"
                                style="padding: 4px 10px; font-size: 0.775rem; background: white; border-color: #93c5fd; color: #1e40af;">
                                Change
                            </button>
                        </div>

                        {{-- Search Input with Dropdown --}}
                        <div id="search-input-wrapper" style="position: relative; margin-bottom: 16px;">
                            <input type="text" id="recipient-search-input"
                                placeholder="{{ $nextRole ? "Search for assigned {$nextRole} by name or CPF..." : "Type name or CPF (e.g. Pooja, IM001, Anita, SAM001)..." }}"
                                autocomplete="off"
                                style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                            <div id="search-spinner"
                                style="display: none; position: absolute; right: 12px; top: 12px; font-size: 0.85rem; color: #64748b;">
                                Searching...
                            </div>

                            {{-- Dropdown Results Menu --}}
                            <div id="search-results-dropdown"
                                style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 240px; overflow-y: auto; z-index: 50; margin-top: 4px;">
                            </div>
                        </div>

                        {{-- Forward with Rejection Checkbox (Strictly Management Authorities: IM, SAM, SM) --}}
                        @can('reject', $indent)
                            <div
                                style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="is_rejected" id="is-rejected-checkbox" value="1"
                                        onchange="toggleRejectionReason(this.checked)"
                                        style="width: 18px; height: 18px; margin-top: 2px; accent-color: #dc2626;">
                                    <div>
                                        <strong style="color: #92400e; font-size: 0.9rem;">Forward with Rejection /
                                            Concerns</strong>
                                        <p style="font-size: 0.8rem; color: #b45309; margin-top: 2px; margin-bottom: 0;">
                                            As a management authority (IM, SAM, SM), you may reject this indent. Rejection reason is
                                            mandatory. The file will be flagged and returned.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        @endcan
                    </div>

                    {{-- Return Mode Container (Hidden by default) --}}
                    <div id="return-mode-container" style="display: none; margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                            Select Earlier Participant to Return To <span style="color: #dc2626;">*</span>
                        </label>

                        @if(isset($earlierParticipants) && $earlierParticipants->isNotEmpty())
                            <div style="display: grid; gap: 8px;">
                                @foreach($earlierParticipants as $ep)
                                    @php
                                        $pUser = $ep->user;
                                        $pRole = $pUser?->roleAssignment?->role?->name ?? 'User';
                                        $pScope = $pUser?->roleAssignment?->installation?->name ?? $pUser?->roleAssignment?->area?->name ?? $pUser?->location;
                                    @endphp
                                    <label
                                        style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; cursor: pointer; transition: all 0.15s ease;"
                                        class="earlier-participant-label">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <input type="radio" name="earlier_participant_choice" value="{{ $ep->cpf }}"
                                                data-name="{{ $pUser?->name ?? $ep->cpf }}" data-role="{{ $pRole }}"
                                                data-scope="{{ $pScope }}" onchange="selectEarlierParticipant(this)"
                                                style="width: 16px; height: 16px; accent-color: #2563eb;">
                                            <div>
                                                <div style="font-weight: 600; color: #0f172a; font-size: 0.9rem;">
                                                    {{ $pUser?->name ?? $ep->cpf }}
                                                    <span
                                                        style="font-family: monospace; font-size: 0.775rem; color: #64748b;">({{ $ep->cpf }})</span>
                                                </div>
                                                <div style="font-size: 0.775rem; color: #64748b; margin-top: 2px;">
                                                    <span class="badge"
                                                        style="background: #e2e8f0; color: #334155; font-size: 0.7rem; padding: 1px 6px;">
                                                        {{ $pRole }}
                                                    </span>
                                                    &bull; {{ $pScope }}
                                                </div>
                                            </div>
                                        </div>
                                        <span style="font-size: 0.775rem; color: #2563eb; font-weight: 600;">
                                            Return here
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div style="color: #64748b; font-size: 0.85rem; font-style: italic; padding: 8px 0;">
                                No earlier participants recorded on this indent.
                            </div>
                        @endif
                    </div>

                    {{-- Remark Textarea --}}
                    <div style="margin-bottom: 20px;">
                        <label id="remark-label"
                            style="display: block; font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
                            Remarks / Transfer Notes: <span id="remark-required-star"
                                style="display: none; color: #dc2626;">*</span>
                        </label>
                        <textarea name="remark" id="remark-textarea" rows="3" class="form-control"
                            placeholder="Enter transfer instructions, observations, or review comments..."
                            style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem;"></textarea>
                        <span id="remark-help-text"
                            style="font-size: 0.75rem; color: #64748b; margin-top: 4px; display: block;">
                            Optional for normal forward; strictly mandatory when forwarding with rejection.
                        </span>
                        @error('remark')
                            <div style="color: #dc2626; font-size: 0.775rem; margin-top: 4px; font-weight: 600;">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Action Buttons --}}
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <button type="submit" id="submit-action-btn" class="btn btn-primary"
                            style="padding: 10px 24px; font-size: 0.9rem; font-weight: 600;">
                            Forward Indent
                        </button>
                        <span id="validation-hint" style="font-size: 0.8rem; color: #dc2626; display: none;">
                            Please select a recipient before submitting.
                        </span>
                    </div>
                </form>
            </div>
        @endcan

        {{-- Workflow Movements History Card --}}
        <div class="card"
            style="background: white; border: 1px solid var(--border-color); padding: 24px; border-radius: 8px; margin-bottom: 20px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;">
                    Workflow Movements & Forwarding History
                </h2>
                <span
                    style="font-size: 0.75rem; background: #e2e8f0; color: #334155; padding: 2px 8px; border-radius: 9999px; font-weight: 600;">
                    {{ $indent->movements->count() }} Transitions
                </span>
            </div>

            @if($indent->movements->isNotEmpty())
                <div class="timeline">
                    @foreach($indent->movements as $mvt)
                        <x-timeline-item :movement="$mvt" :step="$loop->iteration" />
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

    {{-- Vanilla JavaScript for Recipient Picker & Workflow Action Form --}}
    <script>
        let searchTimeout = null;
        let currentTab = 'Forward';

        function switchActionTab(mode) {
            currentTab = mode;
            const forwardBtn = document.getElementById('tab-btn-forward');
            const returnBtn = document.getElementById('tab-btn-return');
            const forwardContainer = document.getElementById('forward-mode-container');
            const returnContainer = document.getElementById('return-mode-container');
            const actionInput = document.getElementById('action-input');
            const submitBtn = document.getElementById('submit-action-btn');
            const recipientInput = document.getElementById('recipient-cpf-input');

            if (mode === 'Forward') {
                if (forwardBtn) forwardBtn.className = 'btn btn-primary';
                if (returnBtn) returnBtn.className = 'btn btn-outline';
                if (forwardContainer) forwardContainer.style.display = 'block';
                if (returnContainer) returnContainer.style.display = 'none';
                if (actionInput) actionInput.value = 'Forward';
                if (submitBtn) {
                    const isRej = document.getElementById('is-rejected-checkbox')?.checked;
                    submitBtn.innerHTML = isRej ? 'Forward with Rejection' : 'Forward Indent';
                    submitBtn.className = 'btn btn-primary';
                    submitBtn.style.background = isRej ? '#dc2626' : '';
                    submitBtn.style.borderColor = isRej ? '#b91c1c' : '';
                    submitBtn.style.color = '#ffffff';
                }
            } else {
                if (returnBtn) returnBtn.className = 'btn btn-primary';
                if (forwardBtn) forwardBtn.className = 'btn btn-outline';
                if (forwardContainer) forwardContainer.style.display = 'none';
                if (returnContainer) returnContainer.style.display = 'block';
                if (actionInput) actionInput.value = 'Return';
                if (submitBtn) {
                    submitBtn.innerHTML = 'Return Indent';
                    submitBtn.className = 'btn btn-primary';
                    submitBtn.style.background = '#d97706';
                    submitBtn.style.borderColor = '#b45309';
                    submitBtn.style.color = '#ffffff';
                }

                // Pre-select first checked earlier participant if any
                const checkedRadio = document.querySelector('input[name="earlier_participant_choice"]:checked');
                if (checkedRadio && recipientInput) {
                    recipientInput.value = checkedRadio.value;
                }
            }
        }

        function selectEarlierParticipant(radio) {
            const recipientInput = document.getElementById('recipient-cpf-input');
            if (recipientInput) {
                recipientInput.value = radio.value;
            }
            const hint = document.getElementById('validation-hint');
            if (hint) hint.style.display = 'none';
        }

        function toggleRejectionReason(checked) {
            const star = document.getElementById('remark-required-star');
            const textarea = document.getElementById('remark-textarea');
            const submitBtn = document.getElementById('submit-action-btn');

            if (checked) {
                if (star) star.style.display = 'inline';
                if (textarea) {
                    textarea.required = true;
                    textarea.placeholder = 'Provide required reason for rejecting or noting concerns with this indent...';
                }
                if (submitBtn && currentTab === 'Forward') {
                    submitBtn.innerHTML = 'Forward with Rejection';
                    submitBtn.style.background = '#dc2626';
                    submitBtn.style.borderColor = '#b91c1c';
                }
            } else {
                if (star) star.style.display = 'none';
                if (textarea) {
                    textarea.required = false;
                    textarea.placeholder = 'Enter transfer instructions, observations, or review comments...';
                }
                if (submitBtn && currentTab === 'Forward') {
                    submitBtn.innerHTML = 'Forward Indent';
                    submitBtn.style.background = '';
                    submitBtn.style.borderColor = '';
                }
            }
        }

        const searchInput = document.getElementById('recipient-search-input');
        const dropdown = document.getElementById('search-results-dropdown');
        const spinner = document.getElementById('search-spinner');

        function fetchUsers(query = '') {
            if (spinner) spinner.style.display = 'block';
            fetch(`/users/search?indent_id={{ $indent->id }}&q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    if (!dropdown) return;
                    dropdown.innerHTML = '';

                    if (!data || data.length === 0) {
                        dropdown.innerHTML = '<div style="padding: 12px; font-size: 0.85rem; color: #64748b; text-align: center;">No matching users found for this workflow step</div>';
                        dropdown.style.display = 'block';
                        return;
                    }

                    data.forEach(user => {
                        const item = document.createElement('div');
                        item.style.padding = '10px 14px';
                        item.style.borderBottom = '1px solid #f1f5f9';
                        item.style.cursor = 'pointer';
                        item.style.display = 'flex';
                        item.style.justifyContent = 'space-between';
                        item.style.alignItems = 'center';
                        item.onmouseover = () => item.style.background = '#f8fafc';
                        item.onmouseout = () => item.style.background = 'white';

                        item.innerHTML = `
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 0.875rem;">
                                ${user.name} <span style="font-family: monospace; font-size: 0.775rem; color: #64748b;">(${user.cpf})</span>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                ${user.designation} &bull; ${user.location}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.75rem;">
                                ${user.role}
                            </span>
                            <div style="font-size: 0.7rem; color: #64748b; margin-top: 2px;">
                                ${user.scope || ''}
                            </div>
                        </div>
                    `;

                        item.onclick = () => selectRecipient(user);
                        dropdown.appendChild(item);
                    });

                    dropdown.style.display = 'block';
                })
                .catch(() => {
                    if (spinner) spinner.style.display = 'none';
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.trim();
                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(() => {
                    fetchUsers(query);
                }, 200);
            });

            searchInput.addEventListener('focus', function () {
                fetchUsers(this.value.trim());
            });

            document.addEventListener('click', function (e) {
                if (dropdown && !searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
        }

        function selectRecipient(user) {
            const input = document.getElementById('recipient-cpf-input');
            if (input) input.value = user.cpf;

            const nameEl = document.getElementById('selected-recipient-name');
            if (nameEl) nameEl.textContent = user.name;

            const cpfEl = document.getElementById('selected-recipient-cpf');
            if (cpfEl) cpfEl.textContent = user.cpf;

            const roleEl = document.getElementById('selected-recipient-role');
            if (roleEl) roleEl.textContent = user.role;

            const scopeEl = document.getElementById('selected-recipient-scope');
            if (scopeEl) scopeEl.textContent = user.scope || user.location;

            const card = document.getElementById('selected-recipient-card');
            if (card) card.style.display = 'flex';

            const searchWrapper = document.getElementById('search-input-wrapper');
            if (searchWrapper) searchWrapper.style.display = 'none';

            if (dropdown) dropdown.style.display = 'none';

            const hint = document.getElementById('validation-hint');
            if (hint) hint.style.display = 'none';
        }

        function clearSelectedRecipient() {
            const input = document.getElementById('recipient-cpf-input');
            if (input) input.value = '';

            const card = document.getElementById('selected-recipient-card');
            if (card) card.style.display = 'none';

            const searchWrapper = document.getElementById('search-input-wrapper');
            if (searchWrapper) searchWrapper.style.display = 'block';

            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
        }

        document.getElementById('workflow-action-form')?.addEventListener('submit', function (e) {
            const recipientCpf = document.getElementById('recipient-cpf-input')?.value;
            if (!recipientCpf) {
                e.preventDefault();
                const hint = document.getElementById('validation-hint');
                if (hint) hint.style.display = 'inline';
                if (currentTab === 'Forward' && searchInput) {
                    searchInput.focus();
                }
            }
        });
    </script>
@endsection
@props([
    'movement',
    'step' => 1,
])

@php
    $action = strtoupper((string) $movement->action);
    $isRejected = (bool) $movement->is_rejected;
    $isReturn = $action === 'RETURN';

    $actionStyle = match (true) {
        $isRejected => 'color: #dc2626; background: #fee2e2; border: 1px solid #fca5a5;',
        $isReturn => 'color: #b45309; background: #fef3c7; border: 1px solid #fde68a;',
        $action === 'SUBMIT' => 'color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe;',
        $action === 'ACCEPT' => 'color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0;',
        $action === 'START_WORK' || $action === 'START WORK' => 'color: #6b21a8; background: #f3e8ff; border: 1px solid #d8b4fe;',
        $action === 'CLOSE' => 'color: #0f172a; background: #f1f5f9; border: 1px solid #cbd5e1;',
        default => 'color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0;',
    };

    $dotColor = match (true) {
        $isRejected => 'background: #dc2626; box-shadow: 0 0 0 3px #fee2e2;',
        $isReturn => 'background: #d97706; box-shadow: 0 0 0 3px #fef3c7;',
        $action === 'SUBMIT' => 'background: #2563eb; box-shadow: 0 0 0 3px #dbeafe;',
        $action === 'ACCEPT' => 'background: #059669; box-shadow: 0 0 0 3px #d1fae5;',
        $action === 'START_WORK' || $action === 'START WORK' => 'background: #7c3aed; box-shadow: 0 0 0 3px #ede9fe;',
        $action === 'CLOSE' => 'background: #16a34a; box-shadow: 0 0 0 3px #bbf7d0;',
        default => 'background: #2563eb; box-shadow: 0 0 0 3px #dbeafe;',
    };

    $fromName = $movement->fromUser?->name ?? $movement->from;
    $fromRole = $movement->from_role_name;
    $toName = $movement->toUser?->name ?? $movement->to;
    $toRole = $movement->to_role_name;
@endphp

<div class="timeline-item" style="position: relative; margin-bottom: 24px;">
    {{-- Dot indicator --}}
    <div class="timeline-dot" style="position: absolute; left: -29px; top: 8px; width: 20px; height: 20px; border-radius: 50%; border: 3px solid #ffffff; {{ $dotColor }}" title="Step {{ $step }}"></div>

    {{-- Movement Card --}}
    <div style="background: #ffffff; border: 1px solid {{ $isRejected ? '#fca5a5' : '#e2e8f0' }}; border-radius: 10px; padding: 18px 20px; box-shadow: {{ $isRejected ? '0 4px 6px -1px rgba(239, 68, 68, 0.08)' : '0 1px 3px 0 rgba(0, 0, 0, 0.05)' }}; transition: all 0.2s ease;">
        {{-- Header: Step #, Action Badge, Rejection Badge, Date-Time --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 9999px;">
                    Step #{{ $step }}
                </span>

                <span class="badge" style="font-size: 0.775rem; font-weight: 700; padding: 3px 10px; border-radius: 6px; {{ $actionStyle }}">
                    {{ $action }}
                </span>

                @if($isRejected)
                    <span class="badge" style="font-size: 0.75rem; font-weight: 700; color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5; padding: 3px 10px; border-radius: 6px;">
                        REJECTED / CONCERNS
                    </span>
                @endif
            </div>

            <div style="font-size: 0.8rem; color: #64748b; display: inline-flex; align-items: center; gap: 6px;">
                <span>{{ $movement->created_at->format('d M Y, H:i') }}</span>
                <span style="color: #94a3b8;">&bull;</span>
                <span style="color: #475569; font-weight: 500;">{{ $movement->created_at->diffForHumans() }}</span>
            </div>
        </div>

        {{-- From & To Flow with Role at that time --}}
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #f1f5f9; padding: 12px 14px; border-radius: 8px;">
            {{-- Sender (From) --}}
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.05em;">From:</span>
                <strong style="color: #0f172a; font-size: 0.9rem;">{{ $fromName }}</strong>
                <code style="font-size: 0.75rem; color: #475569; background: #e2e8f0; padding: 1px 6px; border-radius: 4px;">{{ $movement->from }}</code>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.72rem; padding: 1px 8px;" title="Role at time of movement">
                    {{ $fromRole }}
                </span>
            </div>

            {{-- Flow Direction Arrow --}}
            <div style="display: flex; align-items: center; color: #2563eb; font-weight: 700; font-size: 1.1rem;">
                &rarr;
            </div>

            {{-- Recipient (To) --}}
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.05em;">To:</span>
                <strong style="color: #0f172a; font-size: 0.9rem;">{{ $toName }}</strong>
                <code style="font-size: 0.75rem; color: #475569; background: #e2e8f0; padding: 1px 6px; border-radius: 4px;">{{ $movement->to }}</code>
                <span class="badge" style="background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 0.72rem; padding: 1px 8px;" title="Role at time of movement">
                    {{ $toRole }}
                </span>
            </div>
        </div>

        {{-- Remark / Transition Note --}}
        @if($movement->remark)
            <div style="font-size: 0.875rem; color: {{ $isRejected ? '#7f1d1d' : '#334155' }}; background: {{ $isRejected ? '#fff5f5' : '#ffffff' }}; border: 1px solid {{ $isRejected ? '#fecaca' : '#e2e8f0' }}; border-left: 4px solid {{ $isRejected ? '#ef4444' : '#3b82f6' }}; padding: 10px 14px; border-radius: 6px; margin-top: 8px; line-height: 1.5;">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: {{ $isRejected ? '#991b1b' : '#64748b' }}; margin-bottom: 2px;">
                    {{ $isRejected ? 'Mandatory Rejection Reason' : 'Remark / Notes' }}:
                </div>
                <span>{{ $movement->remark }}</span>
            </div>
        @endif

        {{-- Movement-specific Attachments if any --}}
        @if($movement->relationLoaded('attachments') && $movement->attachments->isNotEmpty())
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Attachments:</span>
                @foreach($movement->attachments as $att)
                    <a href="{{ route('attachments.download', $att) }}" class="btn btn-outline" style="padding: 2px 8px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; background: white; border-color: #cbd5e1; color: #1e293b; text-decoration: none; border-radius: 4px;">
                        {{ $att->original_name }} <span style="color: #64748b; font-size: 0.7rem;">({{ $att->formatted_size }})</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

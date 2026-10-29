@props([
    'movement',
    'step' => 1,
])

@php
    $action = strtoupper((string) $movement->action);
    $isRejected = (bool) $movement->is_rejected;
    $isReturn = $action === 'RETURN';

    $actionStyle = match (true) {
        $isRejected => 'color: #B91C1C; background: #FEF2F2; border-color: #FECACA;',
        $isReturn => 'color: #B45309; background: #FFFBEB; border-color: #FDE68A;',
        $action === 'SUBMIT' => 'color: #16324F; background: #F0F4F8; border-color: #CBD5E1;',
        $action === 'ACCEPT' => 'color: #0F766E; background: #F0FDFA; border-color: #CCFBF1;',
        $action === 'START_WORK' || $action === 'START WORK' => 'color: #6D28D9; background: #F5F3FF; border-color: #DDD6FE;',
        $action === 'CLOSE' => 'color: #15803D; background: #F0FDF4; border-color: #BBF7D0;',
        default => 'color: #0F766E; background: #F0FDFA; border-color: #CCFBF1;',
    };

    $dotColor = match (true) {
        $isRejected => 'background: #B91C1C; box-shadow: 0 0 0 3px #FEF2F2;',
        $isReturn => 'background: #B45309; box-shadow: 0 0 0 3px #FFFBEB;',
        $action === 'SUBMIT' => 'background: #16324F; box-shadow: 0 0 0 3px #F0F4F8;',
        $action === 'ACCEPT' => 'background: #0F766E; box-shadow: 0 0 0 3px #F0FDFA;',
        $action === 'START_WORK' || $action === 'START WORK' => 'background: #7C3AED; box-shadow: 0 0 0 3px #EDE9FE;',
        $action === 'CLOSE' => 'background: #15803D; box-shadow: 0 0 0 3px #BBF7D0;',
        default => 'background: #16324F; box-shadow: 0 0 0 3px #F0F4F8;',
    };

    $fromName = $movement->fromUser?->name ?? $movement->from;
    $fromRole = $movement->from_role_name;
    $toName = $movement->toUser?->name ?? $movement->to;
    $toRole = $movement->to_role_name;
@endphp

<div class="timeline-item" style="position: relative; margin-bottom: 22px;">
    {{-- Dot indicator --}}
    <div class="timeline-dot" style="position: absolute; left: -29px; top: 10px; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #FFFFFF; {{ $dotColor }}" title="Step {{ $step }}"></div>

    {{-- Movement Card --}}
    <div style="background: var(--surface); border: 1px solid {{ $isRejected ? 'var(--danger-border)' : 'var(--border)' }}; border-radius: var(--radius-lg); padding: 16px 20px; box-shadow: var(--shadow-xs);">
        {{-- Header: Step #, Action Badge, Rejection Badge, Date-Time --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); background: var(--surface-subtle); border: 1px solid var(--border); padding: 2px 8px; border-radius: 9999px;">
                    Step #{{ $step }}
                </span>

                <span class="badge" style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-sm); border: 1px solid; {{ $actionStyle }}">
                    {{ $action }}
                </span>

                @if($isRejected)
                    <span class="badge" style="font-size: 0.72rem; font-weight: 700; color: var(--danger); background: var(--danger-bg); border: 1px solid var(--danger-border); padding: 2px 8px; border-radius: var(--radius-sm);">
                        FLAGGED WITH REJECTION
                    </span>
                @endif
            </div>

            <div style="font-size: 0.78rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px;">
                <span>{{ $movement->created_at->format('d M Y, H:i') }}</span>
                <span style="color: var(--text-light);">&bull;</span>
                <span style="font-weight: 500;">{{ $movement->created_at->diffForHumans() }}</span>
            </div>
        </div>

        {{-- From & To Flow --}}
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; background: var(--surface-subtle); border: 1px solid var(--border); padding: 10px 14px; border-radius: var(--radius-md);">
            {{-- Sender (From) --}}
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">From:</span>
                <strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $fromName }}</strong>
                <code style="font-size: 0.72rem; color: var(--text-muted); background: var(--surface); padding: 1px 6px; border-radius: var(--radius-sm); border: 1px solid var(--border);">{{ $movement->from }}</code>
                <span style="background: #F0F9FF; color: #0284C7; border: 1px solid #BAE6FD; font-size: 0.7rem; font-weight: 600; padding: 1px 7px; border-radius: var(--radius-sm);" title="Role at time of movement">
                    {{ $fromRole }}
                </span>
            </div>

            {{-- Flow Direction Arrow --}}
            <div style="display: flex; align-items: center; color: var(--secondary); font-weight: 700; font-size: 1rem;">
                &rarr;
            </div>

            {{-- Recipient (To) --}}
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                <span style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.05em;">To:</span>
                <strong style="color: var(--text-primary); font-size: 0.85rem;">{{ $toName }}</strong>
                <code style="font-size: 0.72rem; color: var(--text-muted); background: var(--surface); padding: 1px 6px; border-radius: var(--radius-sm); border: 1px solid var(--border);">{{ $movement->to }}</code>
                <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #CCFBF1; font-size: 0.7rem; font-weight: 600; padding: 1px 7px; border-radius: var(--radius-sm);" title="Role at time of movement">
                    {{ $toRole }}
                </span>
            </div>
        </div>

        {{-- Remark / Transition Note --}}
        @if($movement->remark)
            <div style="font-size: 0.85rem; color: {{ $isRejected ? 'var(--danger)' : 'var(--text-primary)' }}; background: {{ $isRejected ? 'var(--danger-bg)' : 'var(--surface-subtle)' }}; border: 1px solid {{ $isRejected ? 'var(--danger-border)' : 'var(--border)' }}; border-left: 4px solid {{ $isRejected ? 'var(--danger)' : 'var(--secondary)' }}; padding: 10px 14px; border-radius: var(--radius-md); margin-top: 8px; line-height: 1.5;">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: {{ $isRejected ? 'var(--danger)' : 'var(--text-muted)' }}; margin-bottom: 2px;">
                    {{ $isRejected ? 'Rejection Reason' : 'Remark / Notes' }}:
                </div>
                <span>{{ $movement->remark }}</span>
            </div>
        @endif

        {{-- Movement-specific Attachments --}}
        @if($movement->relationLoaded('attachments') && $movement->attachments->isNotEmpty())
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Attachments:</span>
                @foreach($movement->attachments as $att)
                    <a href="{{ route('attachments.download', $att) }}" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 2px 8px;">
                        {{ $att->original_name }} <span style="color: var(--text-muted); font-size: 0.7rem;">({{ $att->formatted_size }})</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

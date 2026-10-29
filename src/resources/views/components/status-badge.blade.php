@props([
    'indent' => null,
    'status' => null,
    'isDraft' => null,
    'isRejected' => null,
])

@php
    if ($indent) {
        $status = $status ?? $indent->status;
        $isDraft = $isDraft ?? $indent->is_draft;
        $isRejected = $isRejected ?? $indent->is_rejected;
    }

    $status = $status ?? \App\Enums\IndentStatus::OPEN;
    $statusVal = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $isDraft = (bool) ($isDraft ?? false);
    $isRejected = (bool) ($isRejected ?? false);

    $statusNormalized = strtolower(str_replace([' ', '_'], '', $statusVal));

    $displayLabel = match ($statusNormalized) {
        'inprogress' => 'In Progress',
        'closed' => 'Completed',
        'assigned' => 'Assigned',
        'open' => 'Open',
        default => $statusVal,
    };

    $badgeStyle = match ($statusNormalized) {
        'open' => 'background: #ECFDF5; color: #047857; border-color: #A7F3D0;',
        'assigned' => 'background: #F0F4F8; color: #16324F; border-color: #CBD5E1;',
        'inprogress' => 'background: #F5F3FF; color: #6D28D9; border-color: #DDD6FE;',
        'closed' => 'background: #F1F5F9; color: #475569; border-color: #CBD5E1;',
        default => 'background: #ECFDF5; color: #047857; border-color: #A7F3D0;',
    };
@endphp

@if($isDraft)
    <span class="badge" style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border);">
        Draft
    </span>
@else
    <span class="badge" style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; border: 1px solid; {{ $badgeStyle }}">
        {{ $displayLabel }}
    </span>

    @if($isRejected)
        <span class="badge" style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); margin-left: 4px;">
            Rejected
        </span>
    @endif
@endif

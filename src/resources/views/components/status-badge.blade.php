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
        'closed' => 'Closed',
        'assigned' => 'Assigned',
        'open' => 'Open',
        default => $statusVal,
    };

    $badgeClass = match ($statusNormalized) {
        'open' => 'badge-open',
        'assigned' => 'badge-assigned',
        'inprogress' => 'badge-inprogress',
        'closed' => 'badge-closed',
        default => 'badge-open',
    };
@endphp

@if($isDraft)
    <span class="badge badge-draft">
        Draft (Editable)
    </span>
@else
    <span class="badge {{ $badgeClass }}">
        {{ $displayLabel }}
    </span>

    @if($isRejected)
        <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; margin-left: 4px;">
            Flagged (Rejected)
        </span>
    @endif
@endif

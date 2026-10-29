@props([
    'indent' => null,
    'priority' => null,
])

@php
    if ($indent && !$priority) {
        $priority = $indent->priority;
    }
    $priority = $priority ?? 'Medium';
    $priorityVal = $priority instanceof \BackedEnum ? $priority->value : (string) $priority;
    $priorityLower = strtolower((string) $priorityVal);

    $badgeStyle = match ($priorityLower) {
        'urgent' => 'background: #FEF2F2; color: #B91C1C; border-color: #FECACA;',
        'high' => 'background: #FFF7ED; color: #C2410C; border-color: #FFEDD5;',
        'low' => 'background: #F8FAFC; color: #64748B; border-color: #E2E8F0;',
        default => 'background: #F0FDFA; color: #0F766E; border-color: #CCFBF1;',
    };
@endphp

<span class="badge" style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; border: 1px solid; {{ $badgeStyle }}">
    {{ $priorityVal }}
</span>

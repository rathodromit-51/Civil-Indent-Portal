@props([
    'indent' => null,
    'priority' => null,
])

@php
    if ($indent && ! $priority) {
        $priority = $indent->priority;
    }
    $priority = $priority ?? 'Medium';
    $priorityVal = $priority instanceof \BackedEnum ? $priority->value : (string) $priority;
    $priorityClass = match (strtolower((string) $priorityVal)) {
        'urgent' => 'badge-priority-urgent',
        'high' => 'badge-priority-high',
        'low' => 'badge-priority-low',
        default => 'badge-priority-medium',
    };
@endphp

<span class="badge {{ $priorityClass }}">
    {{ $priorityVal }}
</span>

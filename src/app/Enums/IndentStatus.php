<?php

namespace App\Enums;

enum IndentStatus: string
{
    case OPEN = 'Open';
    case ASSIGNED = 'Assigned';
    case IN_PROGRESS = 'InProgress';
    case CLOSED = 'Closed';

    /**
     * Get an array of all status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::CLOSED => 'Completed / Closed',
        };
    }
}

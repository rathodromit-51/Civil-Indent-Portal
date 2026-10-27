<?php

namespace App\Enums;

enum MovementAction: string
{
    case SUBMIT = 'Submit';
    case FORWARD = 'Forward';
    case APPROVE = 'Approve';
    case REJECT = 'Reject';
    case ASSIGN = 'Assign';
    case RETURN = 'Return';
    case ACCEPT = 'Accept';
    case START_WORK = 'StartWork';
    case CLOSE = 'Close';
    case COMMENT = 'Comment';

    /**
     * Get an array of all movement action values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

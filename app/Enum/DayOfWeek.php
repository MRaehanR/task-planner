<?php

namespace App\Enum;

enum DayOfWeek: string
{
    case SUNDAY = 'Sunday';
    case MONDAY = 'Monday';
    case TUESDAY = 'Tuesday';
    case WEDNESDAY = 'Wednesday';
    case THURSDAY = 'Thursday';
    case FRIDAY = 'Friday';
    case SATURDAY = 'Saturday';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

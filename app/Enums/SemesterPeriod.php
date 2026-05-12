<?php

namespace App\Enums;

enum SemesterPeriod: string
{
    case First = '1';
    case Second = '2';
    case Midyear = '3';

    public function label(): string
    {
        return match ($this) {
            self::First => '1st Semester',
            self::Second => '2nd Semester',
            self::Midyear => 'Midyear',
        };
    }
}

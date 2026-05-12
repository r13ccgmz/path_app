<?php

namespace App\Enums;

enum DegreeLevel: string
{
    case Master = 'master';
    case MasterOfScience = 'master_of_science';
    case Doctorate = 'doctorate';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Master',
            self::MasterOfScience => 'Master of Science',
            self::Doctorate => 'Doctorate',
        };
    }
}

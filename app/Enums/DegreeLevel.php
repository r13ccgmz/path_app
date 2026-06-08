<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DegreeLevel: string implements HasLabel
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

    public function getLabel(): ?string
    {
        return $this->label();
    }
}

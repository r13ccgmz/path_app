<?php

namespace App\Enums;

enum CommitteeRole: string
{
    case Chair = 'Chair';
    case CoChair = 'Co-Chair';
    case Cognate = 'Cognate';
    case Major = 'Major';
    case Minor = 'Minor';
    case Member = 'Member';
    case Adviser = 'Adviser';
    case CoAdviser = 'Co-Adviser';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Get all roles as an options array for Filament Select fields.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->toArray();
    }
}

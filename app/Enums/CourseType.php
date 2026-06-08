<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CourseType: string implements HasLabel
{
    case Core = 'core';
    case Prescribed = 'prescribed';
    case Major = 'major';
    case Specialization = 'specialization';
    case Cognate = 'cognate';
    case Elective = 'elective';
    case Thesis = 'thesis';
    case Dissertation = 'dissertation';
    case FieldStudy = 'field_study';
    case Seminar = 'seminar';

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Core',
            self::Prescribed => 'Prescribed',
            self::Major => 'Major',
            self::Specialization => 'Specialization',
            self::Cognate => 'Cognate',
            self::Elective => 'Elective',
            self::Thesis => 'Thesis',
            self::Dissertation => 'Dissertation',
            self::FieldStudy => 'Field Study',
            self::Seminar => 'Seminar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Core => 'primary',
            self::Prescribed => 'info',
            self::Major => 'success',
            self::Specialization => 'violet',
            self::Cognate => 'warning',
            self::Elective => 'gray',
            self::Thesis => 'danger',
            self::Dissertation => 'danger',
            self::FieldStudy => 'teal',
            self::Seminar => 'gray',
        };
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }
}

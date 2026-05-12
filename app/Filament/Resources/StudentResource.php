<?php

namespace App\Filament\Resources;

use App\Models\Student;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Lightweight resource that exists solely to make Students discoverable
 * via Filament's global search (Ctrl+K).  The actual student management
 * UI lives in the StudentHistory Page — this resource is hidden from
 * navigation and has no create/edit pages.
 */
class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    // ── Hidden from navigation — students are managed via StudentHistory page ──
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'student_number';

    protected static int $globalSearchResultsLimit = 10;

    protected static ?string $modelLabel = 'Student';
    protected static ?string $pluralModelLabel = 'Students';

    public static function getGloballySearchableAttributes(): array
    {
        return ['student_number', 'surname', 'given_name', 'full_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->full_name ?? trim(($record->surname ?? '') . ', ' . ($record->given_name ?? ''));
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Student No.' => $record->formatted_student_number,
            'Program' => $record->program?->code,
            'Status' => ucfirst(str_replace('-', ' ', $record->student_status ?? '')),
        ]);
    }

    public static function getGlobalSearchResultUrl(\Illuminate\Database\Eloquent\Model $record): ?string
    {
        // Link directly to the StudentHistory page with the student pre-selected
        return url('/admin/list-of-students?studentNumber=' . urlencode($record->student_number));
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('program');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

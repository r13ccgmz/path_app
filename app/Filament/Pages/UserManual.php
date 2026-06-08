<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Filament\Pages\Page;

class UserManual extends Page
{
    use HasPageShield;
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'User Manual';
    protected static ?string $title = 'User Manual';
    protected static ?string $slug = 'user-manual';

    protected string $view = 'filament.pages.user-manual';
}

<?php

namespace App\Filament\Resources\CognateFields;

use App\Filament\Resources\CognateFields\Pages\CreateCognateField;
use App\Filament\Resources\CognateFields\Pages\EditCognateField;
use App\Filament\Resources\CognateFields\Pages\ListCognateFields;
use App\Filament\Resources\CognateFields\Schemas\CognateFieldForm;
use App\Filament\Resources\CognateFields\Tables\CognateFieldsTable;
use App\Models\CognateField;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CognateFieldResource extends Resource
{
    protected static ?string $model = CognateField::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';


    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 4;


    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return CognateFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CognateFieldsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCognateFields::route('/'),
            'create' => CreateCognateField::route('/create'),
            'edit' => EditCognateField::route('/{record}/edit'),
        ];
    }
}

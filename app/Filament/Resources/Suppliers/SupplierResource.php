<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'مورد';

    protected static ?string $pluralModelLabel = 'الموردون';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if ($record instanceof Supplier) {
            return $record->getLocalizedDisplayName();
        }

        return parent::getRecordTitle($record);
    }

    /**
     * @return array<int, string|array<string>>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'name_ar', 'code'];
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}

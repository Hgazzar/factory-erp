<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // يمنع ربط الصف بـ URL صفحة التعديل؛ النقر يفتح إجراء «تعديل» (شريط جانبي) بدل الانتقال الكامل.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('localized_display_name')
                    ->label(fn (): string => Supplier::localePrefersArabic() ? 'اسم المورد' : 'Supplier name')
                    ->searchable(['name', 'name_ar', 'code'])
                    ->sortable(Supplier::localePrefersArabic() ? ['name_ar', 'name'] : ['name', 'name_ar']),
                TextColumn::make('contact_name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('supplier_type')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_number')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('commercial_register')
                    ->searchable(),
                TextColumn::make('credit_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_terms_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->searchable(),
                TextColumn::make('iban')
                    ->searchable(),
                TextColumn::make('swift_code')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('contact_name'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('address'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('supplier_type'),
                TextInput::make('rating')
                    ->numeric(),
                TextInput::make('tax_number'),
                TextInput::make('currency'),
                TextInput::make('mobile'),
                TextInput::make('website')
                    ->url(),
                TextInput::make('name_ar'),
                TextInput::make('commercial_register'),
                TextInput::make('credit_limit')
                    ->numeric(),
                TextInput::make('payment_terms_days')
                    ->numeric(),
                TextInput::make('bank_name'),
                TextInput::make('bank_account_number'),
                TextInput::make('iban'),
                TextInput::make('swift_code'),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->default(1),
            ]);
    }
}

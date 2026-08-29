<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('business_name'),
                TextInput::make('tax_id'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('country'),
                TextInput::make('timezone')
                    ->required()
                    ->default('America/Lima'),
                TextInput::make('logo'),
                Select::make('status')
                    ->options(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended', 'inactive' => 'Inactive'])
                    ->default('trial')
                    ->required(),
                DateTimePicker::make('trial_ends_at'),
            ]);
    }
}

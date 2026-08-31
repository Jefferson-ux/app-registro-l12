<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(null),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->default(null),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->default(null),
                Select::make('position_id')
                    ->relationship('position', 'name')
                    ->default(null),
                Select::make('supervisor_id')
                    ->relationship('supervisor', 'id')
                    ->default(null),
                TextInput::make('employee_code')
                    ->required(),
                TextInput::make('document_type')
                    ->default(null),
                TextInput::make('document_number')
                    ->default(null),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('personal_email')
                    ->email()
                    ->default(null),
                TextInput::make('work_email')
                    ->email()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                DatePicker::make('hire_date'),
                DatePicker::make('termination_date'),
                Select::make('employment_status')
                    ->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
        ])
                    ->default('active')
                    ->required(),
            ]);
    }
}

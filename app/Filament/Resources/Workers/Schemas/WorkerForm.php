<?php

namespace App\Filament\Resources\Workers\Schemas;

use App\Models\User;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->required(),

                Textarea::make('bio')
                    ->columnSpanFull(),

                TextInput::make('experience_years')
                    ->numeric()
                    ->minValue(0),

                Toggle::make('is_available')
                    ->label('Is Available')
                    ->default(true),

                TextInput::make('rating')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(5),

                TextInput::make('total_jobs')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
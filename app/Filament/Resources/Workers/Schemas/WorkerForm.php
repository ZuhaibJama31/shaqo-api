<?php

namespace App\Filament\Resources\Workers\Schemas;

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
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('category_id')
                    ->required()
                    ->numeric(),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('experience_years'),
                Toggle::make('is_available')
                    ->required(),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_jobs')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

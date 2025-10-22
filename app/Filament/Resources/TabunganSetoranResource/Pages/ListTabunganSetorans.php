<?php

namespace App\Filament\Resources\TabunganSetoranResource\Pages;

use App\Filament\Resources\TabunganSetoranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTabunganSetorans extends ListRecords
{
    protected static string $resource = TabunganSetoranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

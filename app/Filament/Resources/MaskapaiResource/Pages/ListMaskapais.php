<?php

namespace App\Filament\Resources\MaskapaiResource\Pages;

use App\Filament\Resources\MaskapaiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaskapais extends ListRecords
{
    protected static string $resource = MaskapaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
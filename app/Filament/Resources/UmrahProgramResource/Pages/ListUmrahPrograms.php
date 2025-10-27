<?php

namespace App\Filament\Resources\UmrahProgramResource\Pages;

use App\Filament\Resources\UmrahProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUmrahPrograms extends ListRecords
{
    protected static string $resource = UmrahProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

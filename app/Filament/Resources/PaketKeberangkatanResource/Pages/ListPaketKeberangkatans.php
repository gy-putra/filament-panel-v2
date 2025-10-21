<?php

namespace App\Filament\Resources\PaketKeberangkatanResource\Pages;

use App\Filament\Resources\PaketKeberangkatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaketKeberangkatans extends ListRecords
{
    protected static string $resource = PaketKeberangkatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
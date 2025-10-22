<?php

namespace App\Filament\Resources\TabunganTargetResource\Pages;

use App\Filament\Resources\TabunganTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTabunganTargets extends ListRecords
{
    protected static string $resource = TabunganTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

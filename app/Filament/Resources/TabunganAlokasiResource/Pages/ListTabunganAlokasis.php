<?php

namespace App\Filament\Resources\TabunganAlokasiResource\Pages;

use App\Filament\Resources\TabunganAlokasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTabunganAlokasis extends ListRecords
{
    protected static string $resource = TabunganAlokasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\TabunganSetoranResource\Pages;

use App\Filament\Resources\TabunganSetoranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTabunganSetoran extends EditRecord
{
    protected static string $resource = TabunganSetoranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

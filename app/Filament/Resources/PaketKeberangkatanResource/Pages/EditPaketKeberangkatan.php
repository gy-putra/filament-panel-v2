<?php

namespace App\Filament\Resources\PaketKeberangkatanResource\Pages;

use App\Filament\Resources\PaketKeberangkatanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaketKeberangkatan extends EditRecord
{
    protected static string $resource = PaketKeberangkatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
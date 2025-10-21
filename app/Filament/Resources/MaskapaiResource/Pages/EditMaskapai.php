<?php

namespace App\Filament\Resources\MaskapaiResource\Pages;

use App\Filament\Resources\MaskapaiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaskapai extends EditRecord
{
    protected static string $resource = MaskapaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
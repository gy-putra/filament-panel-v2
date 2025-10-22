<?php

namespace App\Filament\Resources\TabunganTargetResource\Pages;

use App\Filament\Resources\TabunganTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTabunganTarget extends EditRecord
{
    protected static string $resource = TabunganTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

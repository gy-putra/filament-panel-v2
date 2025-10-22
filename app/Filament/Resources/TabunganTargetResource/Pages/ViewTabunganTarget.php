<?php

namespace App\Filament\Resources\TabunganTargetResource\Pages;

use App\Filament\Resources\TabunganTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTabunganTarget extends ViewRecord
{
    protected static string $resource = TabunganTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
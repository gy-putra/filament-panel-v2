<?php

namespace App\Filament\Resources\UmrahProgramResource\Pages;

use App\Filament\Resources\UmrahProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUmrahProgram extends EditRecord
{
    protected static string $resource = UmrahProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

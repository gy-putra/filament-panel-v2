<?php

namespace App\Filament\Resources\RoomAssignmentResource\Pages;

use App\Filament\Resources\RoomAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoomAssignment extends EditRecord
{
    protected static string $resource = RoomAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
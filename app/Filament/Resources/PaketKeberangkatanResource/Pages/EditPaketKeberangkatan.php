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
            Actions\DeleteAction::make()
                ->label('Delete (Cascade)')
                ->modalHeading('Delete Package and All Related Data')
                ->modalDescription('This will permanently delete the package and ALL related data including registrations, itineraries, hotels, flights, and staff assignments. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete Everything')
                ->action(function () {
                    $this->record->cascadeDelete();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
        ];
    }
}
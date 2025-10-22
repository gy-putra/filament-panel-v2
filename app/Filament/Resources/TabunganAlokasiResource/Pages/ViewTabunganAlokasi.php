<?php

namespace App\Filament\Resources\TabunganAlokasiResource\Pages;

use App\Filament\Resources\TabunganAlokasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTabunganAlokasi extends ViewRecord
{
    protected static string $resource = TabunganAlokasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status === 'draft'),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->status === 'draft'),
        ];
    }
}
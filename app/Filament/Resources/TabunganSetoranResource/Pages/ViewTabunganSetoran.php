<?php

namespace App\Filament\Resources\TabunganSetoranResource\Pages;

use App\Filament\Resources\TabunganSetoranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTabunganSetoran extends ViewRecord
{
    protected static string $resource = TabunganSetoranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status_verifikasi === 'pending'),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->status_verifikasi === 'pending'),
        ];
    }
}
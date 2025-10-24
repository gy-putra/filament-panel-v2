<?php

namespace App\Filament\Resources\PaketKeberangkatanResource\Pages;

use App\Filament\Resources\PaketKeberangkatanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPaketKeberangkatan extends ViewRecord
{
    protected static string $resource = PaketKeberangkatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->loadCount('pendaftarans');
    }
}
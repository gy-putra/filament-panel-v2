<?php

namespace App\Filament\Resources\SalesAgentResource\Pages;

use App\Filament\Resources\SalesAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSalesAgent extends CreateRecord
{
    protected static string $resource = SalesAgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

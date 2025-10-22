<?php

namespace App\Filament\Resources\TabunganAlokasiResource\Pages;

use App\Filament\Resources\TabunganAlokasiResource;
use App\Services\SavingsLedgerService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTabunganAlokasi extends CreateRecord
{
    protected static string $resource = TabunganAlokasiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure status is always draft for new allocations
        $data['status'] = 'draft';
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = app(SavingsLedgerService::class);
        
        try {
            return $service->createAllocation($data);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal membuat alokasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            // Re-throw the exception to prevent form submission
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

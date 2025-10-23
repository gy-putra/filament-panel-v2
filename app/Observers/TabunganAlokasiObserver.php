<?php

namespace App\Observers;

use App\Models\TabunganAlokasi;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class TabunganAlokasiObserver
{
    /**
     * Handle the TabunganAlokasi "updated" event.
     * This method is triggered whenever a TabunganAlokasi record is updated.
     * It specifically handles the case where the status changes to 'reversed'
     * and automatically updates the related invoice status to 'canceled'.
     */
    public function updated(TabunganAlokasi $tabunganAlokasi): void
    {
        // Check if the status was changed to 'reversed'
        if ($tabunganAlokasi->isDirty('status') && $tabunganAlokasi->status === 'reversed') {
            $this->handleAllocationReversed($tabunganAlokasi);
        }
    }

    /**
     * Handle the allocation reversal by updating the related invoice status.
     * This ensures data integrity between TabunganAlokasi and Invoice resources.
     */
    private function handleAllocationReversed(TabunganAlokasi $tabunganAlokasi): void
    {
        // Check if there's a related invoice
        if ($tabunganAlokasi->invoice_id) {
            $invoice = Invoice::find($tabunganAlokasi->invoice_id);
            
            if ($invoice && $invoice->status !== 'cancelled') {
                // Update the invoice status to 'canceled'
                $invoice->update(['status' => 'cancelled']);
                
                Log::info('Invoice status automatically updated to cancelled due to allocation reversal', [
                    'allocation_id' => $tabunganAlokasi->id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->nomor_invoice,
                    'previous_status' => $invoice->getOriginal('status'),
                    'new_status' => 'canceled',
                    'updated_by' => auth()->id() ?? 'system',
                ]);
            }
        }
    }

    /**
     * Handle the TabunganAlokasi "creating" event.
     */
    public function creating(TabunganAlokasi $tabunganAlokasi): void
    {
        // Set audit fields if authenticated user exists
        if (auth()->check()) {
            // Note: created_by and updated_by fields would need to be added to the migration
            // if audit tracking is required for TabunganAlokasi
        }
    }

    /**
     * Handle the TabunganAlokasi "created" event.
     */
    public function created(TabunganAlokasi $tabunganAlokasi): void
    {
        Log::info('TabunganAlokasi created', [
            'allocation_id' => $tabunganAlokasi->id,
            'tabungan_id' => $tabunganAlokasi->tabungan_id,
            'status' => $tabunganAlokasi->status,
            'nominal' => $tabunganAlokasi->nominal,
            'created_by' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Handle the TabunganAlokasi "deleted" event.
     */
    public function deleted(TabunganAlokasi $tabunganAlokasi): void
    {
        Log::info('TabunganAlokasi deleted', [
            'allocation_id' => $tabunganAlokasi->id,
            'tabungan_id' => $tabunganAlokasi->tabungan_id,
            'deleted_by' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Handle the TabunganAlokasi "restored" event.
     */
    public function restored(TabunganAlokasi $tabunganAlokasi): void
    {
        Log::info('TabunganAlokasi restored', [
            'allocation_id' => $tabunganAlokasi->id,
            'tabungan_id' => $tabunganAlokasi->tabungan_id,
            'restored_by' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Handle the TabunganAlokasi "force deleted" event.
     */
    public function forceDeleted(TabunganAlokasi $tabunganAlokasi): void
    {
        Log::info('TabunganAlokasi force deleted', [
            'allocation_id' => $tabunganAlokasi->id,
            'tabungan_id' => $tabunganAlokasi->tabungan_id,
            'force_deleted_by' => auth()->id() ?? 'system',
        ]);
    }
}
<?php

namespace App\Observers;

use App\Models\Pendaftaran;
use App\Services\KuotaService;
use Illuminate\Support\Facades\Log;

class PendaftaranObserver
{
    protected KuotaService $kuotaService;

    public function __construct(KuotaService $kuotaService)
    {
        $this->kuotaService = $kuotaService;
    }

    /**
     * Handle the Pendaftaran "created" event.
     */
    public function created(Pendaftaran $pendaftaran): void
    {
        // Update kuota if the new registration is confirmed
        if ($pendaftaran->status === 'confirmed') {
            $this->kuotaService->updateKuotaTerisi($pendaftaran->paket_keberangkatan_id);
        }
    }

    /**
     * Handle the Pendaftaran "updated" event.
     */
    public function updated(Pendaftaran $pendaftaran): void
    {
        // Check if status changed
        if ($pendaftaran->isDirty('status')) {
            $oldStatus = $pendaftaran->getOriginal('status');
            $newStatus = $pendaftaran->status;

            // Update kuota if status changed to/from confirmed
            if ($oldStatus === 'confirmed' || $newStatus === 'confirmed') {
                $this->kuotaService->updateKuotaTerisi($pendaftaran->paket_keberangkatan_id);
            }
        }

        // Check if paket_keberangkatan_id changed (rare case)
        if ($pendaftaran->isDirty('paket_keberangkatan_id')) {
            $oldPaketId = $pendaftaran->getOriginal('paket_keberangkatan_id');
            $newPaketId = $pendaftaran->paket_keberangkatan_id;

            // Update both old and new paket quotas
            if ($oldPaketId) {
                $this->kuotaService->updateKuotaTerisi($oldPaketId);
            }
            if ($newPaketId) {
                $this->kuotaService->updateKuotaTerisi($newPaketId);
            }
        }
    }

    /**
     * Handle the Pendaftaran "deleted" event.
     */
    public function deleted(Pendaftaran $pendaftaran): void
    {
        // Update kuota if the deleted registration was confirmed
        if ($pendaftaran->status === 'confirmed') {
            $this->kuotaService->updateKuotaTerisi($pendaftaran->paket_keberangkatan_id);
        }
    }

    /**
     * Handle the Pendaftaran "restored" event.
     */
    public function restored(Pendaftaran $pendaftaran): void
    {
        // Update kuota if the restored registration is confirmed
        if ($pendaftaran->status === 'confirmed') {
            $this->kuotaService->updateKuotaTerisi($pendaftaran->paket_keberangkatan_id);
        }
    }

    /**
     * Handle the Pendaftaran "force deleted" event.
     */
    public function forceDeleted(Pendaftaran $pendaftaran): void
    {
        // Update kuota if the force deleted registration was confirmed
        if ($pendaftaran->status === 'confirmed') {
            $this->kuotaService->updateKuotaTerisi($pendaftaran->paket_keberangkatan_id);
        }
    }
}
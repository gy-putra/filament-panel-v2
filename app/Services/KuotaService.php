<?php

namespace App\Services;

use App\Models\PaketKeberangkatan;
use App\Models\Pendaftaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KuotaService
{
    /**
     * Update kuota_terisi for a specific paket keberangkatan
     */
    public function updateKuotaTerisi(int $paketKeberangkatanId): void
    {
        try {
            DB::transaction(function () use ($paketKeberangkatanId) {
                $paket = PaketKeberangkatan::lockForUpdate()->find($paketKeberangkatanId);
                
                if (!$paket) {
                    Log::warning("PaketKeberangkatan with ID {$paketKeberangkatanId} not found");
                    return;
                }

                // Count confirmed registrations
                $kuotaTerisi = Pendaftaran::where('paket_keberangkatan_id', $paketKeberangkatanId)
                    ->where('status', 'confirmed')
                    ->count();

                $paket->update(['kuota_terisi' => $kuotaTerisi]);

                Log::info("Updated kuota_terisi for paket {$paketKeberangkatanId}: {$kuotaTerisi}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to update kuota_terisi for paket {$paketKeberangkatanId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if paket has available quota
     */
    public function hasAvailableQuota(int $paketKeberangkatanId): bool
    {
        $paket = PaketKeberangkatan::find($paketKeberangkatanId);
        
        if (!$paket) {
            return false;
        }

        return $paket->kuota_terisi < $paket->kuota_total;
    }

    /**
     * Get remaining quota for a paket
     */
    public function getRemainingQuota(int $paketKeberangkatanId): int
    {
        $paket = PaketKeberangkatan::find($paketKeberangkatanId);
        
        if (!$paket) {
            return 0;
        }

        return max(0, $paket->kuota_total - $paket->kuota_terisi);
    }

    /**
     * Validate if registration can be confirmed based on quota
     */
    public function canConfirmRegistration(int $paketKeberangkatanId): bool
    {
        return $this->hasAvailableQuota($paketKeberangkatanId);
    }

    /**
     * Update quota for multiple pakets (bulk operation)
     */
    public function updateMultipleKuotaTerisi(array $paketKeberangkatanIds): void
    {
        foreach ($paketKeberangkatanIds as $paketId) {
            $this->updateKuotaTerisi($paketId);
        }
    }

    /**
     * Recalculate all kuota_terisi (maintenance operation)
     */
    public function recalculateAllKuota(): void
    {
        $pakets = PaketKeberangkatan::all();
        
        foreach ($pakets as $paket) {
            $this->updateKuotaTerisi($paket->id);
        }
        
        Log::info("Recalculated kuota_terisi for all pakets");
    }
}
<?php

namespace App\Observers;

use App\Models\Pendaftaran;
use App\Services\RoomingService;
use Illuminate\Support\Facades\Log;

class PendaftaranRoomingObserver
{
    protected RoomingService $roomingService;

    public function __construct(RoomingService $roomingService)
    {
        $this->roomingService = $roomingService;
    }

    /**
     * Handle the Pendaftaran "updated" event.
     */
    public function updated(Pendaftaran $pendaftaran): void
    {
        // Check if status changed to confirmed
        if ($pendaftaran->isDirty('status') && $pendaftaran->status === 'confirmed') {
            try {
                $this->roomingService->autoAssignRoom($pendaftaran->id);
                Log::info("Auto-assigned room for confirmed pendaftaran {$pendaftaran->id}");
            } catch (\Exception $e) {
                Log::error("Failed to auto-assign room for pendaftaran {$pendaftaran->id}: " . $e->getMessage());
            }
        }

        // Check if status changed from confirmed to something else
        if ($pendaftaran->isDirty('status') && $pendaftaran->getOriginal('status') === 'confirmed' && $pendaftaran->status !== 'confirmed') {
            try {
                $this->roomingService->removeRoomAssignment($pendaftaran->id);
                Log::info("Removed room assignment for non-confirmed pendaftaran {$pendaftaran->id}");
            } catch (\Exception $e) {
                Log::error("Failed to remove room assignment for pendaftaran {$pendaftaran->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Pendaftaran "deleted" event.
     */
    public function deleted(Pendaftaran $pendaftaran): void
    {
        try {
            $this->roomingService->removeRoomAssignment($pendaftaran->id);
            Log::info("Removed room assignment for deleted pendaftaran {$pendaftaran->id}");
        } catch (\Exception $e) {
            Log::error("Failed to remove room assignment for deleted pendaftaran {$pendaftaran->id}: " . $e->getMessage());
        }
    }

    /**
     * Handle the Pendaftaran "restored" event.
     */
    public function restored(Pendaftaran $pendaftaran): void
    {
        // If restored pendaftaran is confirmed, try to auto-assign room
        if ($pendaftaran->status === 'confirmed') {
            try {
                $this->roomingService->autoAssignRoom($pendaftaran->id);
                Log::info("Auto-assigned room for restored confirmed pendaftaran {$pendaftaran->id}");
            } catch (\Exception $e) {
                Log::error("Failed to auto-assign room for restored pendaftaran {$pendaftaran->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Pendaftaran "force deleted" event.
     */
    public function forceDeleted(Pendaftaran $pendaftaran): void
    {
        try {
            $this->roomingService->removeRoomAssignment($pendaftaran->id);
            Log::info("Removed room assignment for force deleted pendaftaran {$pendaftaran->id}");
        } catch (\Exception $e) {
            Log::error("Failed to remove room assignment for force deleted pendaftaran {$pendaftaran->id}: " . $e->getMessage());
        }
    }
}
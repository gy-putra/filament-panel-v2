<?php

namespace App\Observers;

use App\Models\PaketKeberangkatan;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for PaketKeberangkatan model to handle cache invalidation
 * 
 * This observer ensures that the homepage cache is cleared whenever
 * PaketKeberangkatan data is modified, ensuring real-time data display.
 */
class PaketKeberangkatanObserver
{
    /**
     * Clear homepage cache when data changes
     */
    private function clearHomepageCache(): void
    {
        // Clear all possible cache keys for the homepage
        // The cache keys follow the pattern: home.schedule.v1:month=X:year=Y:q=Z
        
        // Since we can't predict all possible filter combinations,
        // we'll use cache tags or clear by pattern
        // For now, we'll flush all cache keys that start with 'home.schedule.v1'
        
        // Get all cache keys (this is a simple approach, for production you might want to use cache tags)
        $cacheKeys = [
            'home.schedule.v1:month=any:year=any:q=none',
            'home.schedule.v1:month=1:year=any:q=none',
            'home.schedule.v1:month=2:year=any:q=none',
            'home.schedule.v1:month=3:year=any:q=none',
            'home.schedule.v1:month=4:year=any:q=none',
            'home.schedule.v1:month=5:year=any:q=none',
            'home.schedule.v1:month=6:year=any:q=none',
            'home.schedule.v1:month=7:year=any:q=none',
            'home.schedule.v1:month=8:year=any:q=none',
            'home.schedule.v1:month=9:year=any:q=none',
            'home.schedule.v1:month=10:year=any:q=none',
            'home.schedule.v1:month=11:year=any:q=none',
            'home.schedule.v1:month=12:year=any:q=none',
        ];
        
        // Clear common cache keys
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Also clear year-specific caches for current and next year
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;
        
        for ($month = 1; $month <= 12; $month++) {
            Cache::forget("home.schedule.v1:month={$month}:year={$currentYear}:q=none");
            Cache::forget("home.schedule.v1:month={$month}:year={$nextYear}:q=none");
            Cache::forget("home.schedule.v1:month=any:year={$currentYear}:q=none");
            Cache::forget("home.schedule.v1:month=any:year={$nextYear}:q=none");
        }
        
        // For a more comprehensive approach, we could also flush all cache
        // but this might affect other parts of the application
        // Cache::flush();
    }

    /**
     * Handle the PaketKeberangkatan "created" event.
     */
    public function created(PaketKeberangkatan $paketKeberangkatan): void
    {
        $this->clearHomepageCache();
    }

    /**
     * Handle the PaketKeberangkatan "updated" event.
     */
    public function updated(PaketKeberangkatan $paketKeberangkatan): void
    {
        $this->clearHomepageCache();
    }

    /**
     * Handle the PaketKeberangkatan "deleted" event.
     */
    public function deleted(PaketKeberangkatan $paketKeberangkatan): void
    {
        $this->clearHomepageCache();
    }

    /**
     * Handle the PaketKeberangkatan "restored" event.
     */
    public function restored(PaketKeberangkatan $paketKeberangkatan): void
    {
        $this->clearHomepageCache();
    }

    /**
     * Handle the PaketKeberangkatan "force deleted" event.
     */
    public function forceDeleted(PaketKeberangkatan $paketKeberangkatan): void
    {
        $this->clearHomepageCache();
    }
}
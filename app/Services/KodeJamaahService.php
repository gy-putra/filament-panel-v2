<?php

namespace App\Services;

use App\Models\Jamaah;
use Illuminate\Support\Facades\DB;

class KodeJamaahService
{
    /**
     * Generate the next kode_jamaah with format JM{YYYY}-{NNN}
     * 
     * @param string $prefix
     * @return string
     */
    public function next(string $prefix = 'JM'): string
    {
        $currentYear = date('Y');
        $yearPrefix = $prefix . $currentYear . '-';
        
        // Get the latest kode_jamaah for the current year
        $latestKode = Jamaah::withTrashed()
            ->where('kode_jamaah', 'like', $yearPrefix . '%')
            ->orderBy('kode_jamaah', 'desc')
            ->value('kode_jamaah');
        
        if ($latestKode) {
            // Extract the sequence number from the latest code
            $sequencePart = substr($latestKode, strlen($yearPrefix));
            $nextSequence = intval($sequencePart) + 1;
        } else {
            // First record for this year
            $nextSequence = 1;
        }
        
        // Format the sequence with leading zeros (3 digits)
        $formattedSequence = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
        
        return $yearPrefix . $formattedSequence;
    }
    
    /**
     * Check if a kode_jamaah already exists
     * 
     * @param string $kode
     * @return bool
     */
    public function exists(string $kode): bool
    {
        return Jamaah::withTrashed()
            ->where('kode_jamaah', $kode)
            ->exists();
    }
    
    /**
     * Generate a unique kode_jamaah (with retry mechanism)
     * 
     * @param string $prefix
     * @param int $maxRetries
     * @return string
     * @throws \Exception
     */
    public function generateUnique(string $prefix = 'JM', int $maxRetries = 10): string
    {
        $attempts = 0;
        
        do {
            $kode = $this->next($prefix);
            $attempts++;
            
            if (!$this->exists($kode)) {
                return $kode;
            }
            
            if ($attempts >= $maxRetries) {
                throw new \Exception("Unable to generate unique kode_jamaah after {$maxRetries} attempts");
            }
            
            // Small delay to avoid race conditions in high-concurrency scenarios
            usleep(1000); // 1ms
            
        } while ($attempts < $maxRetries);
        
        throw new \Exception("Unable to generate unique kode_jamaah");
    }
    
    /**
     * Get statistics for kode generation
     * 
     * @param int|null $year
     * @return array
     */
    public function getStats(?int $year = null): array
    {
        $year = $year ?? date('Y');
        $yearPrefix = 'JM' . $year . '-';
        
        $count = Jamaah::withTrashed()
            ->where('kode_jamaah', 'like', $yearPrefix . '%')
            ->count();
            
        $latestKode = Jamaah::withTrashed()
            ->where('kode_jamaah', 'like', $yearPrefix . '%')
            ->orderBy('kode_jamaah', 'desc')
            ->value('kode_jamaah');
            
        return [
            'year' => $year,
            'total_generated' => $count,
            'latest_code' => $latestKode,
            'next_sequence' => $count + 1,
            'next_code' => $this->next(),
        ];
    }
}
<?php

namespace App\Services;

use App\Models\Pendaftaran;
use Illuminate\Support\Facades\DB;

class KodePendaftaranService
{
    /**
     * Generate the next kode_pendaftaran with format PD{YYYY}-{NNN}
     * 
     * @param string $prefix
     * @return string
     */
    public function next(string $prefix = 'PD'): string
    {
        $currentYear = date('Y');
        $yearPrefix = $prefix . $currentYear . '-';
        
        // Get the latest kode_pendaftaran for the current year
        $latestKode = Pendaftaran::withTrashed()
            ->where('kode_pendaftaran', 'like', $yearPrefix . '%')
            ->orderBy('kode_pendaftaran', 'desc')
            ->value('kode_pendaftaran');
        
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
     * Check if a kode_pendaftaran already exists
     * 
     * @param string $kode
     * @return bool
     */
    public function exists(string $kode): bool
    {
        return Pendaftaran::withTrashed()
            ->where('kode_pendaftaran', $kode)
            ->exists();
    }
    
    /**
     * Generate a unique kode_pendaftaran
     * 
     * @param string $prefix
     * @return string
     */
    public function generate(string $prefix = 'PD'): string
    {
        do {
            $kode = $this->next($prefix);
        } while ($this->exists($kode));
        
        return $kode;
    }
    
    /**
     * Validate kode_pendaftaran format
     * 
     * @param string $kode
     * @return bool
     */
    public function isValidFormat(string $kode): bool
    {
        return preg_match('/^PD\d{4}-\d{3}$/', $kode);
    }
    
    /**
     * Get statistics for current year
     * 
     * @return array
     */
    public function getYearlyStats(): array
    {
        $currentYear = date('Y');
        $yearPrefix = 'PD' . $currentYear . '-';
        
        $count = Pendaftaran::withTrashed()
            ->where('kode_pendaftaran', 'like', $yearPrefix . '%')
            ->count();
            
        return [
            'year' => $currentYear,
            'total_registrations' => $count,
            'next_sequence' => $count + 1,
            'next_kode' => $this->next(),
        ];
    }
}
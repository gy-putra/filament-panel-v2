<?php

namespace App\Filament\Widgets;

use App\Models\Jamaah;
use App\Models\Pendaftaran;
use App\Models\PaketKeberangkatan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class QuickKpisWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Total Guests (Jamaah)
        $totalGuests = Jamaah::count();
        
        // Total Registrations (Pendaftaran)
        $totalRegistrations = Pendaftaran::count();
        
        // Total Departure Packages (all packages)
        $totalPackages = PaketKeberangkatan::count();
        
        // Upcoming Departures (next 30 days)
        $upcomingDepartures = PaketKeberangkatan::where('tgl_keberangkatan', '>=', now())
            ->where('tgl_keberangkatan', '<=', now()->addDays(30))
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();
        
        // Additional metrics for better insights
        $activeRegistrations = Pendaftaran::whereIn('status', ['confirmed', 'sudah_dp', 'lunas', 'dokumen_lengkap', 'siap_berangkat'])
            ->count();

        return [
            Stat::make('Total Jamaah', $totalGuests)
                ->description('Total Jamaah yang terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Total Pendaftaran', $totalRegistrations)
                ->description($activeRegistrations . ' pendaftaran aktif')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->chart([15, 4, 10, 2, 12, 4, 12]),
            
            Stat::make('Upcoming Keberangkatan', $upcomingDepartures)
                ->description('Next 30 days')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning')
                ->chart([2, 1, 3, 2, 1, 4, 2]),
            
            Stat::make('Total Paket Keberangkatan', $totalPackages)
                ->description('Total paket keberangkatan yang tersedia')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary')
                ->chart([1, 3, 2, 4, 3, 2, 5]),
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\PaketKeberangkatan;
use App\Services\KuotaService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class RemainingQuotaWidget extends Widget
{
    protected static string $view = 'filament.widgets.remaining-quota-widget';
    
    protected static ?string $pollingInterval = '30s';
    
    protected int | string | array $columnSpan = 'full';
    
    protected KuotaService $kuotaService;
    
    public function __construct()
    {
        $this->kuotaService = app(KuotaService::class);
    }
    
    public function getPackageQuotas(): Collection
    {
        return PaketKeberangkatan::where('status', 'open')
            ->where('tgl_keberangkatan', '>', now())
            ->orderBy('tgl_keberangkatan', 'asc')
            ->get()
            ->map(function ($package) {
                $remainingQuota = $this->kuotaService->getRemainingQuota($package->id);
                $percentageFilled = $package->kuota_total > 0 
                    ? round(($package->kuota_terisi / $package->kuota_total) * 100, 1)
                    : 0;
                
                return [
                    'id' => $package->id,
                    'nama_paket' => $package->nama_paket,
                    'kode_paket' => $package->kode_paket,
                    'tgl_keberangkatan' => $package->tgl_keberangkatan,
                    'kuota_total' => $package->kuota_total,
                    'kuota_terisi' => $package->kuota_terisi,
                    'kuota_tersisa' => $remainingQuota,
                    'percentage_filled' => $percentageFilled,
                    'status_color' => $this->getStatusColor($percentageFilled),
                    'status_text' => $this->getStatusText($remainingQuota, $package->kuota_total),
                ];
            });
    }
    
    private function getStatusColor(float $percentage): string
    {
        if ($percentage >= 90) return 'danger';
        if ($percentage >= 70) return 'warning';
        return 'success';
    }
    
    private function getStatusText(int $remaining, int $total): string
    {
        if ($remaining === 0) return 'FULL';
        if ($remaining <= 3) return 'ALMOST FULL';
        if ($remaining <= ($total * 0.3)) return 'LIMITED SEATS';
        return 'AVAILABLE';
    }
}
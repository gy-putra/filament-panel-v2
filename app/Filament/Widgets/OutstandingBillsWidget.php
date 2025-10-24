<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class OutstandingBillsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Get all unpaid invoices (active status means unpaid)
        $unpaidInvoices = Invoice::where('status', 'active')->get();
        
        // Calculate totals
        $totalUnpaidAmount = $unpaidInvoices->sum('total_amount');
        $totalUnpaidCount = $unpaidInvoices->count();
        
        // Calculate overdue invoices (older than 30 days)
        $overdueInvoices = $unpaidInvoices->where('tanggal_invoice', '<', now()->subDays(30));
        $totalOverdueAmount = $overdueInvoices->sum('total_amount');
        $totalOverdueCount = $overdueInvoices->count();
        
        // Recent invoices (last 7 days)
        $recentInvoices = Invoice::where('tanggal_invoice', '>=', now()->subDays(7))
            ->where('status', 'active')
            ->count();
        
        // Paid invoices this month for comparison
        $paidThisMonth = Invoice::where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('total_amount');

        return [
            Stat::make('Total Outstanding', 'Rp ' . number_format($totalUnpaidAmount, 0, ',', '.'))
                ->description($totalUnpaidCount . ' unpaid invoices')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart($this->getUnpaidTrendChart()),
            
            Stat::make('Overdue Bills', 'Rp ' . number_format($totalOverdueAmount, 0, ',', '.'))
                ->description($totalOverdueCount . ' overdue (>30 days)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart($this->getOverdueTrendChart()),
            
            Stat::make('Recent Bills', $recentInvoices)
                ->description('New invoices (7 days)')
                ->descriptionIcon('heroicon-m-document-plus')
                ->color('info')
                ->chart([1, 2, 3, 2, 4, 3, $recentInvoices]),
            
            Stat::make('Paid This Month', 'Rp ' . number_format($paidThisMonth, 0, ',', '.'))
                ->description('Successfully collected')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getPaidTrendChart()),
        ];
    }
    
    private function getUnpaidTrendChart(): array
    {
        // Get unpaid invoices count for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Invoice::where('status', 'active')
                ->whereDate('tanggal_invoice', '<=', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }
    
    private function getOverdueTrendChart(): array
    {
        // Get overdue invoices count for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Invoice::where('status', 'active')
                ->where('tanggal_invoice', '<', $date->copy()->subDays(30))
                ->count();
            $data[] = $count;
        }
        return $data;
    }
    
    private function getPaidTrendChart(): array
    {
        // Get paid invoices amount for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $amount = Invoice::where('status', 'paid')
                ->whereDate('updated_at', $date)
                ->sum('total_amount');
            $data[] = $amount / 1000000; // Convert to millions for chart readability
        }
        return $data;
    }
}
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TabunganAlokasi;
use App\Models\Tabungan;
use App\Services\SavingsLedgerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

try {
    // Test database connection first
    echo "Testing database connection...\n";
    $count = Tabungan::count();
    echo "Found {$count} tabungan records\n";
    
    // Get a draft allocation
    $alokasi = TabunganAlokasi::where('status', 'draft')->first();
    
    if (!$alokasi) {
        echo "No draft allocation found\n";
        exit(1);
    }
    
    echo "Testing allocation ID: {$alokasi->id}\n";
    echo "Status: {$alokasi->status}\n";
    echo "Nominal: {$alokasi->nominal}\n";
    
    // Test the actual postAllocation method
    echo "Testing postAllocation method...\n";
    $service = new SavingsLedgerService();
    $result = $service->postAllocation($alokasi);
    
    echo "PostAllocation completed successfully!\n";
    echo "Result: " . json_encode($result) . "\n";
    
    // Refresh and check status
    $alokasi->refresh();
    echo "Updated status: {$alokasi->status}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
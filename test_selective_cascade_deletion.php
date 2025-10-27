<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Models\PaketKeberangkatan;
use App\Models\Hotel;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Selective Cascade Deletion (Simplified) ===\n";

try {
    DB::beginTransaction();

    // 1. Create a simple PaketKeberangkatan using raw SQL to avoid model validation issues
    echo "Creating test PaketKeberangkatan...\n";
    $paketId = DB::table('paket_keberangkatan')->insertGetId([
        'kode_paket' => 'TEST-CASCADE-001',
        'nama_paket' => 'Test Cascade Package',
        'program_title' => 'Test Program',
        'tgl_keberangkatan' => '2025-03-01',
        'tgl_kepulangan' => '2025-03-15',
        'kuota_total' => 40,
        'kuota_terisi' => 0,
        'harga_paket' => 35000000.00,
        'status' => 'draft',
        'deskripsi' => 'Test package for cascade deletion',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // 2. Create a Hotel that references this package (should be set to NULL, not deleted)
    echo "Creating test Hotel...\n";
    $hotelId = DB::table('hotel')->insertGetId([
        'nama' => 'Test Hotel Cascade ' . time(),
        'kota' => 'Makkah',
        'paket_keberangkatan_id' => $paketId,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "\n=== Before Deletion ===\n";
    echo "PaketKeberangkatan count: " . DB::table('paket_keberangkatan')->count() . "\n";
    echo "Hotel count: " . DB::table('hotel')->count() . "\n";
    echo "Hotel paket_keberangkatan_id: " . DB::table('hotel')->where('id', $hotelId)->value('paket_keberangkatan_id') . "\n";

    // 3. Test selective cascade deletion using the model method
    echo "\n=== Performing Selective Cascade Deletion ===\n";
    $paket = PaketKeberangkatan::find($paketId);
    if ($paket) {
        $paket->cascadeDelete();
        echo "Cascade deletion method called successfully\n";
    } else {
        echo "ERROR: Could not find PaketKeberangkatan\n";
    }

    echo "\n=== After Deletion ===\n";
    echo "PaketKeberangkatan count: " . DB::table('paket_keberangkatan')->count() . "\n";
    echo "Hotel count: " . DB::table('hotel')->count() . "\n";
    
    $hotelPaketId = DB::table('hotel')->where('id', $hotelId)->value('paket_keberangkatan_id');
    echo "Hotel paket_keberangkatan_id after deletion: " . ($hotelPaketId ?? 'NULL') . "\n";

    echo "\n=== Test Results ===\n";
    
    // Verify results
    $paketExists = DB::table('paket_keberangkatan')->where('id', $paketId)->exists();
    $hotelExists = DB::table('hotel')->where('id', $hotelId)->exists();

    echo "✓ PaketKeberangkatan deleted: " . ($paketExists ? "NO (ERROR)" : "YES") . "\n";
    echo "✓ Hotel preserved: " . ($hotelExists ? "YES" : "NO (ERROR)") . "\n";
    echo "✓ Hotel paket_keberangkatan_id set to NULL: " . ($hotelPaketId === null ? "YES" : "NO (ERROR)") . "\n";

    // Cleanup
    echo "\n=== Cleanup ===\n";
    if ($hotelExists) {
        DB::table('hotel')->where('id', $hotelId)->delete();
        echo "Cleaned up Hotel\n";
    }

    DB::commit();
    echo "\n=== Test Completed Successfully ===\n";

} catch (Exception $e) {
    DB::rollback();
    echo "Error during test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
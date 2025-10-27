<?php

namespace Database\Seeders;

use App\Models\PaketKeberangkatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo seeder for homepage Umrah schedule testing.
 * 
 * Creates sample departure packages with various scenarios:
 * - Different package names for grouping
 * - Various seat availability scenarios (available, full)
 * - Different price configurations (some with null values)
 * - Future departure dates for testing
 */
class HomepageDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing demo data
        PaketKeberangkatan::where('kode_paket', 'like', 'DEMO-%')->delete();

        // Create sample packages for testing
        $packages = [
            [
                'kode_paket' => 'DEMO-RAM-001',
                'nama_paket' => 'Ramadhan Exclusive - Batch 1',
                'program_title' => 'Program Ramadhan 2025',
                'tgl_keberangkatan' => Carbon::today()->addDays(10),
                'tgl_kepulangan' => Carbon::today()->addDays(20),
                'kuota_total' => 40,
                'kuota_terisi' => 5,
                'harga_paket' => 25000000,
                'harga_quad' => 25000000,
                'harga_triple' => 27000000,
                'harga_double' => 30000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah eksklusif untuk bulan Ramadhan'
            ],
            [
                'kode_paket' => 'DEMO-RAM-002',
                'nama_paket' => 'Ramadhan Exclusive - Batch 2',
                'program_title' => 'Program Ramadhan 2025',
                'tgl_keberangkatan' => Carbon::today()->addDays(25),
                'tgl_kepulangan' => Carbon::today()->addDays(35),
                'kuota_total' => 45,
                'kuota_terisi' => 45, // Full package
                'harga_paket' => 26000000,
                'harga_quad' => 26000000,
                'harga_triple' => 28000000,
                'harga_double' => 31000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah eksklusif untuk bulan Ramadhan - batch 2'
            ],
            [
                'kode_paket' => 'DEMO-REG-001',
                'nama_paket' => 'Umrah Regular - Batch 1',
                'program_title' => 'Program Reguler 2025',
                'tgl_keberangkatan' => Carbon::today()->addDays(15),
                'tgl_kepulangan' => Carbon::today()->addDays(25),
                'kuota_total' => 35,
                'kuota_terisi' => 12,
                'harga_paket' => 22000000,
                'harga_quad' => 22000000,
                'harga_triple' => null, // Test null price
                'harga_double' => 28000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah regular dengan fasilitas standar'
            ],
            [
                'kode_paket' => 'DEMO-REG-002',
                'nama_paket' => 'Umrah Regular - Batch 2',
                'program_title' => 'Program Reguler 2025',
                'tgl_keberangkatan' => Carbon::today()->addDays(30),
                'tgl_kepulangan' => Carbon::today()->addDays(40),
                'kuota_total' => 30,
                'kuota_terisi' => 8,
                'harga_paket' => 23000000,
                'harga_quad' => 23000000,
                'harga_triple' => 25000000,
                'harga_double' => 29000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah regular dengan fasilitas standar - batch 2'
            ],
            [
                'kode_paket' => 'DEMO-VIP-001',
                'nama_paket' => 'VIP Premium - Batch 1',
                'program_title' => 'Program VIP Premium',
                'tgl_keberangkatan' => Carbon::today()->addDays(20),
                'tgl_kepulangan' => Carbon::today()->addDays(30),
                'kuota_total' => 20,
                'kuota_terisi' => 3,
                'harga_paket' => 35000000,
                'harga_quad' => null, // Test null price
                'harga_triple' => null, // Test null price
                'harga_double' => 45000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah VIP dengan fasilitas premium'
            ],
            [
                'kode_paket' => 'DEMO-ECO-001',
                'nama_paket' => 'Ekonomi Hemat - Batch 1',
                'program_title' => 'Program Ekonomi',
                'tgl_keberangkatan' => Carbon::today()->addDays(45),
                'tgl_kepulangan' => Carbon::today()->addDays(55),
                'kuota_total' => 50,
                'kuota_terisi' => 18,
                'harga_paket' => 18000000,
                'harga_quad' => 18000000,
                'harga_triple' => 20000000,
                'harga_double' => 24000000,
                'status' => 'open',
                'deskripsi' => 'Paket umrah ekonomis untuk jamaah dengan budget terbatas'
            ],
            // Add a closed package (should not appear on homepage)
            [
                'kode_paket' => 'DEMO-CLOSED-001',
                'nama_paket' => 'Paket Tertutup',
                'program_title' => 'Program Tertutup',
                'tgl_keberangkatan' => Carbon::today()->addDays(60),
                'tgl_kepulangan' => Carbon::today()->addDays(70),
                'kuota_total' => 25,
                'kuota_terisi' => 0,
                'harga_paket' => 20000000,
                'harga_quad' => 20000000,
                'harga_triple' => 22000000,
                'harga_double' => 26000000,
                'status' => 'closed', // This should not appear
                'deskripsi' => 'Paket yang sudah ditutup'
            ],
            // Add a past date package (should not appear on homepage)
            [
                'kode_paket' => 'DEMO-PAST-001',
                'nama_paket' => 'Paket Masa Lalu',
                'program_title' => 'Program Masa Lalu',
                'tgl_keberangkatan' => Carbon::today()->subDays(10),
                'tgl_kepulangan' => Carbon::today()->subDays(1),
                'kuota_total' => 25,
                'kuota_terisi' => 0,
                'harga_paket' => 20000000,
                'harga_quad' => 20000000,
                'harga_triple' => 22000000,
                'harga_double' => 26000000,
                'status' => 'open',
                'deskripsi' => 'Paket dengan tanggal keberangkatan masa lalu'
            ]
        ];

        foreach ($packages as $packageData) {
            PaketKeberangkatan::create($packageData);
        }

        $this->command->info('Homepage demo data created successfully!');
        $this->command->info('Created ' . count($packages) . ' sample departure packages.');
        $this->command->info('Note: Only packages with status "open" and future dates will appear on homepage.');
    }
}
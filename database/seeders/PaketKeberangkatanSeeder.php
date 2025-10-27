<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaketKeberangkatan;
use App\Models\UmrahProgram;
use Carbon\Carbon;

class PaketKeberangkatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing UmrahProgram IDs
        $umrahPrograms = UmrahProgram::all();
        
        if ($umrahPrograms->isEmpty()) {
            $this->command->error('No UmrahProgram records found. Please run UmrahProgramSeeder first.');
            return;
        }

        $packages = [
            [
                'nama_paket' => 'Umrah Reguler 12 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-REG')->first()?->id,
                'program_title' => null, // Will use UmrahProgram relationship
                'slug' => 'umrah-reguler-12-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(30),
                'kuota_total' => 45,
                'kuota_terisi' => 12,
                'harga_quad' => 25000000,
                'harga_triple' => 28000000,
                'harga_double' => 32000000,
                'status' => 'published'
            ],
            [
                'nama_paket' => 'Umrah Plus Dubai 14 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-PLUS')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-plus-dubai-14-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(45),
                'kuota_total' => 40,
                'kuota_terisi' => 25,
                'harga_quad' => 35000000,
                'harga_triple' => 38000000,
                'harga_double' => 42000000,
                'status' => 'published'
            ],
            [
                'nama_paket' => 'Umrah VIP Premium 10 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-VIP')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-vip-premium-10-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(60),
                'kuota_total' => 20,
                'kuota_terisi' => 18,
                'harga_quad' => 45000000,
                'harga_triple' => 48000000,
                'harga_double' => 52000000,
                'status' => 'published'
            ],
            [
                'nama_paket' => 'Umrah Ramadan Special 15 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-RAMADAN')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-ramadan-special-15-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(90),
                'kuota_total' => 50,
                'kuota_terisi' => 5,
                'harga_quad' => 40000000,
                'harga_triple' => 43000000,
                'harga_double' => 47000000,
                'status' => 'open'
            ],
            [
                'nama_paket' => 'Umrah Family Package 12 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-FAMILY')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-family-package-12-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(75),
                'kuota_total' => 35,
                'kuota_terisi' => 20,
                'harga_quad' => 30000000,
                'harga_triple' => 33000000,
                'harga_double' => 37000000,
                'status' => 'published'
            ],
            [
                'nama_paket' => 'Umrah Reguler Ekonomis 9 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-REG')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-reguler-ekonomis-9-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(120),
                'kuota_total' => 45,
                'kuota_terisi' => 8,
                'harga_quad' => 22000000,
                'harga_triple' => 25000000,
                'harga_double' => 29000000,
                'status' => 'open'
            ],
            [
                'nama_paket' => 'Umrah Plus Dubai Deluxe 16 Hari',
                'umrah_program_id' => $umrahPrograms->where('program_code', 'UMRH-PLUS')->first()?->id,
                'program_title' => null,
                'slug' => 'umrah-plus-dubai-deluxe-16-hari',
                'tgl_keberangkatan' => Carbon::now()->addDays(105),
                'kuota_total' => 30,
                'kuota_terisi' => 30, // Full package
                'harga_quad' => 42000000,
                'harga_triple' => 45000000,
                'harga_double' => 49000000,
                'status' => 'closed'
            ],
            [
                'nama_paket' => 'Legacy Package (Old System)',
                'umrah_program_id' => null, // No UmrahProgram association
                'program_title' => 'Legacy Umrah Package', // Uses old program_title
                'slug' => 'legacy-package-old-system',
                'tgl_keberangkatan' => Carbon::now()->addDays(150),
                'kuota_total' => 25,
                'kuota_terisi' => 10,
                'harga_quad' => 28000000,
                'harga_triple' => 31000000,
                'harga_double' => 35000000,
                'status' => 'draft'
            ]
        ];

        foreach ($packages as $packageData) {
            PaketKeberangkatan::create($packageData);
        }

        $this->command->info('PaketKeberangkatan test data created successfully!');
    }
}

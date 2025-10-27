<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaketKeberangkatan;
use App\Models\Itinerary;
use App\Models\HotelBooking;
use App\Models\FlightSegment;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Get the first package
        $package = PaketKeberangkatan::first();
        
        if (!$package) {
            $this->command->error('No PaketKeberangkatan found. Please run the main seeders first.');
            return;
        }

        // Create sample itinerary
        Itinerary::create([
            'paket_keberangkatan_id' => $package->id,
            'hari' => 1,
            'judul' => 'Arrival in Jakarta',
            'deskripsi' => '<p>Arrive at <strong>Soekarno-Hatta International Airport</strong>. Transfer to hotel and check-in.</p><ul><li>Airport pickup</li><li>Hotel check-in</li><li>Welcome dinner</li></ul>',
            'waktu' => '09:00'
        ]);

        Itinerary::create([
            'paket_keberangkatan_id' => $package->id,
            'hari' => 2,
            'judul' => 'City Tour Jakarta',
            'deskripsi' => '<p>Full day city tour visiting:</p><ul><li><strong>National Monument (Monas)</strong></li><li>Old Town (Kota Tua)</li><li>Istiqlal Mosque</li></ul><p>Lunch at local restaurant.</p>',
            'waktu' => '08:00'
        ]);

        // Create sample hotel booking
        HotelBooking::create([
            'paket_keberangkatan_id' => $package->id,
            'nama_hotel' => 'Grand Hyatt Jakarta',
            'alamat' => 'Jl. M.H. Thamrin No.28-30, Jakarta Pusat',
            'check_in' => now()->addDays(7)->format('Y-m-d'),
            'check_out' => now()->addDays(10)->format('Y-m-d'),
            'tipe_kamar' => 'Deluxe Room',
            'jumlah_kamar' => 2,
            'harga_per_malam' => 1500000
        ]);

        HotelBooking::create([
            'paket_keberangkatan_id' => $package->id,
            'nama_hotel' => 'The Ritz-Carlton Jakarta',
            'alamat' => 'Jl. DR. Ide Anak Agung Gde Agung, Jakarta Selatan',
            'check_in' => now()->addDays(10)->format('Y-m-d'),
            'check_out' => now()->addDays(13)->format('Y-m-d'),
            'tipe_kamar' => 'Executive Suite',
            'jumlah_kamar' => 1,
            'harga_per_malam' => 2500000
        ]);

        // Create sample flight segments
        FlightSegment::create([
            'paket_keberangkatan_id' => $package->id,
            'flight_number' => 'GA-200',
            'departure_airport' => 'CGK',
            'arrival_airport' => 'DPS',
            'departure_time' => now()->addDays(7)->setTime(10, 30)->format('Y-m-d H:i:s'),
            'arrival_time' => now()->addDays(7)->setTime(13, 15)->format('Y-m-d H:i:s'),
            'tipe' => 'domestic'
        ]);

        FlightSegment::create([
            'paket_keberangkatan_id' => $package->id,
            'flight_number' => 'SQ-955',
            'departure_airport' => 'DPS',
            'arrival_airport' => 'SIN',
            'departure_time' => now()->addDays(13)->setTime(14, 45)->format('Y-m-d H:i:s'),
            'arrival_time' => now()->addDays(13)->setTime(17, 30)->format('Y-m-d H:i:s'),
            'tipe' => 'international'
        ]);

        $this->command->info('Test data created successfully for package: ' . $package->nama_paket);
    }
}
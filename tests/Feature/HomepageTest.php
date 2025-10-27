<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\HomepageDemoSeeder;
use App\Models\PaketKeberangkatan;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Homepage Smoke Test
 * 
 * Tests the basic functionality of the homepage including:
 * - Page loads successfully
 * - Table headers are present
 * - Sample data is displayed correctly
 * - Price formatting works
 * - Seat availability is shown
 */
class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_homepage_with_schedule_table()
    {
        // Seed test data
        $this->seed(HomepageDemoSeeder::class);

        // Make request to homepage
        $response = $this->get('/');
        
        // Assert page loads successfully
        $response->assertOk();

        // Assert table headers are present in correct order
        $response->assertSeeTextInOrder([
            'Umrah Package', 'Seat', 'Quad', 'Triple', 'Double',
        ]);

        // Assert sample data is displayed
        $response->assertSeeText('Ramadhan Exclusive');
        $response->assertSeeText('5/40');
        $response->assertSeeText('Rp 25.000.000');
        $response->assertSeeText('Rp 27.000.000');
        $response->assertSeeText('Rp 30.000.000');
    }

    public function test_displays_correct_page_title_and_meta_description()
    {
        $response = $this->get('/');
        
        $response->assertOk();
        $response->assertSee('<title>Jadwal & Biaya Umrah - Nawita Tour</title>', false);
        $response->assertSee('<meta name="description" content="Lihat jadwal keberangkatan dan biaya paket umrah terbaru dari Nawita Tour. Pilih paket sesuai kebutuhan Anda.">', false);
    }

    public function test_handles_empty_state_when_no_departures_available()
    {
        // Clear any existing data
        PaketKeberangkatan::query()->delete();
        
        $response = $this->get('/');
        
        $response->assertOk();
        $response->assertSeeText('Belum ada jadwal keberangkatan yang tersedia saat ini.');
    }

    public function test_filters_by_month_parameter()
    {
        $this->seed(HomepageDemoSeeder::class);
        
        // Test with specific month filter
        $response = $this->get('/?month=2025-03');
        
        $response->assertOk();
        // Should still show table structure even if no results for that month
        $response->assertSeeText('Umrah Package');
    }

    public function test_filters_by_package_name_search()
    {
        $this->seed(HomepageDemoSeeder::class);
        
        // Test with package name search
        $response = $this->get('/?search=Ramadhan');
        
        $response->assertOk();
        $response->assertSeeText('Ramadhan Exclusive');
    }

    public function test_shows_full_when_seats_are_not_available()
    {
        // Create a package with no available seats
        PaketKeberangkatan::create([
            'kode_paket' => 'FULL001',
            'nama_paket' => 'Full Package Test',
            'kuota_total' => 40,
            'kuota_terisi' => 40, // Full capacity
            'tgl_keberangkatan' => now()->addDays(30),
            'tgl_kepulangan' => now()->addDays(45),
            'status' => 'open',
            'harga_paket' => 25000000,
            'harga_quad' => 25000000,
        ]);
        
        $response = $this->get('/');
        
        $response->assertOk();
        $response->assertSeeText('FULL');
    }

    public function test_shows_contact_us_for_missing_prices()
    {
        // Create a package with missing prices
        PaketKeberangkatan::create([
            'kode_paket' => 'NOPRICE001',
            'nama_paket' => 'No Price Package',
            'kuota_total' => 40,
            'kuota_terisi' => 10,
            'tgl_keberangkatan' => now()->addDays(30),
            'tgl_kepulangan' => now()->addDays(45),
            'status' => 'open',
            'harga_paket' => 25000000,
            'harga_quad' => null, // Missing price
            'harga_triple' => null,
            'harga_double' => null,
        ]);
        
        $response = $this->get('/');
        
        $response->assertOk();
        $response->assertSeeText('Contact us');
    }
}
<?php

namespace Tests\Feature;

use App\Models\PaketKeberangkatan;
use App\Models\Itinerary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * DetailPaketTest - Tests for package detail page functionality
 * 
 * Tests that the detail page returns 200, shows "Details" and "Description" tabs,
 * and displays seeded day/title content correctly.
 */
class DetailPaketTest extends TestCase
{
    use RefreshDatabase;

    protected PaketKeberangkatan $testPackage;
    protected array $testItineraries;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test package with slug
        $this->testPackage = PaketKeberangkatan::create([
            'kode_paket' => 'TEST-001',
            'nama_paket' => 'Test Umrah Package',
            'slug' => 'test-umrah-package',
            'tgl_keberangkatan' => Carbon::now()->addDays(30),
            'tgl_kepulangan' => Carbon::now()->addDays(45),
            'kuota_total' => 40,
            'kuota_terisi' => 10,
            'harga_paket' => 30000000,
            'status' => 'open',
            'deskripsi' => 'Test package description for testing purposes.',
        ]);

        // Create test itineraries
        $this->testItineraries = [
            [
                'paket_keberangkatan_id' => $this->testPackage->id,
                'hari_ke' => 1,
                'tanggal' => $this->testPackage->tgl_keberangkatan,
                'judul' => 'Day 1 - Departure',
                'deskripsi' => 'Departure from Soekarno-Hatta Airport',
            ],
            [
                'paket_keberangkatan_id' => $this->testPackage->id,
                'hari_ke' => 2,
                'tanggal' => $this->testPackage->tgl_keberangkatan->addDay(),
                'judul' => 'Day 2 - Arrival',
                'deskripsi' => 'Arrival and transfer to Madinah',
            ],
            [
                'paket_keberangkatan_id' => $this->testPackage->id,
                'hari_ke' => 3,
                'tanggal' => $this->testPackage->tgl_keberangkatan->addDays(2),
                'judul' => 'Day 3 - Ziarah',
                'deskripsi' => 'Ziarah at Masjid Nabawi and Raudhah',
            ],
        ];

        foreach ($this->testItineraries as $itinerary) {
            Itinerary::create($itinerary);
        }
    }

    /** @test */
    public function it_returns_200_for_valid_package_slug()
    {
        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_shows_details_and_description_tabs()
    {
        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        $response->assertSee('Details');
        $response->assertSee('Description');
        
        // Check for ARIA attributes for accessibility
        $response->assertSee('role="tablist"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('role="tabpanel"', false);
    }

    /** @test */
    public function it_displays_seeded_day_and_title_content()
    {
        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        // Check that itinerary content is displayed
        foreach ($this->testItineraries as $itinerary) {
            $response->assertSee("Hari {$itinerary['hari_ke']}");
            $response->assertSee($itinerary['judul']);
        }
    }

    /** @test */
    public function it_displays_package_information()
    {
        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        // Check package name in title
        $response->assertSee($this->testPackage->nama_paket);
        
        // Check package description
        $response->assertSee($this->testPackage->deskripsi);
    }

    /** @test */
    public function it_includes_seo_elements()
    {
        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        // Check for SEO title
        $response->assertSee("<title>Umrah Package — {$this->testPackage->nama_paket} | Nawita Tour</title>", false);
        
        // Check for meta description
        $response->assertSee('<meta name="description"', false);
        
        // Check for canonical link
        $response->assertSee('<link rel="canonical"', false);
        
        // Check for JSON-LD breadcrumbs
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    /** @test */
    public function it_returns_404_for_invalid_slug()
    {
        $response = $this->get('/detailpaket/non-existent-slug');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_for_non_public_package()
    {
        // Create a closed/archived package
        $closedPackage = PaketKeberangkatan::create([
            'kode_paket' => 'CLOSED-001',
            'nama_paket' => 'Closed Package',
            'slug' => 'closed-package',
            'tgl_keberangkatan' => Carbon::now()->addDays(30),
            'tgl_kepulangan' => Carbon::now()->addDays(45),
            'kuota_total' => 40,
            'kuota_terisi' => 10,
            'harga_paket' => 30000000,
            'status' => 'closed', // Non-public status
            'deskripsi' => 'Closed package description.',
        ]);

        $response = $this->get("/detailpaket/{$closedPackage->slug}");
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_return_query_parameter()
    {
        $returnUrl = urlencode('http://localhost/?filter=premium');
        
        $response = $this->get("/detailpaket/{$this->testPackage->slug}?return={$returnUrl}");
        
        $response->assertStatus(200);
        $response->assertSee('Back to results');
    }

    /** @test */
    public function it_orders_itineraries_by_day_then_date()
    {
        // Create additional itinerary with same day but different date
        Itinerary::create([
            'paket_keberangkatan_id' => $this->testPackage->id,
            'hari_ke' => 1,
            'tanggal' => $this->testPackage->tgl_keberangkatan->subDay(),
            'judul' => 'Day 1 - Earlier Activity',
            'deskripsi' => 'This should appear before the other day 1 activity',
        ]);

        $response = $this->get("/detailpaket/{$this->testPackage->slug}");
        
        $content = $response->getContent();
        
        // Check that earlier activity appears before later activity for same day
        $earlierPos = strpos($content, 'Day 1 - Earlier Activity');
        $laterPos = strpos($content, 'Day 1 - Departure');
        
        $this->assertNotFalse($earlierPos);
        $this->assertNotFalse($laterPos);
        $this->assertLessThan($laterPos, $earlierPos);
    }
}
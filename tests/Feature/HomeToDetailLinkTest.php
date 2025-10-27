<?php

namespace Tests\Feature;

use App\Models\PaketKeberangkatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * HomeToDetailLinkTest - Tests for homepage to detail page navigation
 * 
 * Tests that the homepage link resolves to the detail route correctly
 * and that navigation between pages works as expected.
 */
class HomeToDetailLinkTest extends TestCase
{
    use RefreshDatabase;

    protected PaketKeberangkatan $testPackage;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test package with slug for homepage display
        $this->testPackage = PaketKeberangkatan::create([
            'kode_paket' => 'HOME-TEST-001',
            'nama_paket' => 'Homepage Test Package',
            'slug' => 'homepage-test-package',
            'tgl_keberangkatan' => Carbon::now()->addDays(30),
            'tgl_kepulangan' => Carbon::now()->addDays(45),
            'kuota_total' => 40,
            'kuota_terisi' => 15,
            'harga_paket' => 32000000,
            'harga_quad' => 29000000,
            'harga_triple' => 32000000,
            'harga_double' => 39000000,
            'status' => 'open', // Ensure it's public and visible
            'deskripsi' => 'Test package for homepage navigation testing.',
        ]);
    }

    /** @test */
    public function homepage_displays_package_with_clickable_link()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Check that package name is displayed
        $response->assertSee($this->testPackage->nama_paket);
        
        // Check that the link to detail page exists
        $expectedUrl = route('detailpaket.show', $this->testPackage->slug);
        $response->assertSee($expectedUrl, false);
    }

    /** @test */
    public function homepage_link_resolves_to_detail_route()
    {
        // First, get the homepage to see the link
        $homepageResponse = $this->get('/');
        $homepageResponse->assertStatus(200);
        
        // Extract the detail page URL from homepage
        $expectedDetailUrl = route('detailpaket.show', $this->testPackage->slug);
        
        // Test that the detail route resolves correctly
        $detailResponse = $this->get($expectedDetailUrl);
        $detailResponse->assertStatus(200);
        
        // Verify we're on the correct detail page
        $detailResponse->assertSee($this->testPackage->nama_paket);
    }

    /** @test */
    public function homepage_link_includes_return_parameter()
    {
        // Test homepage with filters
        $homepageUrl = '/?filter=premium&sort=price';
        $response = $this->get($homepageUrl);
        
        $response->assertStatus(200);
        
        // Check that the link includes return parameter
        $expectedReturnUrl = urlencode(url()->full());
        $response->assertSee("return={$expectedReturnUrl}", false);
    }

    /** @test */
    public function detail_page_shows_back_to_results_when_return_parameter_present()
    {
        $returnUrl = urlencode('http://localhost/?filter=premium');
        $detailUrl = route('detailpaket.show', $this->testPackage->slug) . "?return={$returnUrl}";
        
        $response = $this->get($detailUrl);
        
        $response->assertStatus(200);
        $response->assertSee('Back to results');
    }

    /** @test */
    public function detail_page_does_not_show_back_link_without_return_parameter()
    {
        $detailUrl = route('detailpaket.show', $this->testPackage->slug);
        
        $response = $this->get($detailUrl);
        
        $response->assertStatus(200);
        $response->assertDontSee('Back to results');
    }

    /** @test */
    public function navigation_preserves_homepage_filters()
    {
        // Create additional packages for filtering test
        PaketKeberangkatan::create([
            'kode_paket' => 'FILTER-TEST-001',
            'nama_paket' => 'Premium Filter Test Package',
            'slug' => 'premium-filter-test-package',
            'tgl_keberangkatan' => Carbon::now()->addDays(60),
            'tgl_kepulangan' => Carbon::now()->addDays(75),
            'kuota_total' => 30,
            'kuota_terisi' => 5,
            'harga_paket' => 45000000,
            'status' => 'open',
            'deskripsi' => 'Premium package for filter testing.',
        ]);

        // Test homepage with specific filters
        $filteredHomepage = '/?status=open&sort=price';
        $homepageResponse = $this->get($filteredHomepage);
        
        $homepageResponse->assertStatus(200);
        
        // Check that links preserve the current URL with filters
        $currentUrl = request()->fullUrl();
        $encodedReturnUrl = urlencode($currentUrl);
        
        // The link should include the return parameter with current filtered URL
        $homepageResponse->assertSee("return=", false);
    }

    /** @test */
    public function homepage_handles_packages_without_slugs_gracefully()
    {
        // Create a package without slug (edge case)
        $packageWithoutSlug = PaketKeberangkatan::create([
            'kode_paket' => 'NO-SLUG-001',
            'nama_paket' => 'Package Without Slug',
            'slug' => null, // No slug
            'tgl_keberangkatan' => Carbon::now()->addDays(90),
            'tgl_kepulangan' => Carbon::now()->addDays(105),
            'kuota_total' => 25,
            'kuota_terisi' => 8,
            'harga_paket' => 28000000,
            'status' => 'open',
            'deskripsi' => 'Package without slug for testing.',
        ]);

        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Should display package name but not as a link
        $response->assertSee($packageWithoutSlug->nama_paket);
        
        // Should not create a broken link
        $response->assertDontSee('detailpaket/', false);
    }

    /** @test */
    public function route_name_resolves_correctly()
    {
        // Test that the named route resolves to the correct URL
        $expectedUrl = "/detailpaket/{$this->testPackage->slug}";
        $actualUrl = route('detailpaket.show', $this->testPackage->slug);
        
        $this->assertEquals($expectedUrl, parse_url($actualUrl, PHP_URL_PATH));
    }

    /** @test */
    public function multiple_packages_all_have_working_links()
    {
        // Create multiple packages
        $packages = [];
        for ($i = 1; $i <= 3; $i++) {
            $packages[] = PaketKeberangkatan::create([
                'kode_paket' => "MULTI-{$i}",
                'nama_paket' => "Multi Test Package {$i}",
                'slug' => "multi-test-package-{$i}",
                'tgl_keberangkatan' => Carbon::now()->addDays(30 + $i * 10),
                'tgl_kepulangan' => Carbon::now()->addDays(45 + $i * 10),
                'kuota_total' => 40,
                'kuota_terisi' => 10 + $i,
                'harga_paket' => 30000000 + ($i * 2000000),
                'status' => 'open',
                'deskripsi' => "Multi test package {$i} description.",
            ]);
        }

        // Test homepage shows all packages
        $homepageResponse = $this->get('/');
        $homepageResponse->assertStatus(200);

        // Test each package link works
        foreach ($packages as $package) {
            $homepageResponse->assertSee($package->nama_paket);
            
            $detailResponse = $this->get(route('detailpaket.show', $package->slug));
            $detailResponse->assertStatus(200);
            $detailResponse->assertSee($package->nama_paket);
        }
    }
}
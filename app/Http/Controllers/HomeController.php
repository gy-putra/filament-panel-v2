<?php

namespace App\Http\Controllers;

use App\Models\PaketKeberangkatan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Display the public Umrah Schedule & Costs table.
 *
 * Loads only public, non-archived, future (or today) departures from PaketKeberangkatan.
 * Supports query-string filters (month/year, q) and returns a grouped dataset 
 * keyed by package_name. Data is cached briefly to keep the homepage fast under typical traffic.
 */
class HomeController extends Controller
{
    /**
     * Display the homepage with Umrah schedule and costs.
     * 
     * Fetches active departure packages, applies filters, computes seat availability,
     * formats prices, and groups by package name. Results are cached for performance.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $month = (int) $request->query('month', 0);
        $year  = (int) $request->query('year', 0);
        $q     = trim((string) $request->query('q', ''));

        // Create cache key based on filters
        $cacheKey = sprintf(
            'home.schedule.v1:month=%s:year=%s:q=%s',
            $month ?: 'any',
            $year  ?: 'any',
            $q     ?: 'none'
        );

        // Cache the grouped dataset for 12 minutes
        $grouped = Cache::remember($cacheKey, now()->addMinutes(12), function () use ($month, $year, $q) {
            // Build query for open packages only
            $query = PaketKeberangkatan::query()
                ->where('status', 'open'); // Only show open packages

            // Apply month filter
            if ($month > 0 && $month <= 12) {
                $query->whereMonth('tgl_keberangkatan', $month);
            }

            // Apply year filter
            if ($year > 0) {
                $query->whereYear('tgl_keberangkatan', $year);
            }

            // Apply package name search
            if ($q !== '') {
                $query->where('nama_paket', 'like', '%' . $q . '%');
            }

            // Fetch packages ordered by closest departure date first
            $packages = $query
                ->with('umrahProgram') // Eager load the UmrahProgram relationship
                ->orderBy('tgl_keberangkatan')
                ->get([
                    'id', 'nama_paket', 'program_title', 'umrah_program_id', 'slug', 'tgl_keberangkatan',
                    'kuota_total', 'kuota_terisi',
                    'harga_quad', 'harga_triple', 'harga_double',
                    'status'
                ]);

            // Transform and group by program title (prioritize UmrahProgram, fallback to program_title)
            $byPackage = $packages->map(function ($pkg) {
                $total = (int) ($pkg->kuota_total ?? 0);
                $reserved = (int) ($pkg->kuota_terisi ?? 0);
                $available = max($total - $reserved, 0);

                // Use UmrahProgram name if available, otherwise fallback to program_title
                $programTitle = $pkg->umrahProgram?->program_name ?? $pkg->program_title ?? 'Program Tidak Diketahui';

                return [
                    'id'              => $pkg->id,
                    'package_name'    => $pkg->nama_paket,
                    'program_title'   => $programTitle,
                    'slug'            => $pkg->slug, // Include slug for detail page linking
                    'departure_date'  => $pkg->tgl_keberangkatan,
                    'seats_total'     => $total,
                    'seats_reserved'  => $reserved,
                    'seats_available' => $available,
                    'quad_price'      => $pkg->harga_quad,
                    'triple_price'    => $pkg->harga_triple,
                    'double_price'    => $pkg->harga_double,
                ];
            })->groupBy('program_title');

            return $byPackage;
        });

        return view('home', [
            'grouped'   => $grouped, // Collection<string, Collection<array>>
            'filters'   => [
                'month' => $month ?: null, 
                'year' => $year ?: null, 
                'q' => $q ?: null
            ],
            'pageTitle' => 'Umrah Schedule & Costs | ' . config('app.name'),
            'metaDesc'  => 'Compare upcoming Umrah packages, seat availability, and Quad/Triple/Double prices. Updated daily.',
        ]);
    }

    /**
     * Display the package detail page with comprehensive information.
     * 
     * Shows detailed information about a specific package including description,
     * itinerary, hotel bookings, and flight segments in a tabbed interface.
     *
     * @param PaketKeberangkatan $paket
     * @return View
     */
    public function detailPaket(PaketKeberangkatan $paket): View
    {
        // Load all related data for the package
        $paket->load([
            'itinerary' => function ($query) {
                $query->orderBy('hari_ke');
            },
            'hotelBookings.hotel',
            'flightSegments.maskapai'
        ]);

        return view('detailpaket', [
            'paket' => $paket,
            'pageTitle' => $paket->nama_paket . ' - Detail Paket Umrah | ' . config('app.name'),
            'metaDesc' => 'Detail lengkap paket umrah ' . $paket->nama_paket . ' termasuk itinerary, hotel, dan informasi penerbangan.',
        ]);
    }
}
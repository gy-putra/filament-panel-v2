
# PRD — Public Homepage “Umrah Schedule & Costs” (Laravel Blade)

**Author:** Nawitatour Team  
**Date:** 2025‑10‑25  
**Status:** Draft → Implementable  
**Target Stack:** Laravel 10/11, Blade, PHP 8.2/8.3, MySQL, Tailwind (optional)

---

## 1) Objective

Rewrite and implement the public homepage (`GET /`) so it cleanly presents an **“Umrah Schedule & Costs”** table sourced from the existing **Departure Package** domain. The page must be fast, accessible, SEO‑friendly, and easy to extend (component‑ready).

---

## 2) Scope

- Build a dedicated `HomeController@index` for `GET /`.
- Load **only active/future & public** departures from the Departure Package data, grouped by **Package Name**.
- Render a responsive, accessible table with these **columns in order**:
  1. **Umrah Package**
  2. **Seat** (show `Available/Total`; if available ≤ 0 display **FULL**)
  3. **Quad**
  4. **Triple**
  5. **Double**
- **Price rules:** pull from the correct fields on `DeparturePackage` or a related `Price` model; format as **Indonesian Rupiah** with **no decimals** (e.g., `Rp 25.000.000`). Show **“Contact us”** if a tier price is `null`.
- Sorting by the **closest `departure_date` first**.
- Query‑string filters:
  - **Month/Year** (SEO‑friendly; e.g., `/?month=10&year=2025`).
  - **Full‑text search** on **package name** (e.g., `/?q=rama`).
- Keep business logic in controller (or a small *view model*), not in Blade.
- Compute `seats_available = seats_total - seats_reserved` on the server.
- Add **basic caching** (10–15 minutes) for the grouped dataset; **eager‑load** relations to avoid N+1.
- Build the Blade at `resources/views/home.blade.php` using **semantic HTML**, **responsive layout** (stack on small screens), **accessible table markup** (caption, `<th scope="col">`, and group headers with `<tr role="rowheader">`).
- Only show **public, non‑archived** packages; **guard against missing data**.
- Include concise **PHPDoc** on the controller method.
- Add a quick **smoke test** (Pest or PHPUnit) asserting:
  - `GET /` returns 200
  - renders expected columns
  - renders seeded package prices (Quad/Triple/Double)
- Basic **SEO** (title `Umrah Schedule & Costs | <SiteName>`, meta description).
- Design: **neutral**, production‑ready, **no external JS**. Tailwind allowed if available; otherwise, inline lightweight CSS.

**Out of Scope / Non‑Goals**
- Admin (Filament) side changes.
- Advanced faceted search or pagination.
- Currency conversion, multi‑language, or price negotiation flows.

---

## 3) Data Model & Assumptions

**Primary source:** `DeparturePackage` (table name may vary).  
Assumed fields (adjust in implementation if naming differs):
- `id` (bigint), `package_name` (string, required)
- `departure_date` (date), indexed
- `is_public` (bool), `is_archived` (bool)
- `seats_total` (int), `seats_reserved` (int, defaults to 0)
- Optional relation: `prices()` → `HasOne Price` with columns:
  - `quad_price` (decimal / integer in IDR)
  - `triple_price` (decimal / integer in IDR)
  - `double_price` (decimal / integer in IDR)

> **Config Note:** If prices live directly on `DeparturePackage`, map accordingly in the controller (see §6).

Business rule:
```
seats_available = max( (int)seats_total - (int)seats_reserved, 0 )
Seat label:
  - if seats_available <= 0 → "FULL"
  - else → "{seats_available}/{seats_total}"
```

---

## 4) User Stories & Acceptance Criteria

### US‑1 View Schedule
**As a visitor**, I want to see upcoming Umrah departures grouped by package name so that I can quickly compare seats and prices.

**Acceptance**
- Table appears with a **caption** and **five columns** (Umrah Package, Seat, Quad, Triple, Double).
- Rows are grouped by package name: each group begins with a **section header row** (spanning 5 columns) labeled with the package name.
- Inside each group, departures are listed (soonest date first).

### US‑2 Prices
**As a visitor**, I want to see Quad/Triple/Double prices where available; otherwise a clear fallback.

**Acceptance**
- Prices render as formatted **IDR** (`Rp 12.345.678`) with **no decimals**.
- If price is `null`, show **“Contact us”** (not “0”).

### US‑3 Seats
**As a visitor**, I need to know seat availability at a glance.

**Acceptance**
- Seat column shows either **`Available/Total`** (e.g., `5/40`) or **`FULL`** when `seats_available ≤ 0`.

### US‑4 Filtering & Search
**As a visitor**, I can filter by month/year and search by package name using the URL so I can share/bookmark it.

**Acceptance**
- `/?month=MM&year=YYYY` filters by departure_date month/year.
- `/?q=term` filters package name (case‑insensitive, `%LIKE%`).
- Filters are **composable** (e.g., `/?month=10&year=2025&q=rama`).

### US‑5 Performance & Reliability
**As an operator**, I need the page to be fast and stable.

**Acceptance**
- The controller uses **eager‑loading** and **caching** (~10–15 minutes).
- No N+1 queries. Laravel Debugbar (if enabled) shows consistent query count.

### US‑6 Accessibility & SEO
**As a visitor**, I can navigate with screen readers and find the page on search engines.

**Acceptance**
- Semantic, accessible table markup.
- `<title>` and `<meta name="description">` set appropriately.
- Mobile friendly (columns stack on small screens).

---

## 5) Routing

Add to `routes/web.php`:
```php
<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
```
> **Comment:** Keep `GET /` public and cacheable. Consider `GET /umrah-schedule` 301 → `/` if migrating from an older URL.

---

## 6) Controller (Business Logic & Caching)

**File:** `app/Http/Controllers/HomeController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\DeparturePackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Display the public Umrah Schedule & Costs table.
 *
 * Loads only public, non-archived, future (or today) departures.
 * Supports query-string filters (month/year, q) and returns
 * a grouped dataset keyed by package_name. Data is cached briefly
 * to keep the homepage fast under typical traffic.
 */
class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $month = (int) $request->query('month', 0);
        $year  = (int) $request->query('year', 0);
        $q     = trim((string) $request->query('q', ''));

        $cacheKey = sprintf(
            'home.schedule.v1:month=%s:year=%s:q=%s',
            $month ?: 'any',
            $year  ?: 'any',
            $q     ?: 'none'
        );

        $grouped = Cache::remember($cacheKey, now()->addMinutes(12), function () use ($month, $year, $q) {
            $today = \Illuminate\Support\Carbon::today();

            $query = DeparturePackage::query()
                ->with(['prices']) // adjust/remove if prices are on the same table
                ->where('is_public', true)
                ->where('is_archived', false)
                ->whereDate('departure_date', '>=', $today);

            if ($month > 0 && $month <= 12) {
                $query->whereMonth('departure_date', $month);
            }
            if ($year > 0) {
                $query->whereYear('departure_date', $year);
            }
            if ($q !== '') {
                $query->where('package_name', 'like', '%' . $q . '%');
            }

            $packages = $query
                ->orderBy('departure_date') // closest first
                ->get([
                    'id', 'package_name', 'departure_date',
                    'seats_total', 'seats_reserved',
                    'is_public', 'is_archived',
                ]);

            // Transform and group
            $byPackage = $packages->map(function ($pkg) {
                $total = (int) ($pkg->seats_total ?? 0);
                $reserved = (int) ($pkg->seats_reserved ?? 0);
                $available = max($total - $reserved, 0);

                // Resolve prices whether via relation or same table
                $quad   = data_get($pkg, 'prices.quad_price', data_get($pkg, 'quad_price'));
                $triple = data_get($pkg, 'prices.triple_price', data_get($pkg, 'triple_price'));
                $double = data_get($pkg, 'prices.double_price', data_get($pkg, 'double_price'));

                return [
                    'id'              => $pkg->id,
                    'package_name'    => $pkg->package_name,
                    'departure_date'  => $pkg->departure_date,
                    'seats_total'     => $total,
                    'seats_reserved'  => $reserved,
                    'seats_available' => $available,
                    'quad_price'      => $quad,
                    'triple_price'    => $triple,
                    'double_price'    => $double,
                ];
            })->groupBy('package_name');

            return $byPackage;
        });

        return view('home', [
            'grouped'   => $grouped, // Collection<string, Collection<array>>
            'filters'   => ['month' => $month ?: null, 'year' => $year ?: null, 'q' => $q ?: null],
            'pageTitle' => 'Umrah Schedule & Costs | ' . config('app.name'),
            'metaDesc'  => 'Compare upcoming Umrah packages, seat availability, and Quad/Triple/Double prices. Updated daily.',
        ]);
    }
}
```

---

## 7) Blade View (Semantic, Responsive, Accessible)

**File:** `resources/views/home.blade.php`

```blade
{{--
  Home schedule table.
  Future enhancement: extract to <x-umrah/schedule-table> and <x-umrah/filters> components.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ('Umrah Schedule & Costs | ' . config('app.name')) }}</title>
    <meta name="description" content="{{ $metaDesc ?? '' }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        /* lightweight responsive table if Tailwind is unavailable */
        .container { max-width: 1000px; margin: 0 auto; padding: 1rem; }
        .filters { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        caption { text-align: left; margin-bottom: .5rem; font-weight: 600; }
        th, td { border: 1px solid #e5e7eb; padding: .5rem .75rem; vertical-align: top; }
        th[scope="col"] { background: #f9fafb; }
        tr[role="rowheader"] td { background: #f3f4f6; font-weight: 600; }
        .seat-full { font-weight: 700; color: #b91c1c; }
        @media (max-width: 640px) {
            .table-wrap { overflow-x: auto; }
            table { font-size: .9rem; }
        }
    </style>
</head>
<body>
<div class="container">

    <h1 class="sr-only">Umrah Schedule &amp; Costs</h1>

    {{-- Filters (query-string based, SEO-friendly) --}}
    <form role="search" class="filters" method="get" action="{{ route('home') }}">
        <input type="number" name="month" min="1" max="12" value="{{ request('month') }}" placeholder="Month (1-12)">
        <input type="number" name="year" min="2000" max="2100" value="{{ request('year') }}" placeholder="Year">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search package name…">
        <button type="submit">Filter</button>
        <a href="{{ route('home') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table aria-describedby="table-desc">
            <caption id="table-desc">Upcoming Umrah departures grouped by package name</caption>
            <thead>
            <tr>
                <th scope="col">Umrah Package</th>
                <th scope="col">Seat</th>
                <th scope="col">Quad</th>
                <th scope="col">Triple</th>
                <th scope="col">Double</th>
            </tr>
            </thead>
            <tbody>
            @forelse($grouped as $packageName => $rows)
                <tr role="rowheader">
                    <td colspan="5">{{ $packageName }}</td>
                </tr>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['departure_date'])->isoFormat('DD MMM YYYY') }}</td>
                        <td>
                            @php
                                $total = (int) ($row['seats_total'] ?? 0);
                                $avail = (int) ($row['seats_available'] ?? 0);
                            @endphp
                            @if($avail <= 0)
                                <span class="seat-full">FULL</span>
                            @else
                                {{ $avail }}/{{ $total }}
                            @endif
                        </td>
                        <td>
                            {{ is_null($row['quad_price'] ?? null) ? 'Contact us' : 'Rp ' . number_format((int)$row['quad_price'], 0, ',', '.') }}
                        </td>
                        <td>
                            {{ is_null($row['triple_price'] ?? null) ? 'Contact us' : 'Rp ' . number_format((int)$row['triple_price'], 0, ',', '.') }}
                        </td>
                        <td>
                            {{ is_null($row['double_price'] ?? null) ? 'Contact us' : 'Rp ' . number_format((int)$row['double_price'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5">No departures found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
```

**Notes**
- For Tailwind projects, add utility classes in place of the inline CSS.
- To extract later into components, start with `<x-umrah/schedule-table>` and `<x-umrah/filters>` using the same data contract (`$grouped`, `$filters`).

---

## 8) Performance, Caching, and N+1 Avoidance

- Use `->with(['prices'])` or appropriate relations to prefetch prices.
- Cache key includes filter params (`month`, `year`, `q`) to avoid mixing results.
- Cache TTL: **12 minutes** (tunable 10–15). Bust cache on create/update/delete of relevant models if needed (future improvement).

---

## 9) Error Handling & Data Guardrails

- If `seats_total` or `seats_reserved` are missing, coerce to `0` and compute availability safely.
- If price fields are missing or null, display **“Contact us”**.
- Only show records where `is_public = 1` and `is_archived = 0` and `departure_date >= today()`.

---

## 10) SEO

- Title: `Umrah Schedule & Costs | <SiteName>` (use `config('app.name')`).
- Meta description: “Compare upcoming Umrah packages, seat availability, and Quad/Triple/Double prices. Updated daily.”
- Clean, sharable URLs with query‑string filters.

---

## 11) Testing

Use **Pest** (or PHPUnit).

**Seed Fixture (example):**
```php
// database/seeders/HomepageDemoSeeder.php
use App\Models\DeparturePackage;
use App\Models\Price;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HomepageDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pkg = DeparturePackage::factory()->create([
            'package_name'  => 'Ramadhan Exclusive',
            'departure_date'=> Carbon::today()->addDays(10),
            'is_public'     => true,
            'is_archived'   => false,
            'seats_total'   => 40,
            'seats_reserved'=> 5,
        ]);

        Price::factory()->create([
            'departure_package_id' => $pkg->id,
            'quad_price'   => 25000000,
            'triple_price' => 27000000,
            'double_price' => 30000000,
        ]);
    }
}
```

**Smoke Test (Pest):**
```php
// tests/Feature/HomepageTest.php
use Illuminate\Testing\TestResponse;

it('renders homepage with schedule table', function () {
    $this->seed(\HomepageDemoSeeder::class);

    $response = $this->get('/');
    $response->assertOk();

    $response->assertSeeTextInOrder([
        'Umrah Package', 'Seat', 'Quad', 'Triple', 'Double',
    ]);

    $response->assertSeeText('Ramadhan Exclusive');
    $response->assertSeeText('5/40');
    $response->assertSeeText('Rp 25.000.000');
    $response->assertSeeText('Rp 27.000.000');
    $response->assertSeeText('Rp 30.000.000');
});
```

---

## 12) Implementation Checklist

- [ ] Route: `GET /` → `HomeController@index`.
- [ ] Controller: filters, caching, eager‑loading, transformation, grouping.
- [ ] Blade view: semantic table, responsive styles, accessible markup, empty state.
- [ ] Price formatter with “Contact us” fallback.
- [ ] Seat computation and **FULL** condition when `seats_available ≤ 0`.
- [ ] Basic SEO (title & description).
- [ ] Test: seed + feature test for columns and prices.
- [ ] QA: check mobile layout and screen reader semantics.
- [ ] Perf: confirm no N+1, confirm cache effectiveness.

---

## 13) Risks & Mitigations

- **Data sparsity/legacy records:** Guard against missing fields; use defaults and fallbacks.
- **Inconsistent price location:** Controller resolves from relation **or** local columns.
- **Cache staleness:** Short TTL; consider event‑driven cache busting in future.
- **Accessibility regressions:** Keep semantic table elements; validate with Lighthouse/axe.

---

## 14) Future Enhancements (Post‑MVP)

- Componentize into `<x-umrah/schedule-table>` and `<x-umrah/filters>`.
- Add pagination and richer filters (airline, duration, hotel class).
- Add per‑row “Book/Contact” CTAs with tracking.
- Expose JSON API for the schedule for reuse across channels.

---

## 15) File Map (Proposed)

```
app/
  Http/Controllers/HomeController.php
resources/
  views/
    home.blade.php
routes/
  web.php
tests/
  Feature/HomepageTest.php
database/
  seeders/HomepageDemoSeeder.php
```

---

## 16) Definition of Done

- ✅ `GET /` loads in < 200ms (warm cache) on staging.
- ✅ Displays grouped, sorted, filtered schedule with correct seat and price formatting.
- ✅ Passes the smoke test and manual QA for empty states and FULL seats.
- ✅ Meets accessibility & SEO basics; no N+1; code commented for future extension.

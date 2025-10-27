
# PRD — Package Detail Page & Linked Homepage Titles (Laravel Blade)

**Author:** Nawitatour Team  
**Date:** 2025-10-25  
**Status:** Draft → Implementable  
**Target Stack:** Laravel 12, Blade, PHP 8.2/8.3, MySQL, Tailwind (optional), Alpine (minimal)

---

## 1) Objective

Enhance the public site so **each Umrah Package name on the homepage** is a real, accessible link to a dedicated **Package Detail** page. Replace raw-name URLs with a **stable `slug`** on the `DeparturePackage` model, and use **route model binding** for `GET /detailpaket/{slug}`. The detail page must present an **accessible, keyboard-navigable Tab Interface** with two tabs—**Details** and **Description**—that render itinerary content and long-form package description, respectively. Performance, accessibility, and SEO are first-class concerns.

---

## 2) Scope

- Add a **URL-safe, unique `slug`** to `DeparturePackage` and expose it as the route key.
- Update homepage table so **package title is an `<a>`** to the new detail route.
- Implement `DetailPaketController@show` with **implicit route-model binding** (`{package:slug}`).
- Build `resources/views/detailpaket.blade.php` with a semantic, ARIA-correct **Tab Interface** (no heavy JS; Tailwind + a tiny script/Alpine allowed).
- Description tab: render **long description + highlights/notes** from the package.
- Details tab: render **Itinerary** rows bound by **foreign key (`itineraries.departure_package_id`)**, ordered by **day then date**, dates formatted in **Indonesian locale**, and guard against nulls.
- **Eager-load** itinerary to avoid N+1; **404** unknown slugs.
- **Cache** the assembled view-model for **10–15 minutes**.
- **Deep-linking**: hash fragments like `#details` open the correct tab.
- **SEO**: dynamic title, meta description (first 150–160 chars of description), canonical link, and **structured breadcrumbs**.
- **Tests** (Pest/PHPUnit): one for the detail page render + content, one that the **homepage link** resolves to the detail route.

**Out of Scope**
- Admin authoring UI (Filament) beyond existing fields.
- Rich text editor work beyond rendering stored description safely.
- Image gallery/booking actions (future task).

---

## 3) Data Model Changes & Assumptions

### 3.1 `DeparturePackage` (new `slug`)
- Add nullable `slug` string column with **unique index**.
- Backfill existing rows by slugifying `package_name` with collision handling (`-{id}` suffix fallback).
- Enforce slug generation on create/update if `slug` is empty or `package_name` changes.

**Migration** (`database/migrations/xxxx_add_slug_to_departure_packages.php`):
```php
return new class extends Migration {
    public function up(): void {
        Schema::table('departure_packages', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('package_name');
        });
    }
    public function down(): void {
        Schema::table('departure_packages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
```

**Model** (`app/Models/DeparturePackage.php`):
```php
class DeparturePackage extends Model
{
    protected $fillable = [
        'package_name','slug','departure_date','is_public','is_archived',
        'seats_total','seats_reserved','description','highlights'
    ];

    // Serve slug for routes
    public function getRouteKeyName(): string { return 'slug'; }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (blank($model->slug) || $model->isDirty('package_name')) {
                $base = Str::slug($model->package_name ?? '');
                $slug = $base;
                $n = 1;
                while (static::where('slug', $slug)->whereKeyNot($model->getKey())->exists()) {
                    $slug = $base . '-' . ($model->id ?? $n++);
                }
                $model->slug = $slug ?: (string) Str::uuid();
            }
        });
    }

    public function itineraries() {
        return $this->hasMany(Itinerary::class, 'departure_package_id');
    }
}
```

### 3.2 `Itinerary` (normalized relation)
Assumptions:
- Table **`itineraries`** with: `id`, `departure_package_id` (FK), `day` (int), `date` (date, nullable), `title` (string), `description` (text, nullable), timestamps.
- Index on `departure_package_id, day, date`.

> If legacy data matches by name: normalize by assigning `departure_package_id` via the **slug** during a one-time migration script and stop using name-based joins.

---

## 4) Routing

**`routes/web.php`**
```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DetailPaketController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Implicit binding by slug
Route::get('/detailpaket/{package:slug}', [DetailPaketController::class, 'show'])
    ->name('detailpaket.show');
```

---

## 5) Controllers

### 5.1 DetailPaketController
**File:** `app/Http/Controllers/DetailPaketController.php`

```php
namespace App\Http\Controllers;

use App\Models\DeparturePackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DetailPaketController extends Controller
{
    public function show(Request $request, DeparturePackage $package): View
    {
        abort_if(!$package->is_public || $package->is_archived, 404);

        $cacheKey = "detailpaket.v1:{$package->slug}";
        $vm = Cache::remember($cacheKey, now()->addMinutes(12), function () use ($package) {
            $package->load(['itineraries' => function ($q) {
                $q->orderBy('day')->orderBy('date');
            }]);

            $desc = (string) ($package->description ?? '');
            $metaDesc = trim(mb_substr(strip_tags($desc), 0, 160));

            return [
                'slug' => $package->slug,
                'package_name' => $package->package_name,
                'description_html' => $package->description, // render safely in Blade
                'highlights' => $package->highlights,
                'itinerary' => $package->itineraries->map(function ($row) {
                    return [
                        'day' => (int) $row->day,
                        'date' => optional($row->date)->format('Y-m-d'),
                        'title' => $row->title,
                        'description' => $row->description,
                    ];
                })->values(),
                'meta' => [
                    'title' => 'Umrah Package — ' . $package->package_name . ' | ' . config('app.name'),
                    'description' => $metaDesc,
                    'canonical' => route('detailpaket.show', $package),
                ],
            ];
        });

        return view('detailpaket', [
            'vm' => $vm,
            'return' => $request->query('return'),
        ]);
    }
}
```

### 5.2 Homepage change (link)
In `HomeController@index`’s view data, include `slug` per package so the **homepage Blade** can build the link cleanly:
```blade
{{-- Inside the package header row --}}
<tr role="rowheader">
  <td colspan="5">
    <a href="{{ route('detailpaket.show', ['package' => $rows->first()['slug']]) }}{{ request()->getQueryString() ? ('?return=' . urlencode(url()->full())) : '' }}">
      {{ $packageName }}
    </a>
  </td>
</tr>
```

---

## 6) Views

### 6.1 Homepage Title Link (resources/views/home.blade.php)
- Wrap the package group header with the link above.
- Prefer pulling `$rows->first()['slug']` (provided by controller) to avoid re-slugifying in Blade.

### 6.2 Detail Page (resources/views/detailpaket.blade.php)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $vm['meta']['title'] }}</title>
  <meta name="description" content="{{ $vm['meta']['description'] }}">
  <link rel="canonical" href="{{ $vm['meta']['canonical'] }}">

  {{-- Structured breadcrumbs --}}
  <script type="application/ld+json">
  {!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    '@id' => $vm['meta']['canonical'] . '#breadcrumbs',
    'itemListElement' => [
      ['@type'=>'ListItem','position'=>1,'name'=>config('app.name'),'item'=>url('/')],
      ['@type'=>'ListItem','position'=>2,'name'=>'Umrah Packages','item'=>url('/')],
      ['@type'=>'ListItem','position'=>3,'name'=>$vm['package_name'],'item'=>$vm['meta']['canonical']],
    ]
  ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
  </script>

  @vite(['resources/css/app.css','resources/js/app.js'])
  <style>
    .container { max-width: 1000px; margin: 0 auto; padding: 1rem; }
    .tabs { display: flex; gap: .5rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem; }
    .tab { padding: .5rem .75rem; border: 1px solid transparent; border-radius: .375rem .375rem 0 0; }
    .tab[aria-selected="true"] { border-color: #e5e7eb; border-bottom-color: white; background: #fff; }
    .panel { display: none; }
    .panel[aria-hidden="false"] { display: block; }
  </style>
</head>
<body>
<div class="container">
  <nav aria-label="Breadcrumb" class="mb-2">
    <a href="{{ $return ?? url('/') }}">&larr; Back</a>
  </nav>

  <header class="mb-3">
    <h1 class="text-2xl font-semibold">{{ $vm['package_name'] }}</h1>
  </header>

  {{-- Accessible Tabs --}}
  <div x-data="tabs()" x-init="init()" class="mb-6">
    <div role="tablist" aria-label="Package tabs" class="tabs">
      <button id="tab-details" class="tab" role="tab" :tabindex="active==='details'?0:-1"
              :aria-selected="active==='details'" aria-controls="panel-details"
              @click="switchTo('details')" @keydown.right.prevent="switchTo('description')" @keydown.left.prevent="switchTo('description')">Details</button>
      <button id="tab-description" class="tab" role="tab" :tabindex="active==='description'?0:-1"
              :aria-selected="active==='description'" aria-controls="panel-description"
              @click="switchTo('description')" @keydown.right.prevent="switchTo('details')" @keydown.left.prevent="switchTo('details')">Description</button>
    </div>

    <section id="panel-details" role="tabpanel" class="panel" :aria-hidden="active!=='details'">
      @if(!empty($vm['itinerary']))
        <ol>
          @foreach($vm['itinerary'] as $row)
            <li class="mb-3">
              <div><strong>Day {{ $row['day'] }}</strong>
                @if(!empty($row['date']))
                  — {{ \Carbon\Carbon::parse($row['date'])->locale('id')->translatedFormat('dddd, DD MMMM YYYY') }}
                @endif
              </div>
              <div class="font-medium">{{ $row['title'] }}</div>
              @if(!empty($row['description']))
                <p class="text-sm text-gray-700">{{ $row['description'] }}</p>
              @endif
            </li>
          @endforeach
        </ol>
      @else
        <p>No itinerary available.</p>
      @endif
    </section>

    <section id="panel-description" role="tabpanel" class="panel" :aria-hidden="active!=='description'">
      @if(!empty($vm['description_html']))
        {!! $vm['description_html'] !!}
      @else
        <p>No description available.</p>
      @endif

      @if(!empty($vm['highlights']))
        <hr class="my-4">
        <h2 class="font-semibold">Highlights</h2>
        <p>{{ $vm['highlights'] }}</p>
      @endif
    </section>
  </div>
</div>

<script>
function tabs(){
  return {
    active: 'details',
    init(){
      const hash = window.location.hash.replace('#','');
      if(['details','description'].includes(hash)) this.active = hash;
      this.updateHash();
    },
    switchTo(tab){
      this.active = tab;
      this.updateHash();
      const btn = document.getElementById('tab-'+tab);
      if(btn) btn.focus();
    },
    updateHash(){
      history.replaceState(null, '', '#'+this.active);
    },
  }
}
</script>
<script src="//unpkg.com/alpinejs" defer></script>
</body>
</html>
```

---

## 7) Performance & Caching

- Detail page: cache per-slug view-model for ~12 minutes.
- Eager-load itineraries; ensure ordering by `day`, then `date` for chronology.
- Homepage cache remains from previous PRD; ensure each row carries `slug` for the link.

---

## 8) Testing (Pest)

**Seeder** (`database/seeders/DetailPaketDemoSeeder.php`):
```php
use Illuminate\Database\Seeder;
use App\Models\DeparturePackage;
use App\Models\Itinerary;
use Illuminate\Support\Carbon;

class DetailPaketDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pkg = DeparturePackage::factory()->create([
            'package_name' => 'Ramadhan Exclusive',
            'slug' => 'ramadhan-exclusive',
            'is_public' => true,
            'is_archived' => false,
            'description' => '<p>Paket Ramadhan eksklusif dengan jadwal padat dan layanan premium.</p>',
            'highlights' => 'Dekat Masjidil Haram, pembimbing berpengalaman.',
        ]);

        foreach ([1,2,3] as $d) {
            Itinerary::factory()->create([
                'departure_package_id' => $pkg->id,
                'day' => $d,
                'date' => \Carbon\Carbon::today()->addDays($d),
                'title' => "Hari $d — Kegiatan",
                'description' => "Deskripsi hari $d.",
            ]);
        }
    }
}
```

**Detail Page Test** (`tests/Feature/DetailPaketTest.php`):
```php
it('renders details and description tabs with itinerary', function () {
    $this->seed(\DetailPaketDemoSeeder::class);

    $resp = $this->get('/detailpaket/ramadhan-exclusive');
    $resp->assertOk();
    $resp->assertSeeText('Details');
    $resp->assertSeeText('Description');
    $resp->assertSeeText('Hari 1 — Kegiatan');
});
```

**Homepage Link Test** (`tests/Feature/HomeToDetailLinkTest.php`):
```php
it('homepage package title links to detail route', function () {
    $this->seed(\DetailPaketDemoSeeder::class);

    $url = route('detailpaket.show', ['package' => 'ramadhan-exclusive']);
    $this->assertStringContainsString('/detailpaket/ramadhan-exclusive', $url);
    $this->get($url)->assertOk();
});
```

---

## 9) Implementation Checklist

- [ ] Migration: add `slug` + unique index.
- [ ] Model: `getRouteKeyName()`, slug generation, relation `itineraries()`.
- [ ] Route: `GET /detailpaket/{package:slug}`.
- [ ] Controller: `DetailPaketController@show` with caching, eager-load, 404 guards.
- [ ] Homepage: package title `<a>` to detail route, `?return=` preserved.
- [ ] Detail Blade: accessible tabs, deep-linking, Indonesian date formatting.
- [ ] SEO: title, meta description, canonical, breadcrumbs.
- [ ] Tests: detail render + homepage link resolution.
- [ ] QA: keyboard/focus states verified; mobile layout works.
- [ ] Perf: no N+1; cache effectiveness verified.

---

## 10) Risks & Mitigations

- **Slug collisions** → unique index + suffix strategy.
- **Legacy name matching** → one-time normalization to FK by slug; deprecate name joins.
- **Accessibility** → roles/ARIA + keyboard events; manual QA with keyboard only.
- **Cache staleness** → short TTL; future event-based busting on update/delete.

---

## 11) Future Enhancements

- Add gallery/media sections and CTA buttons.
- Show related packages and a breadcrumb back to filtered results.
- Add structured `ItemList` for itinerary and `FAQPage` for common questions.

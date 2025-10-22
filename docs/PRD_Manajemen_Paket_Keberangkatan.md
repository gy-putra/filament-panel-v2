# PRD — Manajemen Paket Keberangkatan (Laravel 10 + Filament v3)

> Product Requirements Document to implement models, migrations, and Filament v3 resources for **Paket Keberangkatan Management** according to the provided ERD. This module manages departure packages, enrollments, itineraries, flights, hotels, rooms & rooming lists, package staff, and supporting masters (Hotel & Maskapai), and integrates with existing **Jamaah** data.

---

## 1) Objectives & Scope

**Objectives**
- Provide a complete back‑office UI for staff/admins to create and manage **Paket Keberangkatan** and its sub‑entities.
- Enforce strong data integrity with unique constraints, foreign keys, and enumerations.
- Support efficient operations: enrollment tracking, capacity/occupancy, room assignment, itinerary and transport planning.
- Offer search, filters, soft‑deletes (where applicable), and clear status indicators.

**In Scope**
- Migrations, Models (with relationships, casts, events), and Filament v3 Resources (forms, tables, filters, actions) for all ERD entities.
- Business logic for kuota updates and auto‑rooming (as actions/services).
- Basic masters for **Hotel** and **Maskapai**.
- Authorization hooks (policy placeholders) to restrict destructive actions.

**Out of Scope (phase‑1)**
- Payment/billing, passport/visa document uploads, external airline/hotel API integrations.
- Complex scheduling conflicts resolution; we will provide validations and simple constraints.

---

## 2) Entities, Fields & Constraints

### 2.1 PAKET_KEBERANGKATAN (packages)
**Table**: `paket_keberangkatan` — **SoftDeletes enabled**  
**Columns**
- `id BIGINT` PK
- `kode_paket VARCHAR(30)` **UNIQUE**, required
- `nama_paket VARCHAR(150)` required
- `tgl_berangkat DATE` required
- `tgl_pulang DATE` required (>= tgl_berangkat)
- `kota_asal VARCHAR(100)` required
- `destinasi VARCHAR(150)` required
- `harga_dasar DECIMAL(15,2)` required, >= 0
- `kuota_total INT` required, > 0
- `kuota_terisi INT` required, default 0 (maintained by logic; see §3.1)
- `status_paket ENUM('draft','open','closed','selesai')` default `draft`
- `status_visa ENUM('belum','proses','approved')` default `belum`
- `status_tiket ENUM('belum','issued','reissued')` default `belum`
- `status_hotel ENUM('belum','booked','confirmed')` default `belum`
- `catatan TEXT` nullable
- `timestamps`, `deleted_at`

**Indexes**
- `UNIQUE(kode_paket)`
- Index on `tgl_berangkat`, `status_paket`

**Relationships**
- hasMany: `pendaftaran`, `itinerary`, `flightSegments`, `hotelBookings`, `rooms`, `paketStaff`

---

### 2.2 PENDAFTARAN (enrollments)
**Table**: `pendaftaran` — **No soft delete** (audit via timestamps + is_active)  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE on delete**
- `jamaah_id BIGINT` FK → `jamaah(id)` **RESTRICT on delete** (prevent losing enrollment history)
- `status_pendaftar ENUM('belum_daftar','sudah_dp','lunas','dokumen_lengkap','siap_berangkat')` default `belum_daftar`
- `preferensi_kamar ENUM('single','double','triple','quad','no_preference')` default `no_preference`
- `gender ENUM('L','P')` required (copy from Jamaah at creation for grouping)
- `is_active BOOLEAN` default true
- `catatan TEXT` nullable
- `timestamps`
- **Composite UNIQUE** `paket_id, jamaah_id`

**Indexes**
- `INDEX(paket_id, status_pendaftar, is_active)`
- `INDEX(jamaah_id)`

**Relationships**
- belongsTo: `paketKeberangkatan`, `jamaah`
- hasMany: `roomAssignments`

---

### 2.3 ITINERARY
**Table**: `itinerary`  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE on delete**
- `tanggal DATE` required
- `hari_ke INT` required, >= 1
- `kota VARCHAR(100)` required
- `aktivitas VARCHAR(200)` required
- `deskripsi TEXT` nullable
- `jam_mulai TIME` nullable
- `jam_selesai TIME` nullable
- `timestamps`

**Indexes**
- `INDEX(paket_id, tanggal)`

**Constraints**
- Optional uniqueness per paket: (`paket_id`, `hari_ke`, `tanggal`) to avoid duplicates.

---

### 2.4 FLIGHT_SEGMENTS
**Table**: `flight_segments`  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE**
- `maskapai_id BIGINT` FK → `maskapai(id)` **SET NULL** (optional master) nullable
- `maskapai_nama VARCHAR(100)` required (kept denormalized for report)
- `nomor_penerbangan VARCHAR(20)` required
- `rute_dari VARCHAR(50)` required
- `rute_ke VARCHAR(50)` required
- `tanggal_berangkat DATE` required
- `jam_berangkat TIME` required
- `tanggal_tiba DATE` required
- `jam_tiba TIME` required
- `kelas VARCHAR(10)` nullable
- `bagasi_kg INT` nullable
- `status_segment ENUM('draft','scheduled','changed','cancelled')` default `draft`
- `timestamps`

**Indexes**
- `INDEX(paket_id, tanggal_berangkat)`

---

### 2.5 HOTEL_BOOKINGS
**Table**: `hotel_bookings`  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE**
- `hotel_id BIGINT` FK → `hotel(id)` **SET NULL** (optional master) nullable
- `hotel_nama VARCHAR(150)` required
- `kota VARCHAR(100)` required
- `check_in DATE` required
- `check_out DATE` required (>= check_in)
- `tipe_kamar_default ENUM('SGL','DBL','TPL','QUAD')` nullable
- `status_booking ENUM('belum','booked','confirmed','changed','cancelled')` default `belum`
- `catatan TEXT` nullable
- `timestamps`

**Indexes**
- `INDEX(paket_id, check_in)`

---

### 2.6 ROOMS
**Table**: `rooms`  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE**
- `hotel_booking_id BIGINT` FK → `hotel_bookings(id)` **SET NULL** nullable
- `label_kamar VARCHAR(50)` required
- `tipe_kamar ENUM('SGL','DBL','TPL','QUAD')` required
- `kapasitas TINYINT` required (1–4)
- `gender ENUM('L','P')` required (used for grouping occupants)
- `is_locked BOOLEAN` default false
- `timestamps`

**Indexes**
- `UNIQUE(paket_id, label_kamar)`
- `INDEX(hotel_booking_id)`

**Relationships**
- hasMany: `roomAssignments`

---

### 2.7 ROOM_ASSIGNMENTS (occupants)
**Table**: `room_assignments`  
**Columns**
- `id BIGINT` PK
- `room_id BIGINT` FK → `rooms(id)` **CASCADE**
- `pendaftaran_id BIGINT` FK → `pendaftaran(id)` **CASCADE**
- `posisi TINYINT` required (1..kapasitas)
- **Composite UNIQUE** `room_id, pendaftaran_id`
- `timestamps`

**Indexes**
- `INDEX(room_id)`
- `INDEX(pendaftaran_id)`

---

### 2.8 PAKET_STAFF (N—N Paket ↔ Staff)
**Table**: `paket_staff`  
**Columns**
- `id BIGINT` PK
- `paket_id BIGINT` FK → `paket_keberangkatan(id)` **CASCADE**
- `staff_id BIGINT` FK → `staff(id)` **CASCADE**
- `peran ENUM('muthowif','muthowifah','staff_lapangan','dokumen','medis','lainnya')` required
- `tanggal_mulai DATE` nullable
- `tanggal_selesai DATE` nullable
- **Composite UNIQUE** `paket_id, staff_id, peran`
- `timestamps`

---

### 2.9 STAFF (master)
**Table**: `staff`  
**Columns**
- `id BIGINT` PK
- `nama VARCHAR(150)` required
- `jenis_kelamin ENUM('L','P')` required
- `no_hp VARCHAR(20)` required
- `email VARCHAR(100)` nullable
- `tipe_staff ENUM('muthowif','muthowifah','lapangan','dokumen','medis','lainnya')` required
- `timestamps`

**Indexes**
- `UNIQUE(email)` (nullable unique) — implement conditional uniqueness where DB supports or enforce at app layer
- `INDEX(no_hp)`

---

### 2.10 HOTEL (master)
**Table**: `hotel`  
**Columns**
- `id BIGINT` PK
- `nama VARCHAR(150)` required
- `kota VARCHAR(100)` required
- `timestamps`

**Indexes**
- `UNIQUE(nama, kota)`

---

### 2.11 MASKAPAI (master)
**Table**: `maskapai`  
**Columns**
- `id BIGINT` PK
- `nama VARCHAR(150)` required
- `timestamps`

**Indexes**
- `UNIQUE(nama)`

---

## 3) Business Rules & Domain Logic

### 3.1 Kuota Otomatis (paket_keberangkatan.kuota_terisi)
- `kuota_terisi` reflects **active enrollments** in statuses `sudah_dp`, `lunas`, `dokumen_lengkap`, or `siap_berangkat`.
- Update strategies (choose one, default A):
  - **A. Event‑driven**: on create/update of `pendaftaran` (status or is_active changes), recalc and persist `kuota_terisi` using a domain service (`KuotaService`).
  - **B. Periodic sync**: add a nightly job to recompute all packages for safety.
- Block over‑enrollment: validation in `Pendaftaran` create/update must ensure `kuota_terisi < kuota_total` unless user has override permission.

### 3.2 Auto Rooming Logic
- Add a **Filament Table Action** on `Rooms` or `PaketKeberangkatan` called “Auto‑Assign Rooming”.  
- Algorithm:
  1. Group `pendaftaran` by `gender` and `preferensi_kamar` (only `is_active = true` and status >= `sudah_dp`).  
  2. Fill `rooms` of matching `gender` and `tipe_kamar` by capacity, creating/updating `room_assignments` with `posisi` 1..kapasitas.  
  3. Leave remainder unassigned; output summary (assigned, unassigned).
- Respect `is_locked` rooms (skip).

### 3.3 Status Transitions
- `status_paket`: allow `draft → open → closed → selesai`. Backwards requires elevated role.
- Prevent editing critical fields (tanggal) when `status_paket = selesai` (read‑only UI).

### 3.4 Data Integrity
- For `Itinerary`, ensure `hari_ke` sequence is unique within a package (optional constraint).
- For `FlightSegments`, ensure arrival is not before departure (date+time comparison).
- For `HotelBookings`, ensure `check_out >= check_in`.

---

## 4) Models (Eloquent) & Services

**Common**
- Use guarded/fillable carefully. Cast enums to strings. Add relationships with correct inverse names.
- Add query scopes: e.g., `scopeActive()` for enrollments; `scopeUpcoming()` for packages.

**Services**
- `KuotaService`: `recalcForPaket($paketId)`.
- `RoomingService`: `autoAssign($paketId)` with dry‑run & commit modes.

**Observers/Events**
- `Pendaftaran` observer to call `KuotaService` on relevant changes.
- Optional domain events for status transitions (for notifications later).

---

## 5) Filament v3 Resources (Forms, Tables, Filters)

> All resources live under navigation group **“Manajemen Keberangkatan”** unless stated. Use relevant `heroicon` icons, sensible `$navigationSort`, and Global Search registration for key entities.

### 5.1 PaketKeberangkatanResource
**Form**
- Section *Informasi Paket*: `kode_paket` (unique, required), `nama_paket`, `tgl_berangkat`, `tgl_pulang (>=)`, `kota_asal`, `destinasi`, `harga_dasar`, `kuota_total`.
- Section *Status & Administrasi*: selects for `status_paket`, `status_visa`, `status_tiket`, `status_hotel`; `catatan` textarea.
- Read‑only display for `kuota_terisi` (computed badge).

**Table**
- Columns: `kode_paket` (badge), `nama_paket`, `tgl_berangkat` (date), `tgl_pulang`, `kuota_total`, `kuota_terisi` (progress), `status_paket` (badge), `status_visa`, `status_tiket`, `status_hotel`.
- Filters: by `status_paket`, date range (`tgl_berangkat`), destination, “capacity near full” (e.g., 80%+).
- Actions: Edit, View (optional), Delete (soft), **Action: Recalc Kuota**, **Action: Auto‑Assign Rooming**.
- Relation Managers: `Pendaftaran`, `Itinerary`, `FlightSegments`, `HotelBookings`, `Rooms`, `PaketStaff`.

### 5.2 PendaftaranResource
**Form**
- `paket_id` (Select relationship), `jamaah_id` (Select relationship w/ search by name & kode_jamaah), `gender` (default from Jamaah via reactive fill), `status_pendaftar`, `preferensi_kamar`, `is_active`, `catatan`.
- On create: validate capacity; block if full unless override permission.

**Table**
- Columns: `paket.kode_paket`, `jamaah.nama_lengkap`, `gender`, `status_pendaftar` (badge), `preferensi_kamar`, `is_active`, `created_at`.
- Filters: by status, gender, active, package.
- Actions: Edit, Deactivate/Activate, **Move to another package** (custom action with capacity re‑check).

### 5.3 ItineraryResource
**Form**: `paket_id`, `hari_ke`, `tanggal`, `kota`, `aktivitas`, `deskripsi`, `jam_mulai`, `jam_selesai`.  
**Table**: show `hari_ke`, `tanggal`, `kota`, `aktivitas`; filter by date; default sort by `hari_ke`.

### 5.4 FlightSegmentResource
**Form**: `paket_id`, `maskapai_id` (optional), `maskapai_nama`, `nomor_penerbangan`, `rute_dari`, `rute_ke`, `tanggal_berangkat` + `jam_berangkat`, `tanggal_tiba` + `jam_tiba`, `kelas`, `bagasi_kg`, `status_segment`.  
**Table**: show core fields; filters by date, maskapai, status; validations to ensure arrival after departure.

### 5.5 HotelBookingResource
**Form**: `paket_id`, optional `hotel_id`, `hotel_nama`, `kota`, `check_in`, `check_out (>=)`, `tipe_kamar_default`, `status_booking`, `catatan`.  
**Table**: show hotel, city, dates, status; filter by city/date/status.

### 5.6 RoomResource
**Form**: `paket_id`, optional `hotel_booking_id`, `label_kamar` (unique per paket), `tipe_kamar`, `kapasitas` (1..4), `gender`, `is_locked`.  
**Table**: show `label_kamar`, `tipe_kamar`, `kapasitas`, occupancy (computed via relation count), `gender`, `is_locked`.  
**Actions**: Manage Relation `RoomAssignments`, **Auto‑Fill** (per room) using `RoomingService`.

### 5.7 RoomAssignmentResource (or Relation Manager under Rooms & Pendaftaran)
**Form**: `room_id`, `pendaftaran_id`, `posisi (1..kapasitas)` with validation to avoid duplicates.  
**Table**: show room, jamaah, posisi; filters by package/room.

### 5.8 PaketStaffResource
**Form**: `paket_id`, `staff_id`, `peran`, `tanggal_mulai`, `tanggal_selesai`. Enforce UNIQUE (`paket_id`, `staff_id`, `peran`).  
**Table**: show package, staff, role, dates; filters by role.

### 5.9 StaffResource (master)
**Form**: `nama`, `jenis_kelamin`, `no_hp`, `email`, `tipe_staff`.  
**Table**: show `nama`, `jenis_kelamin`, `no_hp`, `email`, `tipe_staff`; filters by role & gender. Basic validations (phone format, email).

### 5.10 HotelResource (master)
**Form**: `nama`, `kota`.  
**Table**: show `nama`, `kota`; unique pair (`nama`, `kota`).

### 5.11 MaskapaiResource (master)
**Form**: `nama`.  
**Table**: show `nama`; enforce unique name.

---

## 6) Validation Rules (Server‑side Highlights)

- `PaketKeberangkatan`: `tgl_pulang >= tgl_berangkat`; `kuota_total > 0`; `harga_dasar >= 0`.
- `Pendaftaran`: composite unique (`paket_id`, `jamaah_id`); prevent over‑capacity; `gender ∈ {L,P}`; when `is_active = false`, exclude from kuota.
- `Itinerary`: `(paket_id, hari_ke, tanggal)` uniqueness (optional); `hari_ke >= 1`.
- `FlightSegments`: arrival date/time after or equal to departure.
- `HotelBookings`: `check_out >= check_in`.
- `Rooms`: `kapasitas ∈ {1,2,3,4}`; unique (`paket_id`, `label_kamar`) and gender required.
- `RoomAssignments`: prevent exceeding capacity and duplicate occupant in same room; keep `pendaftaran.paket_id` consistent with `room.paket_id`.
- `PaketStaff`: composite unique (`paket_id`, `staff_id`, `peran`).

---

## 7) Authorization & Policies

- Restrict delete/force‑delete to high‑privilege roles.  
- Allow rooming & kuota recalculation actions only to authorized roles (e.g., supervisor/manager).  
- Enforce read‑only for core dates/identifiers when `status_paket = selesai`.

---

## 8) Non‑Functional & Performance

- Add selective indexes as listed; paginate tables; avoid N+1 by eager loading in table queries.  
- Use enum constants (PHP 8.1+ `enum` or config arrays) to avoid typos.  
- Provide meaningful empty states and success/failure notifications for actions (auto‑rooming, recalc).

---

## 9) Migration Sketches (abbreviated)

> Illustrative only; final code must include all constraints listed above.

```php
// paket_keberangkatan
Schema::create('paket_keberangkatan', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->string('kode_paket', 30)->unique();
  $t->string('nama_paket', 150);
  $t->date('tgl_berangkat');
  $t->date('tgl_pulang');
  $t->string('kota_asal', 100);
  $t->string('destinasi', 150);
  $t->decimal('harga_dasar', 15, 2);
  $t->integer('kuota_total');
  $t->integer('kuota_terisi')->default(0);
  $t->enum('status_paket', ['draft','open','closed','selesai'])->default('draft');
  $t->enum('status_visa', ['belum','proses','approved'])->default('belum');
  $t->enum('status_tiket', ['belum','issued','reissued'])->default('belum');
  $t->enum('status_hotel', ['belum','booked','confirmed'])->default('belum');
  $t->text('catatan')->nullable();
  $t->timestamps();
  $t->softDeletes();
  $t->index(['tgl_berangkat', 'status_paket']);
});

// pendaftaran
Schema::create('pendaftaran', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('paket_id')->constrained('paket_keberangkatan')->cascadeOnDelete();
  $t->foreignId('jamaah_id')->constrained('jamaah'); // restrict by default
  $t->enum('status_pendaftar', ['belum_daftar','sudah_dp','lunas','dokumen_lengkap','siap_berangkat'])->default('belum_daftar');
  $t->enum('preferensi_kamar', ['single','double','triple','quad','no_preference'])->default('no_preference');
  $t->enum('gender', ['L','P']);
  $t->boolean('is_active')->default(true);
  $t->text('catatan')->nullable();
  $t->timestamps();
  $t->unique(['paket_id','jamaah_id']);
  $t->index(['paket_id','status_pendaftar','is_active']);
});

// rooms
Schema::create('rooms', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('paket_id')->constrained('paket_keberangkatan')->cascadeOnDelete();
  $t->foreignId('hotel_booking_id')->nullable()->constrained('hotel_bookings')->nullOnDelete();
  $t->string('label_kamar', 50);
  $t->enum('tipe_kamar', ['SGL','DBL','TPL','QUAD']);
  $t->tinyInteger('kapasitas');
  $t->enum('gender', ['L','P']);
  $t->boolean('is_locked')->default(false);
  $t->timestamps();
  $t->unique(['paket_id','label_kamar']);
});

// room_assignments
Schema::create('room_assignments', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
  $t->foreignId('pendaftaran_id')->constrained('pendaftaran')->cascadeOnDelete();
  $t->tinyInteger('posisi');
  $t->unique(['room_id','pendaftaran_id']);
  $t->timestamps();
});
```
*(Add remaining tables `itinerary`, `flight_segments`, `hotel_bookings`, `paket_staff`, `staff`, `hotel`, `maskapai` similarly.)*

---

## 10) Implementation Tasks Checklist

1. Create migrations for all tables with constraints and indexes as specified.  
2. Build Eloquent models with relationships, casts, and scopes.  
3. Implement `KuotaService` and `RoomingService`; wire `Pendaftaran` observer to recalc kuota on changes.  
4. Generate Filament resources:
   - PaketKeberangkatan (+ relation managers for sub‑entities)
   - Pendaftaran, Itinerary, FlightSegment, HotelBooking, Room, RoomAssignment, PaketStaff, Staff, Hotel, Maskapai
5. Implement table filters, search, badges, and custom actions (Recalc Kuota, Auto‑Assign Rooming).  
6. Add validation rules mirrored in Form components and server‑side `FormRequest` / model rules.  
7. Seeders/factories for quick testing (sample packages, rooms, flights, hotels, staff).  
8. Policies/authorization gates for destructive actions and special transitions.  
9. Basic tests: migrations run, relations load, kuota updates correctly, rooming assigns within capacity/gender.  
10. Short README for maintainers (status lifecycle, rooming algorithm, kuota calc).

---

## 11) Acceptance Criteria

- Staff can create and manage packages and all related entities via Filament, with clear navigation under **“Manajemen Keberangkatan.”**
- `kuota_terisi` updates accurately when enrollments change, and over‑capacity is prevented (unless override role).  
- Auto‑rooming action assigns eligible pendaftar into rooms by gender & preference, respecting capacity and locked rooms, with a visible summary of results.  
- All unique keys, foreign keys, and enumerations are enforced per PRD; invalid transitions are blocked by validation.  
- Tables are searchable and filterable; pages load efficiently with pagination and eager loading.  
- Migrations and rollbacks execute cleanly across environments.

---

## 12) Future Enhancements

- Payment & invoicing; document checklist & uploads (passport/visa); notifications for status changes.  
- Calendar/Gantt views for itinerary & flights; printable manifests & rooming sheets.  
- Bulk import/export and API integrations with airline/hotel providers.  

---

**End of Document**

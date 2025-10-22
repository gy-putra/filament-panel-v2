# PRD — **Jamaah** (Laravel 10 + Filament v3)

## 1) Summary & Goals
Create a complete **Jamaah** master-data module (model, migration, factory/seed, Filament v3 Resource) for an Umrah & Halal Travel management system. The module must store rich personal data for each pilgrim (“jamaah”), enforce strict data integrity (unique codes and IDs), support soft-deletes, and offer an efficient back-office UI (for staff/admins) with robust validation, search, filtering, and audit trails (created_by/updated_by).

**Goals**
- Reliable single source of truth for jamaah demographic and contact data.  
- Fast staff workflows in Filament (create/edit/list with search and filters).  
- Strong validation + uniqueness (kode_jamaah, no_ktp).  
- Soft-deletes with restore flow.  
- Audit fields automatically recorded.

**Out of scope (for now)**
- Role management and permissions policy tuning beyond basic gating.  
- Attachments (KTP scans, passports).  
- Advanced import/export automation. (Optional extension listed later.)

---

## 2) Entity Definition

**Table:** `jamaah`  
**Primary key:** `id (BIGINT)` — auto-increment.  
**Soft deletes:** `deleted_at TIMESTAMP NULL`

| Column | Type | Constraints / Default | Description |
|---|---|---|---|
| id | BIGINT | PK | Unique row id. |
| kode_jamaah | VARCHAR(20) | **UNIQUE**, not null | Internal code, e.g., `JM2025-001`. |
| nama_lengkap | VARCHAR(150) | not null | Full legal name (KTP/passport). |
| nama_ayah | VARCHAR(150) | not null | Biological father’s name (manifest/visa needs). |
| jenis_kelamin | ENUM('L','P') | not null | L = Laki-laki, P = Perempuan. |
| tgl_lahir | DATE | not null | Date of birth. |
| tempat_lahir | VARCHAR(100) | nullable | Birth city. |
| pendidikan_terakhir | ENUM('SD','SMP','SMA','D3','S1','S2','S3','Lainnya') | not null | Highest education. |
| kewarganegaraan | VARCHAR(64) | not null, **default 'Indonesia'** | Nationality. |
| no_ktp | VARCHAR(32) | **UNIQUE**, nullable | National ID (WNI). |
| no_bpjs | VARCHAR(30) | nullable | BPJS health number. |
| alamat | TEXT | not null | Full address per KTP. |
| kota | VARCHAR(100) | nullable | City. |
| provinsi | VARCHAR(100) | nullable | Province. |
| negara | VARCHAR(100) | not null, **default 'Indonesia'** | Country. |
| no_hp | VARCHAR(32) | not null | Active phone for communication. |
| email | VARCHAR(150) | nullable | Email (optional). |
| status_pernikahan | ENUM('Single','Married','Widowed','Divorced') | not null | Marital status. |
| pekerjaan | VARCHAR(100) | nullable | Occupation/profession. |
| created_by | BIGINT (FK → users.id) | nullable, **ON DELETE SET NULL** | Creator user id. |
| updated_by | BIGINT (FK → users.id) | nullable, **ON DELETE SET NULL** | Last updater user id. |
| created_at | TIMESTAMP | auto | Laravel timestamps. |
| updated_at | TIMESTAMP | auto | Laravel timestamps. |
| deleted_at | TIMESTAMP | nullable | Soft-delete marker. |

**Indexes & Constraints**
- `UNIQUE (kode_jamaah)`
- `UNIQUE (no_ktp)` (nullable unique)
- Indexes for search: `nama_lengkap`, `no_hp`, `email`, `no_ktp`
- FKs: `created_by`, `updated_by` → `users(id)` with `SET NULL`

---

## 3) Business Rules

1. **kode_jamaah auto-generation**  
   - Format: `JM{YYYY}-{NNN}` (e.g., `JM2025-001`).  
   - Sequence resets yearly.  
   - Generated at **creating** (model event/observer/service), only if not manually provided.  
   - Must remain **immutable** after initial create (locked on edit).

2. **Uniqueness**  
   - `kode_jamaah` must be unique always.  
   - `no_ktp` unique if present (nullable unique).  

3. **Validation (high-level)**  
   - `nama_lengkap`, `nama_ayah`, `alamat`, `no_hp` → required, string length limits.  
   - `jenis_kelamin` ∈ {L,P}; `status_pernikahan` ∈ {Single,Married,Widowed,Divorced}.  
   - `pendidikan_terakhir` ∈ allowed set.  
   - `tgl_lahir` → date; cannot be future.  
   - `email` → valid email format if present.  
   - `no_hp` & `no_ktp` → trimmed, no spaces; optional regex checks.  

4. **Audit**  
   - `created_by` set to `auth()->id()` on create; `updated_by` on each update.  
   - On deleting user accounts, these FKs become `NULL` (history kept).

5. **Soft Delete**  
   - Use `SoftDeletes`. Provide Filament UI to **Trash**, **Restore**, and **Force Delete** (restricted).

---

## 4) Migration Requirements

- Use MySQL `ENUM` for `jenis_kelamin`, `pendidikan_terakhir`, `status_pernikahan`.  
- Set defaults for `kewarganegaraan` and `negara` to `'Indonesia'`.  
- Add unique constraints and FK constraints with `->nullOnDelete()`.  
- Add relevant indexes for search performance.

**Rollback** must drop constraints safely, then table.

---

## 5) Eloquent Model: `App\Models\Jamaah`

- Traits: `HasFactory`, `SoftDeletes`.  
- `$fillable`: allow controlled mass assignment (exclude `created_by`, `updated_by`, `kode_jamaah` by default to avoid tampering).  
- `$casts`:  
  - `tgl_lahir` → `date`  
- Relationships:  
  - `createdBy(): BelongsTo(User::class, 'created_by')`  
  - `updatedBy(): BelongsTo(User::class, 'updated_by')`  
- **Boot events**:  
  - `creating`: generate `kode_jamaah` if empty; set `created_by`.  
  - `updating`: set `updated_by`.  
- **Scope**: optional helper scopes for search by name/phone/ktp.  

---

## 6) Code Generation Service (recommended)

Create `App\Services\KodeJamaahService`:
- `next(string $prefix = 'JM'): string`  
  - Finds latest `kode_jamaah` for current year, increments 3-digit sequence (001…999).  
  - Returns formatted code `JM{YYYY}-{NNN}`.  
Use this service inside `Model::creating`.

---

## 7) Filament v3 Resource: `App\Filament\Resources\JamaahResource`

**Navigation**
- Group: **Master Data**  
- Icon: `heroicon-o-user-group`  
- Sort order: place near top of Master Data.

**Pages**
- `ListJamaahs` (index with soft-delete filter & bulk actions)  
- `CreateJamaah` (form)  
- `EditJamaah` (form with `kode_jamaah` read-only)

### 7.1 Form Schema (Create/Edit)

- **Section: Identity**  
  - `TextInput nama_lengkap` (required, max:150, autofocus)  
  - `TextInput nama_ayah` (required, max:150)  
  - `Select jenis_kelamin` (required; options: L=Male, P=Female, native codes stored)  
  - `DatePicker tgl_lahir` (required; `->maxDate(today())`)  
  - `TextInput tempat_lahir` (max:100, nullable)  
  - `Select pendidikan_terakhir` (required; SD, SMP, SMA, D3, S1, S2, S3, Lainnya)

- **Section: Nationality & IDs**  
  - `TextInput kewarganegaraan` (default `Indonesia`, required, max:64)  
  - `TextInput negara` (default `Indonesia`, required, max:100)  
  - `TextInput no_ktp` (nullable, unique, max:32) with hint “WNI only; leave empty if WNA”  
  - `TextInput no_bpjs` (nullable, max:30)

- **Section: Contact & Address**  
  - `Textarea alamat` (required)  
  - `TextInput kota` (nullable, max:100)  
  - `TextInput provinsi` (nullable, max:100)  
  - `TextInput no_hp` (required, max:32, masking/format hint)  
  - `TextInput email` (nullable, email, max:150)

- **Section: Status & Work**  
  - `Select status_pernikahan` (required; Single, Married, Widowed, Divorced)  
  - `TextInput pekerjaan` (nullable, max:100)

- **System**  
  - `TextInput kode_jamaah` → **read-only** on Edit; on Create may show placeholder but value is auto-generated on save.  
  - `KeyValue`/`Placeholder` fields for audit (created_by, updated_by, timestamps) displayed as read-only meta in Edit (optional).

**Validation Notes (server-side)**  
Implement Laravel rules mirroring the above (e.g., `unique:jamaah,no_ktp,NULL,id,deleted_at,NULL` for uniqueness across non-deleted rows). For `kode_jamaah`, enforce uniqueness with same soft-delete condition.

### 7.2 Table (List)

**Columns**
- `kode_jamaah` (badge)  
- `nama_lengkap` (searchable)  
- `jenis_kelamin` (badge: L/P)  
- `tgl_lahir` (date)  
- `no_hp`  
- `no_ktp` (copyable, nullable)  
- `kota` / `provinsi` (compact)  
- `status_pernikahan` (badge)  
- `created_at` (dateTime, toggleable hidden by default)

**Searchable fields**: `nama_lengkap`, `kode_jamaah`, `no_hp`, `email`, `no_ktp`, `kota`, `provinsi`.  
**Filters**
- `jenis_kelamin` (L/P)  
- `status_pernikahan`  
- `pendidikan_terakhir`  
- `deleted` (Trashed: With/Only/Without)  
- Optional text filter by city/province.

**Row Actions**
- View (optional), Edit, Delete (soft), Restore (when trashed), Force Delete (restricted).  
**Bulk Actions**
- Delete, Restore, Force Delete (restricted).  
**Table Default Sort**: `created_at` desc.

**Global Search Result Details**
- Title: `nama_lengkap`  
- Subtitle: `kode_jamaah • no_hp`  
- Extra: `kota, provinsi` if present.

---

## 8) Authorization

- Restrict access to **staff/admin users** only.  
- Deletion & force delete are stricter; only higher-privilege roles (e.g., superadmin) can force-delete.  
- Policies can use your existing gate or Spatie Permissions if available.  
- `created_by`/`updated_by` set using authenticated user in model events.

---

## 9) Import/Export (Optional Extension)

- **Export**: Add a `Tables\Actions\ExportAction` (e.g., using a compatible exporter package). Include columns matching DB schema.  
- **Import**: Add an import Action for CSV/XLSX with header mapping and validation; reject duplicates by `kode_jamaah`/`no_ktp`.  
*(Note: confirm PHP 8.3 compatibility of chosen package in your environment.)*

---

## 10) Factories & Seeders

- **Factory** (`JamaahFactory`) to generate realistic data (Indonesian names, cities, phone numbers, mixture of enum values).  
- **Seeder** to insert a small dataset (10–50 records) for testing; ensure unique `no_ktp` where present.

---

## 11) Testing

**Feature Tests**
- Can create jamaah with valid data; `kode_jamaah` auto-generated and unique.  
- Cannot create with duplicate `kode_jamaah` or `no_ktp`.  
- `tgl_lahir` cannot be future.  
- Soft delete + restore behavior.  
- `created_by`/`updated_by` correctly assigned.  
- Filament list: search by name/phone/ktp, filters work.

**Unit Tests**
- `KodeJamaahService::next()` increments properly and resets per year.  
- Model events set audit fields.

---

## 12) Performance & Non-Functional

- Add indexes on `nama_lengkap`, `no_hp`, `email`, `no_ktp` to keep list/search responsive.  
- Use pagination defaults in Filament table.  
- Enforce max lengths to avoid oversized rows.  
- Ensure inputs are trimmed and normalized (e.g., phone number digits).

---

## 13) Migration Sketch (Reference)

```php
Schema::create('jamaah', function (Blueprint $table) {
    $table->bigIncrements('id');

    $table->string('kode_jamaah', 20)->unique();
    $table->string('nama_lengkap', 150);
    $table->string('nama_ayah', 150);
    $table->enum('jenis_kelamin', ['L','P']);
    $table->date('tgl_lahir');
    $table->string('tempat_lahir', 100)->nullable();
    $table->enum('pendidikan_terakhir', ['SD','SMP','SMA','D3','S1','S2','S3','Lainnya']);

    $table->string('kewarganegaraan', 64)->default('Indonesia');
    $table->string('no_ktp', 32)->unique()->nullable();
    $table->string('no_bpjs', 30)->nullable();

    $table->text('alamat');
    $table->string('kota', 100)->nullable();
    $table->string('provinsi', 100)->nullable();
    $table->string('negara', 100)->default('Indonesia');

    $table->string('no_hp', 32);
    $table->string('email', 150)->nullable();

    $table->enum('status_pernikahan', ['Single','Married','Widowed','Divorced']);
    $table->string('pekerjaan', 100)->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['nama_lengkap']);
    $table->index(['no_hp']);
    $table->index(['email']);
});
```

---

## 14) Implementation Tasks Checklist

1. **Migration** for `jamaah` with all columns, constraints, indexes.  
2. **Model** `Jamaah` with `SoftDeletes`, relationships, casts, guarded/fillable.  
3. **Service** `KodeJamaahService` and integrate in `Jamaah::creating`.  
4. **Audit wiring** in model events to populate `created_by`/`updated_by`.  
5. **Filament Resource**  
   - Forms with sections and validation rules.  
   - List table with columns, search, filters, trashed toggle, actions.  
   - Optional export/import actions (feature-flagged).  
6. **Factory & Seeder** for test data.  
7. **Policy** and Filament page auth (restrict force-delete).  
8. **Tests** (unit + feature).  
9. **Docs**: brief README for maintainers (code generation rules, enums, constraints).  

---

## 15) Acceptance Criteria

- Staff can **create**, **edit**, **soft-delete**, and **restore** Jamaah records in Filament.  
- `kode_jamaah` is **auto-generated**, unique, and **read-only** after create; yearly sequence works.  
- `no_ktp` uniqueness is enforced if present; null allowed.  
- All validations enforced (enums, email, future DOB blocked, length limits).  
- Search and filters operate quickly on the list page.  
- `created_by`/`updated_by` recorded correctly.  
- Soft-deleted records are excluded by default and manageable via “Trashed” filter.  
- Migrations/rollbacks run cleanly in all environments (local/server).  

---

## 16) Future Enhancements (Nice-to-have)

- Passport/visa fields, emergency contact, mahrom mappings, health notes.  
- Bulk import from standardized CSV/XLSX, with preview & row-level validation.  
- Dedicated “Profile” page with timeline of activity and attached documents.  
- Relationships to trip/batch (kelompok keberangkatan), rooming lists, payments, etc.  

---

**End of Document**

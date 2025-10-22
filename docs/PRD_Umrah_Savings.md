# PRD — Umrah Savings (Tabungan Umroh) Module
**Laravel 10 + Filament v3** • **Admin-only operations** • **Version 1.0**

This Product Requirements Document specifies the database schema, Eloquent models, domain rules, and Filament v3 resources to implement the **Umrah Savings** module. The module follows the ERD provided (accounts, deposits, allocations, optional targets) and enforces a one‑way money flow: **Deposits → Available Balance → Allocation (Locked) → Posted to Registration/Invoice**.

---

## 1) Objectives & Non‑Goals

**Objectives**
- Provide a secure back‑office for staff to open savings accounts, verify deposits, allocate funds to registrations/invoices, and optionally track savings targets.
- Enforce strict balance integrity with **available** vs **locked** balances to prevent double spending.
- Full auditability for sensitive actions (who, when, what).

**Non‑Goals (Phase 1)**
- Customer self‑service and withdrawals (not allowed).
- Payment gateways and receipt image OCR (manual upload only).
- Cross‑module billing logic beyond “posting” allocations to target registration/invoice (hooks only).

---

## 2) Entities & Schema

> Naming uses snake_case table names and singular Eloquent models.

### 2.1 `tabungan` — Savings Account (Master)
- **Columns**
  - `id BIGINT` PK
  - `jamaah_id BIGINT` (FK → `jamaah.id`, **UNIQUE**) — one active account per jamaah
  - `nomor_rekening VARCHAR(30)` **UNIQUE**
  - `nama_ibu_kandung VARCHAR(150)`
  - `nama_bank ENUM('BSI','BJB')`
  - `tanggal_buka_rekening DATE`
  - `saldo_tersedia DECIMAL(15,2)` default 0.00
  - `saldo_terkunci DECIMAL(15,2)` default 0.00
  - `status ENUM('aktif','non_aktif')` default `aktif`
  - `dibuka_pada DATE` (alias/dup of tanggal_buka_rekening if needed by reports)
  - `created_by BIGINT` (FK → `users.id`, **SET NULL on delete**)
  - `updated_by BIGINT` (FK → `users.id`, **SET NULL on delete**)
  - timestamps, softDeletes
- **Indexes/Constraints**
  - UNIQUE: `jamaah_id`, `nomor_rekening`
  - INDEX: `status`, `tanggal_buka_rekening`
- **Rules**
  - Only one **active** account per jamaah (enforce via app rule or partial unique using status).

### 2.2 `tabungan_setoran` — Deposits
- **Columns**
  - `id BIGINT` PK
  - `tabungan_id BIGINT` (FK → `tabungan.id` **CASCADE**)
  - `tanggal DATETIME`
  - `nominal DECIMAL(15,2)` (must be > 0)
  - `metode ENUM('transfer','tunai','gateway')`
  - `bukti_path VARCHAR(255)` (nullable, uploaded receipt path)
  - `status_verifikasi ENUM('pending','approved','rejected')` default `pending`
  - `verified_by BIGINT` (FK → `users.id`, nullable, **SET NULL**)
  - `verified_at DATETIME` (nullable)
  - `catatan TEXT` (nullable)
  - timestamps
- **Indexes/Constraints**
  - INDEX: (`tabungan_id`, `status_verifikasi`, `tanggal`)
- **Rules**
  - On **approved**: increase `tabungan.saldo_tersedia` by `nominal` atomically.

### 2.3 `tabungan_alokasi` — Allocation (Lock/Spend to Registration/Invoice)
- **Columns**
  - `id BIGINT` PK
  - `tabungan_id BIGINT` (FK → `tabungan.id` **CASCADE**)
  - `pendaftaran_id BIGINT` (FK, nullable) — registration target
  - `invoice_id BIGINT` (FK, nullable) — invoice target
  - `tanggal DATETIME`
  - `nominal DECIMAL(15,2)` (> 0)
  - `status ENUM('draft','posted','reversed')` default `draft`
  - `catatan TEXT` (nullable)
  - timestamps
- **Indexes/Constraints**
  - INDEX: (`tabungan_id`, `status`, `tanggal`)
- **Rules & Balance Effects**
  - **Create/Update to `draft`**: move from available → locked (increase `saldo_terkunci`, decrease `saldo_tersedia`).
  - **Transition `draft → posted`**: decrease `saldo_terkunci` (final consumption). Trigger “posted” hook for external module.
  - **Transition `draft → reversed`** or `posted → reversed` (admin only): decrease `saldo_terkunci` if still locked, else if posted then do not auto‑refund (business decides); for this PRD, reversed from `draft` returns to available (locked↓, available↑). Reversing a **posted** allocation is recorded but refunding is out of scope.

### 2.4 `tabungan_target` — Optional Per‑Account Goal
- **Columns**
  - `id BIGINT` PK
  - `tabungan_id BIGINT` (FK → `tabungan.id`, **UNIQUE** one‑to‑one)
  - `target_nominal DECIMAL(15,2)` (> 0)
  - `deadline DATE` (nullable)
  - `paket_target_id BIGINT` (FK, nullable)
  - `rencana_bulanan JSON` (nullable) — simple plan array or {amount, months}
  - timestamps
- **Indexes/Constraints**
  - UNIQUE: `tabungan_id`

---

## 3) Business Rules & Domain Logic

1. **No Withdrawals by Customers**: The only way to reduce available balance is via allocation; direct withdrawals are not implemented.
2. **Approval Gating for Deposits**: Only `approved` deposits affect balances; `pending` and `rejected` do not.
3. **Double‑Spend Protection**: Allocation must lock funds first (`saldo_terkunci`) before posting; prevent allocations that exceed available.
4. **Atomicity**: Balance updates must be done in database transactions with `SELECT ... FOR UPDATE` on the account row.
5. **Auditability**: Always record `verified_by`, `verified_at`, and store `catatan` for approvals/rejections and allocation transitions.
6. **One Active Account per Jamaah**: Enforce in app layer; optionally add a partial unique index if DB supports it.
7. **External Posting Hooks**: When an allocation is posted, fire a domain event for `pendaftaran`/`invoice` listeners.

---

## 4) Eloquent Models & Services

### Models
- `App\Models\Tabungan` (uses `SoftDeletes`)
  - Relations: `jamaah`, `setoran()` hasMany, `alokasi()` hasMany, `target()` hasOne
  - Helpers: `lockForUpdate()` scope; mutators to keep balances non‑negative
- `App\Models\TabunganSetoran`
  - BelongsTo: `tabungan`, VerifiedBy `user`
- `App\Models\TabunganAlokasi`
  - BelongsTo: `tabungan`, `pendaftaran` (nullable), `invoice` (nullable)
- `App\Models\TabunganTarget`
  - BelongsTo: `tabungan`

### Services
- `SavingsLedgerService`
  - `approveDeposit(TabunganSetoran $setoran)` — transactionally increases available, stamps verifier/time.
  - `createOrUpdateDraftAllocation(Tabungan $tabungan, array $data)` — moves available→locked; prevents overdraft.
  - `postAllocation(TabunganAlokasi $alokasi)` — decreases locked; dispatch `AllocationPosted` event.
  - `reverseAllocation(TabunganAlokasi $alokasi)` — if draft: locked→available; if posted: record reversal only (no refund here).
- **Events**: `AllocationPosted` with payload (account, allocation, target model).

---

## 5) Filament v3 Resources (Navigation: “Finance → Tabungan Umroh”)

### 5.1 `TabunganResource`
- **Form (Create/Edit)**
  - Section *Account*: `jamaah_id` (Select relation, search by name & kode), `nomor_rekening` (unique), `nama_bank` (Enum), `tanggal_buka_rekening` (default today), `status`.
  - Section *Owner Details*: `nama_ibu_kandung` (required), read‑only owner (jamaah) preview.
  - Section *Balances*: read‑only `saldo_tersedia`, `saldo_terkunci` badges.
- **Table**
  - Columns: `nomor_rekening` (badge), `jamaah.nama_lengkap`, `nama_bank`, `tanggal_buka_rekening`, `saldo_tersedia` (money), `saldo_terkunci` (money), `status` (badge), `updated_at`.
  - Filters: `status`, `nama_bank`, range filter by `tanggal_buka_rekening` and `saldo_tersedia`.
  - Actions: Edit, Soft Delete/Restore (admin only).
  - **Relation Managers**: `SetoranRelationManager`, `AlokasiRelationManager`, `TargetRelationManager`.

### 5.2 `TabunganSetoranResource` (or Relation Manager under Tabungan)
- **Form**
  - Fields: `tabungan_id`, `tanggal`, `nominal`, `metode`, `bukti_path` (FileUpload), `status_verifikasi`, `catatan`.
  - **Approve/Reject Actions**: custom actions that call `SavingsLedgerService::approveDeposit` and stamp `verified_by/verified_at`.
- **Table**
  - Columns: `tanggal`, `nominal` (money), `metode`, `status_verifikasi` (badge), `verified_by`, `verified_at`.
  - Filters: by status, metode, date range.
  - Bulk Actions: Approve, Reject (role‑gated).

### 5.3 `TabunganAlokasiResource` (or Relation Manager under Tabungan)
- **Form**
  - Fields: `tabungan_id`, `tanggal`, `nominal`, `status`, `pendaftaran_id` (nullable), `invoice_id` (nullable), `catatan`.
  - Reactive validation: prevent exceeding available when `status='draft'`.
  - Actions: **Post Allocation** (draft→posted), **Reverse Allocation** (draft→reversed). Both call `SavingsLedgerService`.
- **Table**
  - Columns: `tanggal`, `nominal` (money), `status` (badge), `pendaftaran`, `invoice`, `created_at`.
  - Filters: `status`, date range, has invoice/registration.

### 5.4 `TabunganTargetResource` (or Relation Manager under Tabungan)
- **Form**
  - Single record per account: `target_nominal`, `deadline`, `paket_target_id` (optional), `rencana_bulanan` (KeyValue/JSON textarea).
- **Table**
  - Columns: `target_nominal`, `deadline`, `paket_target_id`.
  - Filters: deadline (overdue, within 30/60/90 days).

---

## 6) Validation Rules (Server‑Side)

- **Common**: Trim strings; forbid negative amounts.
- **Deposits**: `nominal > 0`; approving requires `status_verifikasi='pending'` and a verifier user.
- **Allocations**: `nominal > 0`; cannot exceed `saldo_tersedia` when entering `draft`; posting only allowed from `draft`.
- **Target**: `target_nominal > 0`.
- **Tabungan**: unique `nomor_rekening`; enforce single **active** account per jamaah at application layer.

---

## 7) Migrations (Sketch)

```php
// tabungan
Schema::create('tabungan', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('jamaah_id')->constrained('jamaah');
  $t->string('nomor_rekening', 30)->unique();
  $t->string('nama_ibu_kandung', 150);
  $t->enum('nama_bank', ['BSI','BJB']);
  $t->date('tanggal_buka_rekening');
  $t->decimal('saldo_tersedia', 15, 2)->default(0);
  $t->decimal('saldo_terkunci', 15, 2)->default(0);
  $t->enum('status', ['aktif','non_aktif'])->default('aktif');
  $t->date('dibuka_pada')->nullable();
  $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
  $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
  $t->timestamps();
  $t->softDeletes();
  $t->unique(['jamaah_id']); // one account per jamaah (active rule enforced in app)
});

// tabungan_setoran
Schema::create('tabungan_setoran', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete();
  $t->dateTime('tanggal');
  $t->decimal('nominal', 15, 2);
  $t->enum('metode', ['transfer','tunai','gateway']);
  $t->string('bukti_path', 255)->nullable();
  $t->enum('status_verifikasi', ['pending','approved','rejected'])->default('pending');
  $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
  $t->dateTime('verified_at')->nullable();
  $t->text('catatan')->nullable();
  $t->timestamps();
  $t->index(['tabungan_id','status_verifikasi','tanggal']);
});

// tabungan_alokasi
Schema::create('tabungan_alokasi', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete();
  $t->foreignId('pendaftaran_id')->nullable()->constrained('pendaftaran')->nullOnDelete();
  $t->foreignId('invoice_id')->nullable()->constrained('invoice')->nullOnDelete();
  $t->dateTime('tanggal');
  $t->decimal('nominal', 15, 2);
  $t->enum('status', ['draft','posted','reversed'])->default('draft');
  $t->text('catatan')->nullable();
  $t->timestamps();
  $t->index(['tabungan_id','status','tanggal']);
});

// tabungan_target
Schema::create('tabungan_target', function (Blueprint $t) {
  $t->bigIncrements('id');
  $t->foreignId('tabungan_id')->constrained('tabungan')->cascadeOnDelete()->unique();
  $t->decimal('target_nominal', 15, 2);
  $t->date('deadline')->nullable();
  $t->foreignId('paket_target_id')->nullable()->constrained('paket_keberangkatan')->nullOnDelete();
  $t->json('rencana_bulanan')->nullable();
  $t->timestamps();
});
```

---

## 8) Model Behaviors & Transactions

- Use `DB::transaction` and `->lockForUpdate()` when adjusting balances.
- Emit `AllocationPosted` event to let external modules (Registration/Invoice) react.
- Fill `created_by`/`updated_by` from `auth()->id()` in model events or observers.

---

## 9) Filament UX Details

- Use currency formatting for money fields (IDR) and right‑aligned numbers.
- Show inline audit chips (verified by/at) on deposit list.
- Provide confirmation dialogs for approve/post/reverse actions with effect summaries.
- Add empty‑state helpers (e.g., “No deposits yet — click **Add Deposit**”).

---

## 10) Security & Authorization

- Restrict approve/post/reverse actions to Finance/Admin roles.
- Force‑delete disabled; only soft‑delete Tabungan (admin). Deposits/allocations should not be deletable after approval/posting (use status transitions).

---

## 11) Testing (Minimum)

- Approving a deposit increases available balance.
- Creating a draft allocation decreases available and increases locked; posting decreases locked.
- Over‑allocation is blocked; balances never negative.
- One account per jamaah enforced at app layer; unique nomor_rekening enforced at DB.
- Filament pages load with proper filters and actions; events dispatched on posting.

---

## 12) Acceptance Criteria

- Staff can open accounts, record deposits, approve/reject them, and allocate funds to registrations or invoices through Filament.
- Balances reflect operations immediately and remain consistent across concurrent actions.
- All sensitive transitions are audited with user & timestamps.
- Migrations and rollbacks run cleanly in all environments.
- The UI is organized under **Finance → Tabungan Umroh** with Relation Managers for deposits, allocations, and target.

---

**End of Document**

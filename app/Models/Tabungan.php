<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Tabungan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tabungan';

    protected $fillable = [
        'jamaah_id',
        'nomor_rekening',
        'nama_ibu_kandung',
        'nama_bank',
        'tanggal_buka_rekening',
        'saldo_tersedia',
        'saldo_terkunci',
        'status',
        'dibuka_pada',
    ];

    protected $casts = [
        'tanggal_buka_rekening' => 'date',
        'dibuka_pada' => 'date',
        'saldo_tersedia' => 'decimal:2',
        'saldo_terkunci' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    // Relationships
    public function jamaah(): BelongsTo
    {
        return $this->belongsTo(Jamaah::class);
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(TabunganSetoran::class);
    }

    public function alokasi(): HasMany
    {
        return $this->hasMany(TabunganAlokasi::class);
    }

    public function target(): HasOne
    {
        return $this->hasOne(TabunganTarget::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeLockForUpdate(Builder $query): Builder
    {
        return $query->lockForUpdate();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    // Mutators to keep balances non-negative
    public function setSaldoTersediaAttribute($value)
    {
        $this->attributes['saldo_tersedia'] = max(0, $value);
    }

    public function setSaldoTerkunciAttribute($value)
    {
        $this->attributes['saldo_terkunci'] = max(0, $value);
    }

    // Helper methods - Calculate balances dynamically from relationships
    public function getSaldoTersediaAttribute()
    {
        // Get total approved deposits
        $totalApprovedDeposits = $this->setoran()
            ->where('status_verifikasi', 'approved')
            ->sum('nominal');

        // Get total draft allocations (funds that are locked/reserved)
        $totalDraftAllocations = $this->alokasi()
            ->where('status', 'draft')
            ->sum('nominal');

        // Get total posted allocations (funds that have been disbursed)
        $totalPostedAllocations = $this->alokasi()
            ->where('status', 'posted')
            ->sum('nominal');

        // Get total reversed allocations (funds that have been returned to available balance)
        $totalReversedAllocations = $this->alokasi()
            ->where('status', 'reversed')
            ->sum('nominal');

        // Available balance = Total approved deposits - Draft allocations - Posted allocations + Reversed allocations
        // Draft allocations reduce available balance (funds are reserved)
        // Posted allocations reduce available balance (funds are disbursed)
        // Reversed allocations increase available balance (funds are returned)
        return max(0, $totalApprovedDeposits - $totalDraftAllocations - $totalPostedAllocations + $totalReversedAllocations);
    }

    public function getSaldoTerkunciAttribute()
    {
        // Locked balance = Draft allocations only (funds allocated but not yet posted/finalized)
        // Posted allocations are no longer locked (they've been disbursed)
        // Reversed allocations are no longer locked (they've been returned to available)
        return $this->alokasi()
            ->where('status', 'draft')
            ->sum('nominal');
    }

    public function getTotalSaldoAttribute()
    {
        return $this->saldo_tersedia + $this->saldo_terkunci;
    }

    // Add helper methods for balance calculations
    public function getTotalApprovedDepositsAttribute()
    {
        return $this->setoran()
            ->where('status_verifikasi', 'approved')
            ->sum('nominal');
    }

    public function getTotalPostedAllocationsAttribute()
    {
        return $this->alokasi()
            ->where('status', 'posted')
            ->sum('nominal');
    }

    public function getTotalDraftAllocationsAttribute()
    {
        return $this->alokasi()
            ->where('status', 'draft')
            ->sum('nominal');
    }
}

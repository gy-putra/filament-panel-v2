<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Pendaftaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pendaftaran';

    protected $fillable = [
        'kode_pendaftaran',
        'paket_keberangkatan_id',
        'jamaah_id',
        'tgl_daftar',
        'status',
        'jumlah_bayar',
        'catatan',
        'reference',
        'sales_agent_id',
    ];

    protected $casts = [
        'tgl_daftar' => 'date',
        'jumlah_bayar' => 'decimal:2',
        'status' => 'string',
    ];

    // Relationships
    public function paketKeberangkatan(): BelongsTo
    {
        return $this->belongsTo(PaketKeberangkatan::class);
    }

    public function jamaah(): BelongsTo
    {
        return $this->belongsTo(Jamaah::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class);
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pendaftaran) {
            // Check for duplicate combination before creating (excluding soft deleted records)
            $exists = static::where('paket_keberangkatan_id', $pendaftaran->paket_keberangkatan_id)
                ->where('jamaah_id', $pendaftaran->jamaah_id)
                ->whereNull('deleted_at') // Only check active records
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'jamaah_id' => 'Jamaah ini sudah terdaftar pada paket keberangkatan yang dipilih.'
                ]);
            }
        });

        static::updating(function ($pendaftaran) {
            // Check for duplicate combination before updating (excluding current record and soft deleted records)
            $exists = static::where('paket_keberangkatan_id', $pendaftaran->paket_keberangkatan_id)
                ->where('jamaah_id', $pendaftaran->jamaah_id)
                ->where('id', '!=', $pendaftaran->id)
                ->whereNull('deleted_at') // Only check active records
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'jamaah_id' => 'Jamaah ini sudah terdaftar pada paket keberangkatan yang dipilih.'
                ]);
            }
        });
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeByPaket($query, $paketId)
    {
        return $query->where('paket_keberangkatan_id', $paketId);
    }

    // Accessors
    public function getSisaBayarAttribute()
    {
        return $this->paketKeberangkatan->harga_paket - $this->jumlah_bayar;
    }

    public function getIsLunasAttribute()
    {
        return $this->jumlah_bayar >= $this->paketKeberangkatan->harga_paket;
    }
}
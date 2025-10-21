<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightSegment extends Model
{
    use HasFactory;

    protected $table = 'flight_segments';

    protected $fillable = [
        'paket_keberangkatan_id',
        'maskapai_id',
        'tipe',
        'nomor_penerbangan',
        'asal',
        'tujuan',
        'waktu_berangkat',
        'waktu_tiba',
    ];

    protected $casts = [
        'waktu_berangkat' => 'datetime',
        'waktu_tiba' => 'datetime',
    ];

    // Relationships
    public function paketKeberangkatan(): BelongsTo
    {
        return $this->belongsTo(PaketKeberangkatan::class);
    }

    public function maskapai(): BelongsTo
    {
        return $this->belongsTo(Maskapai::class);
    }

    // Scopes
    public function scopeByPaket($query, $paketId)
    {
        return $query->where('paket_keberangkatan_id', $paketId);
    }

    public function scopeByTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public function scopeBerangkat($query)
    {
        return $query->where('tipe', 'berangkat');
    }

    public function scopePulang($query)
    {
        return $query->where('tipe', 'pulang');
    }

    public function scopeOrderByWaktu($query)
    {
        return $query->orderBy('waktu_berangkat');
    }
}
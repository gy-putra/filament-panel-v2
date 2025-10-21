<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itinerary extends Model
{
    use HasFactory;

    protected $table = 'itinerary';

    protected $fillable = [
        'paket_keberangkatan_id',
        'hari_ke',
        'tanggal',
        'judul',
        'deskripsi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'hari_ke' => 'integer',
    ];

    // Relationships
    public function paketKeberangkatan(): BelongsTo
    {
        return $this->belongsTo(PaketKeberangkatan::class);
    }

    // Scopes
    public function scopeByPaket($query, $paketId)
    {
        return $query->where('paket_keberangkatan_id', $paketId);
    }

    public function scopeByHari($query, $hari)
    {
        return $query->where('hari_ke', $hari);
    }

    public function scopeOrderByHari($query)
    {
        return $query->orderBy('hari_ke');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAssignment extends Model
{
    use HasFactory;

    protected $table = 'room_assignments';

    protected $fillable = [
        'room_id',
        'pendaftaran_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    // Scopes
    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByPendaftaran($query, $pendaftaranId)
    {
        return $query->where('pendaftaran_id', $pendaftaranId);
    }

    public function scopeByPaket($query, $paketId)
    {
        return $query->whereHas('pendaftaran', function($q) use ($paketId) {
            $q->where('paket_keberangkatan_id', $paketId);
        });
    }
}
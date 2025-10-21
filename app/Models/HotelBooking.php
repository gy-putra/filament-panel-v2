<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelBooking extends Model
{
    use HasFactory;

    protected $table = 'hotel_bookings';

    protected $fillable = [
        'paket_keberangkatan_id',
        'hotel_id',
        'check_in',
        'check_out',
        'jumlah_malam',
        'jumlah_kamar',
        'status_booking',
        'nomor_booking',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'jumlah_malam' => 'integer',
        'jumlah_kamar' => 'integer',
    ];

    // Relationships
    public function paketKeberangkatan(): BelongsTo
    {
        return $this->belongsTo(PaketKeberangkatan::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    // Scopes
    public function scopeByPaket($query, $paketId)
    {
        return $query->where('paket_keberangkatan_id', $paketId);
    }

    public function scopeByHotel($query, $hotelId)
    {
        return $query->where('hotel_id', $hotelId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in', [$startDate, $endDate]);
    }

    // Accessors
    public function getTotalRoomsAttribute()
    {
        return $this->rooms()->count();
    }

    public function getAvailableRoomsAttribute()
    {
        return $this->rooms()->where('is_locked', false)->count();
    }
}
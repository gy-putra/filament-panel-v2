<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'hotel_booking_id',
        'nomor_kamar',
        'tipe_kamar',
        'kapasitas',
        'gender_preference',
        'is_locked',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_locked' => 'boolean',
    ];

    // Relationships
    public function hotelBooking(): BelongsTo
    {
        return $this->belongsTo(HotelBooking::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function paketKeberangkatan(): HasOneThrough
    {
        return $this->hasOneThrough(
            PaketKeberangkatan::class,
            HotelBooking::class,
            'id', // Foreign key on hotel_bookings table
            'id', // Foreign key on paket_keberangkatan table
            'hotel_booking_id', // Local key on rooms table
            'paket_keberangkatan_id' // Local key on hotel_bookings table
        );
    }

    // Scopes
    public function scopeByTipe($query, $tipe)
    {
        return $query->where('tipe_kamar', $tipe);
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender_preference', $gender);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_locked', false)
                    ->whereHas('roomAssignments', function($q) {
                        $q->havingRaw('COUNT(*) < rooms.kapasitas');
                    }, '<', 1)
                    ->orWhereDoesntHave('roomAssignments');
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    // Accessors
    public function getCurrentOccupancyAttribute()
    {
        return $this->roomAssignments()->count();
    }

    public function getAvailableSpaceAttribute()
    {
        return $this->kapasitas - $this->current_occupancy;
    }

    public function getIsFullAttribute()
    {
        return $this->current_occupancy >= $this->kapasitas;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotel';

    protected $fillable = [
        'nama',
        'kota',
    ];

    // Relationships
    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    // Scopes
    public function scopeByKota($query, $kota)
    {
        return $query->where('kota', $kota);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaketKeberangkatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paket_keberangkatan';

    protected $fillable = [
        'kode_paket',
        'nama_paket',
        'tgl_keberangkatan',
        'tgl_kepulangan',
        'kuota_total',
        'kuota_terisi',
        'harga_paket',
        'status',
        'deskripsi',
    ];

    protected $casts = [
        'tgl_keberangkatan' => 'date',
        'tgl_kepulangan' => 'date',
        'kuota_total' => 'integer',
        'kuota_terisi' => 'integer',
        'harga_paket' => 'decimal:2',
        'status' => 'string',
    ];

    // Relationships
    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function itinerary(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    public function flightSegments(): HasMany
    {
        return $this->hasMany(FlightSegment::class);
    }

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'paket_staff');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'open')
                    ->whereColumn('kuota_terisi', '<', 'kuota_total');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tgl_keberangkatan', [$startDate, $endDate]);
    }

    // Accessors
    public function getKuotaTersisaAttribute()
    {
        return $this->kuota_total - $this->kuota_terisi;
    }

    public function getIsFullAttribute()
    {
        return $this->kuota_terisi >= $this->kuota_total;
    }
}
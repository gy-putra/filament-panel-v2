<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PaketKeberangkatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paket_keberangkatan';

    protected $fillable = [
        'kode_paket',
        'nama_paket',
        'program_title',
        'umrah_program_id',
        'tgl_keberangkatan',
        'tgl_kepulangan',
        'kuota_total',
        'kuota_terisi',
        'harga_paket',
        'harga_quad',
        'harga_triple',
        'harga_double',
        'status',
        'deskripsi',
    ];

    protected $casts = [
        'tgl_keberangkatan' => 'date',
        'tgl_kepulangan' => 'date',
        'kuota_total' => 'integer',
        'kuota_terisi' => 'integer',
        'harga_paket' => 'decimal:2',
        'harga_quad' => 'decimal:2',
        'harga_triple' => 'decimal:2',
        'harga_double' => 'decimal:2',
        'status' => 'string',
        'program_title' => 'string',
    ];

    // Relationships
    public function umrahProgram(): BelongsTo
    {
        return $this->belongsTo(UmrahProgram::class);
    }

    public function pendaftarans(): HasMany
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

    /**
     * Override the delete method to handle selective cascade deletion
     * This method will permanently delete the record and only related data
     * from the "Departure Management" navigation group, preserving data
     * from other navigation groups like "Tabungan Management"
     */
    public function cascadeDelete()
    {
        DB::transaction(function () {
            // Delete only Departure Management related records
            
            // 1. Delete Pendaftaran records (Departure Management)
            $this->pendaftarans()->forceDelete();
            
            // 2. Delete Itinerary records (Departure Management)
            $this->itinerary()->forceDelete();
            
            // 3. Delete FlightSegment records (Departure Management)
            $this->flightSegments()->forceDelete();
            
            // 4. Delete HotelBooking records (Departure Management)
            $this->hotelBookings()->forceDelete();
            
            // 5. Delete RoomAssignment records through Pendaftaran (Departure Management)
            // This is handled by the pendaftaran cascade above
            
            // 6. Delete PaketStaff junction records (Departure Management)
            $this->staff()->detach();
            
            // 7. Handle TabunganTarget - Set paket_target_id to NULL instead of deleting
            // (TabunganTarget belongs to Tabungan Management, not Departure Management)
            TabunganTarget::where('paket_target_id', $this->id)
                         ->update(['paket_target_id' => null]);
            
            // 8. Handle Hotel records - Set paket_keberangkatan_id to NULL instead of deleting
            // (Hotel is master data that should be preserved)
            Hotel::where('paket_keberangkatan_id', $this->id)
                 ->update(['paket_keberangkatan_id' => null]);
            
            // Finally, force delete the main record
            $this->forceDelete();
        });
        
        return true;
    }

    /**
     * Delete method that handles both soft delete and cascade delete
     * Use this method when you want to permanently delete with selective cascade
     */
    public function permanentDelete()
    {
        return $this->cascadeDelete();
    }
}
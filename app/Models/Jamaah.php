<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\KodeJamaahService;

class Jamaah extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'jamaah';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama_lengkap',
        'nama_ayah',
        'jenis_kelamin',
        'tgl_lahir',
        'tempat_lahir',
        'pendidikan_terakhir',
        'kewarganegaraan',
        'no_ktp',
        'no_bpjs',
        'alamat',
        'kota',
        'provinsi',
        'negara',
        'no_hp',
        'email',
        'status_pernikahan',
        'pekerjaan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tgl_lahir' => 'date',
    ];

    /**
     * The attributes that are not mass assignable.
     */
    protected $guarded = [
        'kode_jamaah',
        'created_by',
        'updated_by',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jamaah) {
            // Generate kode_jamaah if not provided
            if (empty($jamaah->kode_jamaah)) {
                $jamaah->kode_jamaah = app(KodeJamaahService::class)->next();
            }
            
            // Set created_by
            if (auth()->check()) {
                $jamaah->created_by = auth()->id();
            }
        });

        static::updating(function ($jamaah) {
            // Set updated_by
            if (auth()->check()) {
                $jamaah->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created this jamaah.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this jamaah.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to search by name.
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('nama_lengkap', 'like', '%' . $name . '%');
    }

    /**
     * Scope to search by phone.
     */
    public function scopeSearchByPhone($query, $phone)
    {
        return $query->where('no_hp', 'like', '%' . $phone . '%');
    }

    /**
     * Scope to search by KTP.
     */
    public function scopeSearchByKtp($query, $ktp)
    {
        return $query->where('no_ktp', 'like', '%' . $ktp . '%');
    }

    /**
     * Get the full name with gender indicator.
     */
    public function getFullNameWithGenderAttribute()
    {
        return $this->nama_lengkap . ' (' . ($this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') . ')';
    }

    /**
     * Get the age from birth date.
     */
    public function getAgeAttribute()
    {
        return $this->tgl_lahir ? $this->tgl_lahir->age : null;
    }
}
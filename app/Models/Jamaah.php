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
        'no_paspor',
        'kota_paspor',
        'tgl_terbit_paspor',
        'tgl_expired_paspor',
        'foto_jamaah',
        'alamat',
        'kabupaten',
        'kecamatan',
        'kelurahan',
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
        'tgl_terbit_paspor' => 'date',
        'tgl_expired_paspor' => 'date',
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
     * Mutator untuk membersihkan nilai jenis_kelamin
     */
    public function setJenisKelaminAttribute($value)
    {
        // Trim whitespace dan bersihkan nilai
        $cleanValue = trim($value);
        
        // Ganti "Laki - laki" (dengan spasi) menjadi "Laki-laki"
        $cleanValue = preg_replace('/Laki\s*-\s*laki/i', 'Laki-laki', $cleanValue);
        
        // Mapping nilai umum ke enum database yang benar ('L' dan 'P')
        $genderMap = [
            'Laki-laki' => 'L',
            'Laki' => 'L',
            'L' => 'L',
            'Male' => 'L',
            'Perempuan' => 'P',
            'Wanita' => 'P',
            'P' => 'P',
            'Female' => 'P'
        ];
        
        $this->attributes['jenis_kelamin'] = $genderMap[$cleanValue] ?? 'L';
    }

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
        return $this->nama_lengkap . ' (' . ($this->jenis_kelamin === 'Laki-laki' ? 'Laki-laki' : 'Perempuan') . ')';
    }

    /**
     * Get the age from birth date.
     */
    public function getAgeAttribute()
    {
        return $this->tgl_lahir ? $this->tgl_lahir->age : null;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'no_hp',
        'email',
        'tipe_staff',
    ];

    protected $casts = [
        'jenis_kelamin' => 'string',
        'tipe_staff' => 'string',
    ];

    // Relationships
    public function paketKeberangkatan(): BelongsToMany
    {
        return $this->belongsToMany(PaketKeberangkatan::class, 'paket_staff');
    }

    // Scopes
    public function scopeByTipeStaff($query, $tipe)
    {
        return $query->where('tipe_staff', $tipe);
    }

    public function scopeByJenisKelamin($query, $jenis)
    {
        return $query->where('jenis_kelamin', $jenis);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmrahProgram extends Model
{
    use HasFactory;

    protected $table = 'umrah_programs';

    protected $fillable = [
        'program_code',
        'program_name',
    ];

    protected $casts = [
        'program_code' => 'string',
        'program_name' => 'string',
    ];

    /**
     * Get all departure packages that belong to this Umrah program.
     */
    public function paketKeberangkatans(): HasMany
    {
        return $this->hasMany(PaketKeberangkatan::class, 'umrah_program_id');
    }

    /**
     * Scope to search by program code or name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('program_code', 'like', "%{$search}%")
                    ->orWhere('program_name', 'like', "%{$search}%");
    }
}
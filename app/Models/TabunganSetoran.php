<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabunganSetoran extends Model
{
    use HasFactory;

    protected $table = 'tabungan_setoran';

    protected $fillable = [
        'tabungan_id',
        'tanggal',
        'nominal',
        'metode',
        'bukti_path',
        'status_verifikasi',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'verified_at' => 'datetime',
        'nominal' => 'decimal:2',
    ];

    // Relationships
    public function tabungan(): BelongsTo
    {
        return $this->belongsTo(Tabungan::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status_verifikasi', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status_verifikasi', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status_verifikasi', 'rejected');
    }
}

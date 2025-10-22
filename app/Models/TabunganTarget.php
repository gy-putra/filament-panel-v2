<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabunganTarget extends Model
{
    use HasFactory;

    protected $table = 'tabungan_target';

    protected $fillable = [
        'tabungan_id',
        'target_nominal',
        'deadline',
        'paket_target_id',
        'rencana_bulanan',
    ];

    protected $casts = [
        'deadline' => 'date',
        'target_nominal' => 'decimal:2',
        'rencana_bulanan' => 'decimal:2',
    ];

    // Relationships
    public function tabungan(): BelongsTo
    {
        return $this->belongsTo(Tabungan::class);
    }

    public function paketTarget(): BelongsTo
    {
        return $this->belongsTo(PaketKeberangkatan::class, 'paket_target_id');
    }

    // Helper methods
    public function getProgressPercentageAttribute()
    {
        if ($this->target_nominal <= 0) {
            return 0;
        }

        $currentSaldo = $this->tabungan->total_saldo ?? 0;
        return min(100, ($currentSaldo / $this->target_nominal) * 100);
    }

    public function getRemainingAmountAttribute()
    {
        $currentSaldo = $this->tabungan->total_saldo ?? 0;
        return max(0, $this->target_nominal - $currentSaldo);
    }
}

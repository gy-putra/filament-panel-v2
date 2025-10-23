<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'nomor_invoice',
        'tanggal_invoice',
        'total_amount',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function tabunganAlokasi(): HasMany
    {
        return $this->hasMany(TabunganAlokasi::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helper methods for automatic creation from allocations
    public static function createFromAllocation(TabunganAlokasi $alokasi): self
    {
        // Generate unique invoice number
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(
            (self::whereDate('created_at', today())->count() + 1), 
            4, 
            '0', 
            STR_PAD_LEFT
        );

        return self::create([
            'nomor_invoice' => $invoiceNumber,
            'tanggal_invoice' => now(),
            'total_amount' => $alokasi->nominal,
            'status' => 'active',
            'catatan' => $alokasi->tabungan?->catatan ?? '-',
        ]);
    }

    public function addAllocationAmount(float $amount): void
    {
        $this->update([
            'total_amount' => $this->total_amount + $amount
        ]);
    }

    // Calculate total allocated amount from related allocations
    public function getTotalAllocatedAttribute(): float
    {
        return $this->tabunganAlokasi()
            ->whereIn('status', ['posted'])
            ->sum('nominal');
    }
}
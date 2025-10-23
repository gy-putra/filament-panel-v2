<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgent extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sales_agents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'agent_code',
        'name',
        'birth_date',
        'place_of_birth',
        'address',
        'phone_number',
        'type',
        'status',
        'bank_name',
        'account_number',
        'account_name',
        'agency_name',
        'join_on',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'birth_date' => 'date',
        'join_on' => 'date',
        'type' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the user who created this sales agent.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by agent type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}

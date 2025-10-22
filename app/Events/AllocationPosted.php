<?php

namespace App\Events;

use App\Models\TabunganAlokasi;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AllocationPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TabunganAlokasi $allocation;

    /**
     * Create a new event instance.
     */
    public function __construct(TabunganAlokasi $allocation)
    {
        $this->allocation = $allocation;
    }
}
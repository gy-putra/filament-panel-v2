<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Pendaftaran;
use App\Models\Jamaah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RoomingService
{
    /**
     * Auto-assign room for a confirmed registration
     */
    public function autoAssignRoom(int $pendaftaranId): ?RoomAssignment
    {
        try {
            return DB::transaction(function () use ($pendaftaranId) {
                $pendaftaran = Pendaftaran::with(['jamaah', 'paketKeberangkatan.hotelBookings.rooms'])
                    ->find($pendaftaranId);

                if (!$pendaftaran || $pendaftaran->status !== 'confirmed') {
                    Log::warning("Pendaftaran {$pendaftaranId} not found or not confirmed");
                    return null;
                }

                // Check if already assigned
                $existingAssignment = RoomAssignment::where('pendaftaran_id', $pendaftaranId)->first();
                if ($existingAssignment) {
                    Log::info("Pendaftaran {$pendaftaranId} already has room assignment");
                    return $existingAssignment;
                }

                $jamaah = $pendaftaran->jamaah;
                $availableRooms = $this->getAvailableRooms($pendaftaran->paket_keberangkatan_id);

                // Find suitable room based on gender preference and capacity
                $suitableRoom = $this->findSuitableRoom($availableRooms, $jamaah->jenis_kelamin);

                if (!$suitableRoom) {
                    Log::warning("No suitable room found for pendaftaran {$pendaftaranId}");
                    return null;
                }

                // Create room assignment
                $assignment = RoomAssignment::create([
                    'room_id' => $suitableRoom->id,
                    'pendaftaran_id' => $pendaftaranId,
                    'assigned_at' => now(),
                ]);

                Log::info("Assigned pendaftaran {$pendaftaranId} to room {$suitableRoom->id}");
                return $assignment;
            });
        } catch (\Exception $e) {
            Log::error("Failed to auto-assign room for pendaftaran {$pendaftaranId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available rooms for a paket keberangkatan
     */
    public function getAvailableRooms(int $paketKeberangkatanId): Collection
    {
        return Room::whereHas('hotelBooking', function ($query) use ($paketKeberangkatanId) {
                $query->where('paket_keberangkatan_id', $paketKeberangkatanId);
            })
            ->where('is_locked', false)
            ->withCount('roomAssignments')
            ->get()
            ->filter(function ($room) {
                return $room->room_assignments_count < $room->kapasitas;
            });
    }

    /**
     * Find suitable room based on gender preference and capacity
     */
    protected function findSuitableRoom(Collection $availableRooms, string $jamaahGender): ?Room
    {
        // First, try to find rooms with matching gender preference
        $genderMatchedRooms = $availableRooms->filter(function ($room) use ($jamaahGender) {
            return $room->gender_preference === $jamaahGender || $room->gender_preference === 'mixed';
        });

        // Sort by current occupancy (fill rooms efficiently)
        $sortedRooms = $genderMatchedRooms->sortByDesc('room_assignments_count');

        foreach ($sortedRooms as $room) {
            // Check if room has compatible occupants (same gender or mixed)
            if ($this->isRoomCompatible($room, $jamaahGender)) {
                return $room;
            }
        }

        // If no gender-matched rooms, try mixed rooms
        $mixedRooms = $availableRooms->filter(function ($room) {
            return $room->gender_preference === 'mixed';
        })->sortByDesc('room_assignments_count');

        foreach ($mixedRooms as $room) {
            if ($this->isRoomCompatible($room, $jamaahGender)) {
                return $room;
            }
        }

        return null;
    }

    /**
     * Check if room is compatible with jamaah gender
     */
    protected function isRoomCompatible(Room $room, string $jamaahGender): bool
    {
        // If room is empty, it's compatible
        if ($room->room_assignments_count === 0) {
            return true;
        }

        // If room preference is mixed, check current occupants
        if ($room->gender_preference === 'mixed') {
            return true; // Mixed rooms accept any gender
        }

        // Check if all current occupants have the same gender
        $currentOccupants = RoomAssignment::where('room_id', $room->id)
            ->with('pendaftaran.jamaah')
            ->get();

        foreach ($currentOccupants as $assignment) {
            $occupantGender = $assignment->pendaftaran->jamaah->jenis_kelamin;
            if ($occupantGender !== $jamaahGender) {
                return false; // Gender mismatch
            }
        }

        return true;
    }

    /**
     * Manually assign room with validation
     */
    public function assignRoom(int $pendaftaranId, int $roomId): RoomAssignment
    {
        return DB::transaction(function () use ($pendaftaranId, $roomId) {
            $pendaftaran = Pendaftaran::with('jamaah')->find($pendaftaranId);
            $room = Room::withCount('roomAssignments')->find($roomId);

            if (!$pendaftaran || !$room) {
                throw new \InvalidArgumentException('Pendaftaran or Room not found');
            }

            if ($pendaftaran->status !== 'confirmed') {
                throw new \InvalidArgumentException('Pendaftaran must be confirmed');
            }

            if ($room->is_locked) {
                throw new \InvalidArgumentException('Room is locked');
            }

            if ($room->room_assignments_count >= $room->kapasitas) {
                throw new \InvalidArgumentException('Room is full');
            }

            if (!$this->isRoomCompatible($room, $pendaftaran->jamaah->jenis_kelamin)) {
                throw new \InvalidArgumentException('Room is not compatible with jamaah gender');
            }

            // Remove existing assignment if any
            RoomAssignment::where('pendaftaran_id', $pendaftaranId)->delete();

            // Create new assignment
            return RoomAssignment::create([
                'room_id' => $roomId,
                'pendaftaran_id' => $pendaftaranId,
            ]);
        });
    }

    /**
     * Remove room assignment
     */
    public function removeRoomAssignment(int $pendaftaranId): bool
    {
        $assignment = RoomAssignment::where('pendaftaran_id', $pendaftaranId)->first();
        
        if ($assignment) {
            $assignment->delete();
            Log::info("Removed room assignment for pendaftaran {$pendaftaranId}");
            return true;
        }

        return false;
    }

    /**
     * Get room occupancy summary for a paket
     */
    public function getRoomOccupancySummary(int $paketKeberangkatanId): array
    {
        $rooms = Room::whereHas('hotelBooking', function ($query) use ($paketKeberangkatanId) {
                $query->where('paket_keberangkatan_id', $paketKeberangkatanId);
            })
            ->withCount('roomAssignments')
            ->with(['hotelBooking.hotel', 'roomAssignments.pendaftaran.jamaah'])
            ->get();

        return [
            'total_rooms' => $rooms->count(),
            'occupied_rooms' => $rooms->where('room_assignments_count', '>', 0)->count(),
            'full_rooms' => $rooms->filter(function ($room) {
                return $room->room_assignments_count >= $room->kapasitas;
            })->count(),
            'total_capacity' => $rooms->sum('kapasitas'),
            'total_occupied' => $rooms->sum('room_assignments_count'),
            'rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'hotel' => $room->hotelBooking->hotel->nama,
                    'nomor_kamar' => $room->nomor_kamar,
                    'tipe_kamar' => $room->tipe_kamar,
                    'kapasitas' => $room->kapasitas,
                    'occupied' => $room->room_assignments_count,
                    'available' => $room->kapasitas - $room->room_assignments_count,
                    'gender_preference' => $room->gender_preference,
                    'is_locked' => $room->is_locked,
                    'occupants' => $room->roomAssignments->map(function ($assignment) {
                        return [
                            'nama' => $assignment->pendaftaran->jamaah->nama_lengkap,
                            'gender' => $assignment->pendaftaran->jamaah->jenis_kelamin,
                        ];
                    }),
                ];
            }),
        ];
    }

    /**
     * Auto-assign all unassigned confirmed registrations for a paket
     */
    public function autoAssignAllUnassigned(int $paketKeberangkatanId): array
    {
        $unassignedPendaftaran = Pendaftaran::where('paket_keberangkatan_id', $paketKeberangkatanId)
            ->where('status', 'confirmed')
            ->whereDoesntHave('roomAssignments')
            ->get();

        $results = [
            'total' => $unassignedPendaftaran->count(),
            'assigned' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($unassignedPendaftaran as $pendaftaran) {
            try {
                $assignment = $this->autoAssignRoom($pendaftaran->id);
                if ($assignment) {
                    $results['assigned']++;
                    $results['details'][] = [
                        'pendaftaran_id' => $pendaftaran->id,
                        'jamaah_nama' => $pendaftaran->jamaah->nama_lengkap,
                        'status' => 'assigned',
                        'room_id' => $assignment->room_id,
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'pendaftaran_id' => $pendaftaran->id,
                        'jamaah_nama' => $pendaftaran->jamaah->nama_lengkap,
                        'status' => 'failed',
                        'reason' => 'No suitable room found',
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'pendaftaran_id' => $pendaftaran->id,
                    'jamaah_nama' => $pendaftaran->jamaah->nama_lengkap,
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
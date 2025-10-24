<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\HotelBooking;
use App\Models\PaketKeberangkatan;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class RoomStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.room-status-widget';
    
    protected static ?string $pollingInterval = '60s';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getRoomStatusData(): array
    {
        // Get active packages (upcoming departures)
        $activePackages = PaketKeberangkatan::where('status', 'open')
            ->where('tgl_keberangkatan', '>', now())
            ->with(['hotelBookings.rooms.roomAssignments'])
            ->get();
        
        $totalRooms = 0;
        $occupiedRooms = 0;
        $totalCapacity = 0;
        $totalOccupied = 0;
        $unallocatedRooms = 0;
        $roomsByType = [];
        $roomsByGender = [];
        
        foreach ($activePackages as $package) {
            foreach ($package->hotelBookings as $booking) {
                foreach ($booking->rooms as $room) {
                    $totalRooms++;
                    $totalCapacity += $room->kapasitas;
                    
                    $assignedCount = $room->roomAssignments->count();
                    $totalOccupied += $assignedCount;
                    
                    if ($assignedCount > 0) {
                        $occupiedRooms++;
                    } else {
                        $unallocatedRooms++;
                    }
                    
                    // Group by room type
                    $roomType = $room->tipe_kamar ?? 'Unknown';
                    if (!isset($roomsByType[$roomType])) {
                        $roomsByType[$roomType] = [
                            'total' => 0,
                            'occupied' => 0,
                            'capacity' => 0,
                            'assigned' => 0
                        ];
                    }
                    $roomsByType[$roomType]['total']++;
                    $roomsByType[$roomType]['capacity'] += $room->kapasitas;
                    $roomsByType[$roomType]['assigned'] += $assignedCount;
                    if ($assignedCount > 0) {
                        $roomsByType[$roomType]['occupied']++;
                    }
                    
                    // Group by gender preference
                    $gender = $room->gender_preference ?? 'Mixed';
                    if (!isset($roomsByGender[$gender])) {
                        $roomsByGender[$gender] = [
                            'total' => 0,
                            'occupied' => 0,
                            'capacity' => 0,
                            'assigned' => 0
                        ];
                    }
                    $roomsByGender[$gender]['total']++;
                    $roomsByGender[$gender]['capacity'] += $room->kapasitas;
                    $roomsByGender[$gender]['assigned'] += $assignedCount;
                    if ($assignedCount > 0) {
                        $roomsByGender[$gender]['occupied']++;
                    }
                }
            }
        }
        
        $occupancyPercentage = $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100, 1) : 0;
        $roomUtilizationPercentage = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        
        return [
            'summary' => [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'unallocated_rooms' => $unallocatedRooms,
                'total_capacity' => $totalCapacity,
                'total_occupied' => $totalOccupied,
                'available_beds' => $totalCapacity - $totalOccupied,
                'occupancy_percentage' => $occupancyPercentage,
                'room_utilization_percentage' => $roomUtilizationPercentage,
            ],
            'by_type' => $roomsByType,
            'by_gender' => $roomsByGender,
        ];
    }
    
    public function getRecentRoomAssignments(): Collection
    {
        return RoomAssignment::with(['room.hotelBooking.paketKeberangkatan', 'pendaftaran.jamaah'])
            ->where('assigned_at', '>=', now()->subDays(7))
            ->orderBy('assigned_at', 'desc')
            ->limit(5)
            ->get();
    }
}
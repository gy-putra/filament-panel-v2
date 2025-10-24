<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Room Status & Accommodation Utilization
        </x-slot>
        
        <x-slot name="description">
            Current room allocation and occupancy overview for active packages
        </x-slot>

        @php
            $data = $this->getRoomStatusData();
            $summary = $data['summary'];
            $byType = $data['by_type'];
            $byGender = $data['by_gender'];
        @endphp

        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Rooms</p>
                            <p class="text-2xl font-semibold text-blue-900 dark:text-blue-100">{{ $summary['total_rooms'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Occupied Rooms</p>
                            <p class="text-2xl font-semibold text-green-900 dark:text-green-100">{{ $summary['occupied_rooms'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Unallocated</p>
                            <p class="text-2xl font-semibold text-yellow-900 dark:text-yellow-100">{{ $summary['unallocated_rooms'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Occupancy Rate</p>
                            <p class="text-2xl font-semibold text-purple-900 dark:text-purple-100">{{ $summary['occupancy_percentage'] }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Occupancy Progress Bar -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Bed Occupancy</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">
                            {{ $summary['total_occupied'] }} / {{ $summary['total_capacity'] }} beds occupied
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $summary['available_beds'] }} available
                        </span>
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                        <div class="h-3 rounded-full transition-all duration-300 
                            @if($summary['occupancy_percentage'] >= 90) bg-red-600
                            @elseif($summary['occupancy_percentage'] >= 70) bg-yellow-500
                            @else bg-green-600
                            @endif"
                            style="width: {{ $summary['occupancy_percentage'] }}%">
                        </div>
                    </div>
                    
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>0%</span>
                        <span class="font-medium">{{ $summary['occupancy_percentage'] }}% occupied</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>

            <!-- Breakdown by Room Type and Gender -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- By Room Type -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-4">By Room Type</h4>
                    <div class="space-y-3">
                        @forelse($byType as $type => $stats)
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $type }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $stats['occupied'] }}/{{ $stats['total'] }} rooms
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-2 rounded-full" 
                                             style="width: {{ $stats['total'] > 0 ? ($stats['occupied'] / $stats['total']) * 100 : 0 }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No room data available</p>
                        @endforelse
                    </div>
                </div>

                <!-- By Gender Preference -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-4">By Gender Preference</h4>
                    <div class="space-y-3">
                        @forelse($byGender as $gender => $stats)
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $gender }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $stats['assigned'] }}/{{ $stats['capacity'] }} beds
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                        <div class="bg-purple-600 h-2 rounded-full" 
                                             style="width: {{ $stats['capacity'] > 0 ? ($stats['assigned'] / $stats['capacity']) * 100 : 0 }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No gender data available</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Room Assignments -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">Recent Room Assignments</h4>
                <div class="space-y-2">
                    @forelse($this->getRecentRoomAssignments() as $assignment)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $assignment->pendaftaran->jamaah->nama_lengkap ?? 'Unknown Guest' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Room {{ $assignment->room->nomor_kamar ?? 'N/A' }} • 
                                    {{ $assignment->room->hotelBooking->paketKeberangkatan->nama_paket ?? 'Unknown Package' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $assignment->assigned_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No recent assignments</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
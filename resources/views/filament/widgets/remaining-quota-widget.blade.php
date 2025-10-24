<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Sisa Kuota per Paket Keberangkatan
        </x-slot>
        
        <x-slot name="description">
            Sisa kuota tersedia versus jumlah peserta yang terdaftar untuk setiap paket keberangkatan
        </x-slot>

        <div class="space-y-4">
            @forelse($this->getPackageQuotas() as $package)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                {{ $package['nama_paket'] }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $package['kode_paket'] }} • Departure: {{ $package['tgl_keberangkatan']->format('d M Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($package['status_color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($package['status_color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @endif">
                                {{ $package['status_text'] }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ $package['kuota_terisi'] }} / {{ $package['kuota_total'] }} seats filled
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $package['kuota_tersisa'] }} remaining
                            </span>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="h-2.5 rounded-full transition-all duration-300
                                @if($package['status_color'] === 'danger') bg-red-600
                                @elseif($package['status_color'] === 'warning') bg-yellow-500
                                @else bg-green-600
                                @endif"
                                style="width: {{ $package['percentage_filled'] }}%">
                            </div>
                        </div>
                        
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>0%</span>
                            <span class="font-medium">{{ $package['percentage_filled'] }}% filled</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <div class="text-gray-400 dark:text-gray-500 mb-2">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3" />
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">No open packages available</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
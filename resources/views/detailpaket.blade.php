<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $paket->nama_paket }} - Detail Paket Umrah</title>
    <meta name="description" content="{{ Str::limit($paket->deskripsi ?? 'Detail lengkap paket umrah ' . $paket->nama_paket, 160) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Beranda
                    </a>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    {{ \Carbon\Carbon::parse($paket->tgl_keberangkatan)->isoFormat('DD MMMM YYYY') }}
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Package Header -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ $paket->kode_paket }}
                        </span>
                        @if($paket->program_title)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                {{ $paket->program_title }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $paket->nama_paket }}</h1>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                            <span>{{ \Carbon\Carbon::parse($paket->tgl_keberangkatan)->isoFormat('DD MMMM YYYY') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-check mr-2 text-green-500"></i>
                            <span>{{ \Carbon\Carbon::parse($paket->tgl_kepulangan)->isoFormat('DD MMMM YYYY') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-users mr-2 text-purple-500"></i>
                            <span>{{ $paket->kuota_terisi }}/{{ $paket->kuota_total }} jamaah</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 lg:mt-0 lg:ml-6">
                    <div class="text-right">
                        <div class="text-sm text-gray-500 mb-1">Mulai dari</div>
                        <div class="text-2xl font-bold text-blue-600">
                            Rp {{ number_format($paket->harga_quad ?? $paket->harga_paket, 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-500">per jamaah</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Interface -->
        <div class="bg-white rounded-lg shadow-sm border" x-data="{ activeTab: 'description' }">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'description'" 
                            :class="activeTab === 'description' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-info-circle mr-2"></i>
                        Deskripsi
                    </button>
                    <button @click="activeTab = 'itinerary'" 
                            :class="activeTab === 'itinerary' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-route mr-2"></i>
                        Itinerary
                    </button>
                    <button @click="activeTab = 'hotel'" 
                            :class="activeTab === 'hotel' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-hotel mr-2"></i>
                        Hotel
                    </button>
                    <button @click="activeTab = 'airline'" 
                            :class="activeTab === 'airline' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-plane mr-2"></i>
                        Penerbangan
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Description Tab -->
                <div x-show="activeTab === 'description'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="prose max-w-none">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Tentang Paket Ini</h3>
                        @if ($paket->deskripsi)
                            <div class="prose max-w-none text-gray-800 leading-relaxed">
                                {!! auto_markdown($paket->deskripsi) !!}
                            </div>
                        @else
                            <p class="text-gray-500 italic text-sm">Deskripsi paket belum tersedia.</p>
                        @endif
                        
                        <!-- Package Details -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="text-blue-600 font-semibold mb-2">Kamar Quad</div>
                                <div class="text-2xl font-bold text-blue-800">
                                    Rp {{ number_format($paket->harga_quad ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="text-green-600 font-semibold mb-2">Kamar Triple</div>
                                <div class="text-2xl font-bold text-green-800">
                                    Rp {{ number_format($paket->harga_triple ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="text-purple-600 font-semibold mb-2">Kamar Double</div>
                                <div class="text-2xl font-bold text-purple-800">
                                    Rp {{ number_format($paket->harga_double ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-gray-600 font-semibold mb-2">Kuota Tersisa</div>
                                <div class="text-2xl font-bold text-gray-800">
                                    {{ $paket->kuota_total - $paket->kuota_terisi }} jamaah
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Itinerary Tab -->
                <div x-show="activeTab === 'itinerary'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6">Jadwal Perjalanan</h3>
                    @if($paket->itinerary && $paket->itinerary->count() > 0)
                        <div class="space-y-6">
                            @foreach($paket->itinerary->sortBy('hari_ke') as $itinerary)
                                <div class="flex">
                                    <div class="flex-shrink-0 w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                        <span class="text-blue-600 font-bold">{{ $itinerary->hari_ke }}</span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $itinerary->judul }}</h4>
                                            <span class="text-sm text-gray-500">
                                                {{ \Carbon\Carbon::parse($itinerary->tanggal)->isoFormat('DD MMMM YYYY') }}
                                            </span>
                                        </div>
                                        @if($itinerary->deskripsi)
                                            <div class="text-gray-700 leading-relaxed">
                                                {!! auto_markdown($itinerary->deskripsi) !!}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-route text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Itinerary belum tersedia untuk paket ini.</p>
                        </div>
                    @endif
                </div>

                <!-- Hotel Tab -->
                <div x-show="activeTab === 'hotel'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6">Akomodasi Hotel</h3>
                    @if($paket->hotelBookings && $paket->hotelBookings->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($paket->hotelBookings as $booking)
                                <div class="border rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $booking->hotel->nama ?? 'Hotel' }}</h4>
                                            <div class="flex items-center text-sm text-gray-600 mt-1">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <span class="capitalize">{{ $booking->hotel->kota ?? 'Kota' }}</span>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ ucfirst($booking->status_booking ?? 'belum') }}
                                        </span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Check-in:</span>
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($booking->check_in)->isoFormat('DD MMMM YYYY') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Check-out:</span>
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($booking->check_out)->isoFormat('DD MMMM YYYY') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Jumlah Malam:</span>
                                            <span class="font-medium">{{ $booking->jumlah_malam ?? \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)) }} malam</span>
                                        </div>
                                        @if($booking->jumlah_kamar)
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Jumlah Kamar:</span>
                                                <span class="font-medium">{{ $booking->jumlah_kamar }} kamar</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-hotel text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Informasi hotel belum tersedia untuk paket ini.</p>
                        </div>
                    @endif
                </div>

                <!-- Airline Tab -->
                <div x-show="activeTab === 'airline'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6">Informasi Penerbangan</h3>
                    @if($paket->flightSegments && $paket->flightSegments->count() > 0)
                        <div class="space-y-6">
                            @foreach($paket->flightSegments->sortBy('waktu_berangkat') as $flight)
                                <div class="border rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-plane text-blue-600"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-lg font-semibold text-gray-900">{{ $flight->maskapai->nama ?? 'Maskapai' }}</h4>
                                                <p class="text-sm text-gray-600">{{ $flight->nomor_penerbangan ?? 'Nomor Penerbangan' }}</p>
                                            </div>
                                        </div>
                                        @if($flight->tipe)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $flight->tipe === 'berangkat' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                {{ ucfirst($flight->tipe) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-sm text-gray-600 mb-1">Keberangkatan</div>
                                            <div class="font-semibold">{{ $flight->asal ?? 'Asal' }}</div>
                                            <div class="text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($flight->waktu_berangkat)->isoFormat('DD MMMM YYYY, HH:mm') }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-600 mb-1">Tujuan</div>
                                            <div class="font-semibold">{{ $flight->tujuan ?? 'Tujuan' }}</div>
                                            <div class="text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($flight->waktu_tiba)->isoFormat('DD MMMM YYYY, HH:mm') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-plane text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Informasi penerbangan belum tersedia untuk paket ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-600">
                <p>&copy; {{ date('Y') }} Nawita Tour. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>
</body>
</html>
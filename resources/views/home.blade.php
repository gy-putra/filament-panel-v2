{{--
  Home schedule table for Umrah Schedule & Costs.
  Future enhancement: extract to <x-umrah/schedule-table> and <x-umrah/filters> components.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ('Umrah Schedule & Costs | ' . config('app.name')) }}</title>
    <meta name="description" content="{{ $metaDesc ?? '' }}">

    @vite(['resources/css/app.css','resources/js/app.js'])
        <style>
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background: #3b82f6;
        }
        /* Lightweight responsive table styling */
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 1rem; 
            background: #3b82f6;
        }
        
        .header {
            width: 100%;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
            background: #fff;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .filters { 
            display: flex; 
            gap: 0.75rem; 
            flex-wrap: wrap; 
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .filters input, .filters button, .filters a {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .filters input {
            background: white;
        }
        
        .filters button {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            cursor: pointer;
        }
        
        .filters button:hover {
            background: #2563eb;
        }
        
        .filters a {
            background: #6b7280;
            color: white;
            text-decoration: none;
        }
        
        .filters a:hover {
            background: #4b5563;
        }
        
        .table-wrap { 
            overflow-x: auto; 
            border-radius: 0.5rem;
            /* border: 1px solid #e5e7eb; */
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white;
        }
        
        caption { 
            text-align: left; 
            margin-bottom: 1rem; 
            font-weight: 600; 
            font-size: 1.125rem;
            color: #374151;
        }
        
        th, td { 
            border-bottom: 1px solid #e5e7eb; 
            padding: 0.75rem 1rem; 
            vertical-align: top; 
            text-align: left;
        }
        
        th[scope="col"] { 
            background: #f9fafb; 
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        tr[role="rowheader"] td { 
            background: #3b82f6; 
            font-weight: 600; 
            color: #ffffff;
            font-size: 1.125rem;
        }
        
        .package-title-cell {
            padding: 0.75rem 1rem !important;
            color: #ffffff;
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .package-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .package-row:hover {
            background-color: #f8fafc;
        }
        
        .row-link {
            color: #000000;
            text-decoration-thickness: 1px;
            transition: color 0.2s ease;
        }
        
        .row-link:hover {
            color: #1d4ed8;
            text-decoration-color: #1d4ed8;
        }
        
        .row-link:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
            border-radius: 2px;
        }
        
        .seat-full { 
            font-weight: 700; 
            color: #dc2626; 
            background: #fef2f2;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .seat-available {
            color: #059669;
            font-weight: 600;
        }
        
        .price {
            font-weight: 600;
            color: #1f2937;
        }
        
        .contact-us {
            color: #6b7280;
            font-style: italic;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .view-more-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .view-more-btn:hover {
            background: #2563eb;
            color: white;
            text-decoration: none;
        }
        
        .view-more-btn:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
            color: white;
            text-decoration: none;
        }

        .book-btn {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .book-btn:hover {
            background-color: #0056b3;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 0.5rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .filters {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .filters input, .filters button, .filters a {
                width: 100%;
            }
            
            table { 
                font-size: 0.875rem; 
            }
            
            th, td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Jadwal Dan Biaya Umrah</h1>
        <p>Bandingkan paket umrah, ketersediaan kursi, dan harga Quad/Triple/Double terbaru</p>
    </div>
<div class="container">
    {{-- Filters (query-string based, SEO-friendly) --}}
    <form role="search" class="filters" method="get" action="{{ route('home') }}">
        <input type="number" name="month" min="1" max="12" value="{{ request('month') }}" placeholder="Bulan (1-12)" aria-label="Filter berdasarkan bulan">
        <input type="number" name="year" min="2000" max="2100" value="{{ request('year') }}" placeholder="Tahun" aria-label="Filter berdasarkan tahun">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama paket..." aria-label="Cari paket umrah">
        <button type="submit">Filter</button>
        <a href="{{ route('home') }}">Reset</a>
    </form>

    <div class="table-wrap">
        <table aria-describedby="table-desc">
            <!-- <caption id="table-desc">Jadwal keberangkatan umrah mendatang dikelompokkan berdasarkan nama paket</caption> -->
            <thead>
            <tr>
                <th scope="col">Paket Umrah</th>
                <th scope="col">Kursi</th>
                <th scope="col">Quad</th>
                <th scope="col">Triple</th>
                <th scope="col">Double</th>
                <th scope="col">Detail</th>
            </tr>
            </thead>
            <tbody>
            @forelse($grouped as $programTitle => $rows)
                <tr role="rowheader" style="background-color: #3b82f6 !important;">
                    <td colspan="6" class="package-title-cell" style="background-color: #3b82f6 !important; color: white !important; font-weight: bold !important; padding: 12px !important;">
                        {{ $programTitle }}
                    </td>
                </tr>
                @foreach($rows as $row)
                    <tr class="package-row">
                        <td>
                            {{ $row['package_name'] }}
                        </td>
                        <td>
                            @php
                                $total = (int) ($row['seats_total'] ?? 0);
                                $avail = (int) ($row['seats_available'] ?? 0);
                            @endphp
                            @if($avail <= 0)
                                <span class="seat-full">PENUH</span>
                            @else
                                <span class="seat-available">
                                    {{ $avail }} Seat -
                                    <span class="book-btn">Book</span>
                                </span>
                            @endif
                        </td>
                        <td class="price">
                            @if(is_null($row['quad_price'] ?? null))
                                <span class="contact-us">Hubungi kami</span>
                            @else
                                {{ 'Rp ' . number_format((int) $row['quad_price'], 0, ',', '.') . ',-' }}
                            @endif
                        </td>
                        <td class="price">
                            @if(is_null($row['triple_price'] ?? null))
                                <span class="contact-us">Hubungi kami</span>
                            @else
                                {{ 'Rp ' . number_format((int) $row['triple_price'], 0, ',', '.') . ',-' }}
                            @endif
                        </td>
                        <td class="price">
                            @if(is_null($row['double_price'] ?? null))
                                <span class="contact-us">Hubungi kami</span>
                            @else
                                {{ 'Rp ' . number_format((int) $row['double_price'], 0, ',', '.') . ',-' }}
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('paket.detail', ['paket' => $row['id']]) }}" class="view-more-btn">
                                Lihat Detail
                            </a>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <h3>Tidak ada keberangkatan ditemukan</h3>
                            <p>Coba ubah filter pencarian atau periksa kembali nanti.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
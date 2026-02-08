@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <!-- Header Laporan (Muncul di Layar, Hilang saat Cetak) -->
    <div class="flex justify-between items-center mb-8 no-print">
        <div>
            <h1 class="text-3xl font-bold uppercase tracking-tighter text-white">Laporan Kinerja Tahunan</h1>
            <p class="text-blue-200 text-sm opacity-80 mt-1">Rekapitulasi Nilai Seluruh Periode Tahun {{ $tahun }}</p>
        </div>
        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition shadow-lg flex items-center text-white">
            <i class="fas fa-print mr-2 text-sm"></i> Cetak Laporan
        </button>
    </div>

    <!-- AREA YANG DICETAK -->
    <div class="printable-area">
        <!-- Judul Khusus Cetak (Hanya muncul di kertas) -->
        <div class="print-only text-center mb-8">
            <h2 class="text-2xl font-bold uppercase text-black">Laporan Kinerja Tahunan Pegawai</h2>
            <h3 class="text-lg font-medium text-black uppercase">Dinas Kesehatan Kota Manado - Tahun {{ $tahun }}</h3>
            <hr class="mt-4 border-black">
        </div>

        <div class="glass-strong overflow-hidden border-print">
            <table class="w-full text-sm table-print">
                <thead class="bg-black/30 text-[10px] uppercase text-blue-200 font-bold border-b border-white/10 header-print">
                    <tr>
                        <th class="px-6 py-4 text-left">Nama Pegawai / NIP</th>
                        <th class="px-4 py-4 text-center">T1</th>
                        <th class="px-4 py-4 text-center">T2</th>
                        <th class="px-4 py-4 text-center">T3</th>
                        <th class="px-4 py-4 text-center">T4</th>
                        <th class="px-6 py-4 text-center bg-blue-500/20 col-total-print">Skor Akhir</th>
                        <th class="px-6 py-4 text-right">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 body-print">
                    @foreach($dataLaporan as $p)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-white name-print">{{ $p->nama }}</div>
                            <div class="text-[10px] text-blue-300 nip-print">NIP. {{ $p->nip }}</div>
                        </td>
                        
                        {{-- Tampilkan skor tiap triwulan menggunakan Service --}}
                        @for($i=1; $i<=4; $i++)
                            <td class="px-4 py-4 text-center text-xs {{ $triwulanAktif == $i ? 'bg-blue-500/20 font-bold text-white' : 'text-white/60' }} score-print">
                                {{ number_format(app(\App\Services\PerformanceService::class)->hitungNilaiTriwulan($p, $i, $tahun), 1) }}
                            </td>
                        @endfor
                        
                        <td class="px-6 py-4 text-center font-black text-blue-300 text-base total-print">
                            {{ number_format($p->skor_tahunan, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="px-3 py-1 bg-white/10 border border-white/20 rounded-lg text-[10px] font-black uppercase tracking-tighter text-emerald-300 predikat-print">
                                {{ $p->predikat_tahunan }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan (Hanya muncul di kertas) -->
        <div class="print-only mt-12 grid grid-cols-2 text-black">
            <div></div>
            <div class="text-center">
                <p>Manado, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold mt-2 mb-20 uppercase">Kepala Dinas Kesehatan Kota Manado</p>
                <p class="font-bold underline">Boby K. Kereh, SH, M.Si</p>
                <p>NIP. 198505302005011001</p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Sembunyikan elemen judul cetak di layar biasa */
    .print-only { display: none; }

    @media print {
        /* 1. Sembunyikan Navigasi & UI Website */
        aside, header, .no-print, nav, form { display: none !important; }

        /* 2. Reset Layout Utama */
        body {
            background: white !important;
            color: black !important;
            margin: 0 !important;
            padding: 20px !important;
        }

        main {
            margin-left: 0 !important;
            padding: 0 !important;
            min-width: 100% !important;
        }

        /* 3. Tampilkan Header Resmi Laporan */
        .print-only { display: block !important; }

        /* 4. Ubah Tabel Glassmorphism jadi Tabel Formal Kertas */
        .glass-strong {
            background: transparent !important;
            border: 1px solid #000 !important;
            backdrop-filter: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        .table-print { 
            width: 100% !important;
            color: black !important; 
            border-collapse: collapse !important; 
        }
        
        .header-print th {
            background-color: #f0f0f0 !important;
            color: black !important;
            border: 1px solid #000 !important;
            padding: 10px !important;
        }

        .body-print td {
            color: black !important;
            border: 1px solid #000 !important;
            padding: 8px !important;
        }

        /* Warna Teks & Elemen dalam Tabel */
        .name-print, .total-print, .score-print, .nip-print { color: black !important; }
        .predikat-print { 
            border: 1px solid #000 !important; 
            background: none !important; 
            color: black !important;
        }
        
        .col-total-print { background-color: #e0e0e0 !important; }

        /* Hindari baris terpotong antar halaman */
        tr { page-break-inside: avoid; }
    }
</style>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">Laporan Kinerja Tahunan</h1>
            <p class="text-blue-200 text-sm opacity-80">Rekapitulasi Nilai Seluruh Periode Tahun {{ $tahun }}</p>
        </div>
        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition shadow-lg">
            <i class="fas fa-print mr-2"></i> Cetak Laporan
        </button>
    </div>

    <div class="glass-strong overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-black/30 text-[10px] uppercase text-blue-200 font-bold border-b border-white/10">
                <tr>
                    <th class="px-6 py-4 text-left">Nama Pegawai / NIP</th>
                    <th class="px-6 py-4 text-center">T1</th>
                    <th class="px-6 py-4 text-center">T2</th>
                    <th class="px-6 py-4 text-center">T3</th>
                    <th class="px-6 py-4 text-center">T4</th>
                    <th class="px-6 py-4 text-center bg-blue-500/20">Skor Akhir</th>
                    <th class="px-6 py-4 text-right">Predikat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($dataLaporan as $pegawai)
                <tr class="hover:bg-white/5 transition">
                    <td class="px-6 py-4">
                        <div class="font-bold">{{ $pegawai->nama }}</div>
                        <div class="text-[10px] text-blue-300">NIP. {{ $pegawai->nip }}</div>
                    </td>
                    {{-- Tampilkan skor tiap triwulan (anda bisa memanggil logic hitungNilaiTriwulan di sini) --}}
                    @for($i=1; $i<=4; $i++)
                        <td class="px-6 py-4 text-center text-xs opacity-70">
                            {{ number_format(app(\App\Services\PerformanceService::class)->hitungNilaiTriwulan($pegawai, $i, $tahun), 1) }}
                        </td>
                    @endfor
                    
                    <td class="px-6 py-4 text-center bg-blue-500/10 font-black text-blue-300 text-base">
                        {{ number_format($pegawai->skor_tahunan, 2) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="px-3 py-1 bg-white/10 border border-white/20 rounded-lg text-[10px] font-black uppercase tracking-tighter text-emerald-300">
                            {{ $pegawai->predikat_tahunan }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
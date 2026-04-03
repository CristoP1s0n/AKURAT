@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">Penilaian & Kriteria</h1>
            <p class="text-blue-200 text-sm">Daftar bawahan yang perlu dikelola kriteria dan penilaiannya.</p>
        </div>
        <div class="glass px-4 py-2">
            <span class="text-xs font-bold uppercase tracking-widest text-blue-100">
                Total Bawahan: {{ $pegawai->count() }}
            </span>
        </div>
    </div>

    <!-- Tabel Daftar Bawahan -->
    <div class="glass overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-white/10 text-[10px] uppercase tracking-wider text-blue-100 border-b border-white/20">
                <tr>
                    <th class="px-6 py-4 text-left">Pegawai</th>
                    <th class="px-6 py-4 text-left">Jabatan</th>
                    <th class="px-6 py-4 text-center">Unit Kerja</th>
                    <th class="px-6 py-4 text-center">Progres Triwulan Ini</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($pegawai as $p)
                <tr class="hover:bg-white/10 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="{{ $p->avatar ? asset('storage/' . $p->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($p->nama).'&background=3b82f6&color=fff' }}" class="w-10 h-10 rounded-full border-2 border-blue-300/50 object-cover">
                            <div>
                                <div class="font-bold text-white">{{ $p->nama }}</div>
                                <div class="text-[10px] text-blue-300">NIP. {{ $p->nip }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-blue-100 text-xs">
                        {{ $p->jabatan }}<br>
                        <span class="text-[10px] opacity-60">Gol. {{ $p->golongan }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-200 rounded-full text-[10px] font-bold border border-blue-400/30">
                            {{ $p->unitKerja->nama_unit ?? '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $totalKriteria = 0;
                            $sudahDinilai = 0;
                            foreach($p->tupoksis as $tupoksi) {
                                foreach($tupoksi->kriteria as $k) {
                                    $totalKriteria++;
                                    if ($k->penilaian->where('user_id', $p->id)->count() > 0) {
                                        $sudahDinilai++;
                                    }
                                }
                            }
                            $persentase = $totalKriteria > 0 ? round(($sudahDinilai / $totalKriteria) * 100) : 0;
                            $semuaDinilai = ($totalKriteria > 0 && $sudahDinilai === $totalKriteria);
                            
                            // Determine colors
                            $barColor = $semuaDinilai ? 'bg-green-400' : ($totalKriteria === 0 ? 'bg-white/30' : 'bg-yellow-400');
                            $textColor = $semuaDinilai ? 'text-green-300' : ($totalKriteria === 0 ? 'text-white/50' : 'text-yellow-300');
                        @endphp
                        
                        <div class="w-full bg-white/10 rounded-full h-1.5 mb-1">
                            <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $persentase }}%"></div>
                        </div>
                        <span class="text-[9px] {{ $textColor }} font-bold uppercase">
                            {{ $semuaDinilai ? 'SUDAH DINILAI' : ($totalKriteria === 0 ? 'Belum Ada Kriteria' : 'Menunggu Penilaian (' . $persentase . '%)') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('penilaian.detail', $p->id) }}" class="btn-primary px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-wider transition">
                            Detail & Nilai <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
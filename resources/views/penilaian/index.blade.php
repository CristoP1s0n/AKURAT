@extends('layouts.app')

@section('content')
<div class="glass overflow-hidden text-white">
    <div class="p-6 border-b border-white/10 bg-white/5 flex justify-between items-center">
        <h2 class="text-xl font-bold">Daftar Penilaian Pegawai</h2>
        <div class="text-sm text-white/60">Klik detail untuk mengelola kriteria atau menilai</div>
    </div>
    
    <table class="w-full text-sm text-left">
        <thead class="bg-white/10 text-xs uppercase text-white/50">
            <tr>
                <th class="px-6 py-4">Nama / NIP</th>
                <th class="px-6 py-4">Jabatan</th>
                <th class="px-6 py-4">Unit Kerja</th>
                <th class="px-6 py-4 text-center">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
            @foreach($pegawai as $p)
            <tr class="hover:bg-white/5 transition">
                <td class="px-6 py-4">
                    <div class="font-bold">{{ $p->nama }}</div>
                    <div class="text-[10px] text-white/50">{{ $p->nip }}</div>
                </td>
                <td class="px-6 py-4 text-white/80">{{ $p->jabatan }}</td>
                <td class="px-6 py-4 text-white/80">{{ $p->unitKerja->nama_unit ?? '-' }}</td>
                <td class="px-6 py-4 text-center">
                    <a href="{{ route('penilaian.detail', $p->id) }}" class="bg-white text-red-700 px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-red-50 transition shadow-lg">
                        <i class="fas fa-search-plus mr-1"></i> Detail & Nilai
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
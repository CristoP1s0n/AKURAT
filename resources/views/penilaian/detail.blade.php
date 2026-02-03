@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <!-- Header: Info Pegawai & Skor -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold">Detail Kinerja</h1>
            <p class="text-white/70">Pegawai: <span class="text-white font-semibold">{{ $pegawai->nama }}</span> (NIP. {{ $pegawai->nip }})</p>
        </div>
        <div class="flex gap-4">
            <div class="glass px-6 py-2 text-center">
                <p class="text-[10px] uppercase text-white/60">Skor Triwulan {{ $triwulanAktif }}</p>
                <p class="text-2xl font-bold text-blue-300">{{ $skorAngka }}</p>
            </div>
            <div class="glass px-6 py-2 text-center">
                <p class="text-[10px] uppercase text-white/60">Predikat</p>
                <p class="text-2xl font-bold text-green-300">{{ $predikat }}</p>
            </div>
        </div>
    </div>

    <!-- Daftar Tupoksi -->
    @foreach($pegawai->tupoksis as $tupoksi)
    <div class="glass mb-8 overflow-hidden">
        <div class="p-5 bg-white/10 flex justify-between items-center border-b border-white/10">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3 class="font-bold text-lg">{{ $tupoksi->nama_tupoksi }}</h3>
            </div>
            
            @if(auth()->user()->role == 'kadis')
            <button onclick="openModal('{{ $tupoksi->id }}')" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-xs font-bold transition">
                <i class="fas fa-plus mr-1"></i> Tambah Kriteria
            </button>
            @endif
        </div>

        <div class="p-0">
            <table class="w-full text-sm text-left">
                <thead class="bg-black/10 text-[10px] uppercase text-white/50">
                    <tr>
                        <th class="px-6 py-3">Rincian Kriteria Penilaian</th>
                        <th class="px-6 py-3">Bukti Berkas</th>
                        <th class="px-6 py-3">Penilaian Atasan</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($tupoksi->kriteria as $kriteria)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-6 py-4">
                            <div class="font-semibold">{{ $kriteria->nama_kriteria }}</div>
                            <div class="text-[10px] text-white/40 italic">T1:{{$kriteria->t1?'Y':'N'}} | T2:{{$kriteria->t2?'Y':'N'}} | T3:{{$kriteria->t3?'Y':'N'}} | T4:{{$kriteria->t4?'Y':'N'}}</div>
                        </td>
                        <td class="px-6 py-4">
                            @php 
                                $berkas = $kriteria->berkasKinerja->where('triwulan', $triwulanAktif)->first();
                            @endphp
                            
                            @if($berkas)
                                <a href="{{ route('kinerja.berkas.download', $berkas->id) }}" class="text-blue-300 hover:underline flex items-center">
                                    <i class="fas fa-file-pdf mr-2"></i> Lihat Berkas
                                </a>
                            @else
                                <span class="text-white/30 italic">Belum unggah</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($berkas)
                            <form action="{{ route('penilaian.simpan') }}" method="POST" class="flex items-center gap-3">
                                @csrf
                                <input type="hidden" name="berkas_id" value="{{ $berkas->id }}">
                                <div class="flex glass p-1 rounded-full bg-black/20">
                                    @for($i=0; $i<=3; $i++)
                                        <label class="cursor-pointer px-3 py-1 rounded-full text-[10px] hover:bg-white/20 transition {{ ($berkas->penilaian->skor ?? -1) == $i ? 'bg-blue-500 font-bold' : '' }}">
                                            <input type="radio" name="skor" value="{{ $i }}" class="hidden" onchange="this.form.submit()">
                                            {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                                @if($berkas->status_penilaian == 'sudah')
                                    <i class="fas fa-check-circle text-green-400" title="Sudah dinilai"></i>
                                @endif
                            </form>
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(auth()->user()->role == 'kadis' && $kriteria->berkasKinerja->count() == 0)
                            <form action="{{ route('kriteria.destroy', $kriteria->id) }}" method="POST" onsubmit="return confirm('Hapus kriteria ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>

<!-- Modal Tambah Kriteria -->
<div id="modalKriteria" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[100] flex items-center justify-center">
    <div class="glass w-full max-w-md p-8 text-white">
        <h3 class="text-xl font-bold mb-6">Tambah Kriteria Baru</h3>
        <form action="{{ route('kriteria.store') }}" method="POST">
            @csrf
            <input type="hidden" name="tupoksi_id" id="modal_tupoksi_id">
            
            <div class="mb-4">
                <label class="text-xs text-white/60 uppercase">Nama Kriteria</label>
                <input type="text" name="nama_kriteria" class="w-full bg-white/10 border border-white/20 rounded-lg p-3 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div class="mb-6">
                <label class="text-xs text-white/60 uppercase block mb-2">Aktif di Periode</label>
                <div class="flex justify-between glass p-3">
                    @foreach(['t1','t2','t3','t4'] as $t)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="{{ $t }}" checked class="rounded border-white/20 bg-white/10 text-blue-500">
                        <span class="text-xs font-bold uppercase">{{ $t }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-6 py-2 text-sm font-bold text-white/60 hover:text-white">Batal</button>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 px-6 py-2 rounded-lg font-bold shadow-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(tupoksiId) {
        document.getElementById('modal_tupoksi_id').value = tupoksiId;
        document.getElementById('modalKriteria').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('modalKriteria').classList.add('hidden');
    }
</script>
@endsection
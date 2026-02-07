@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight">Tugas & Unggah Berkas</h1>
        <p class="text-blue-200 text-sm opacity-80 mt-1">
            Lengkapi bukti dukung kinerja untuk <span class="font-bold text-white uppercase">Triwulan {{ $triwulanAktif }} - {{ $tahunAktif }}</span>
        </p>
    </div>

    {{-- Gunakan @forelse agar jika data kosong tidak muncul layar putih polos --}}
    @forelse($tupoksis as $tupoksi)
    <div class="glass-strong mb-8 overflow-hidden">
        <!-- Header Tupoksi: Area Utama Informasi & Upload Berkas -->
        <div class="p-5 bg-white/10 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-white/20 gap-4">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg text-white shrink-0">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-white leading-tight">{{ $tupoksi->nama_tupoksi }}</h3>
                    <div class="flex items-center mt-1 gap-2">
                        {{-- Logika Cek Berkas per Tupoksi --}}
                        @php $berkas = $tupoksi->berkasKinerja->where('triwulan', $triwulanAktif)->first(); @endphp
                        
                        @if($berkas && $berkas->file_path !== '-')
                            <span class="text-[9px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-500/30 uppercase font-black tracking-widest">
                                <i class="fas fa-check-circle mr-1"></i> Berkas Terunggah
                            </span>
                        @else
                            <span class="text-[9px] bg-red-500/20 text-red-300 px-2 py-0.5 rounded-full border border-red-500/30 uppercase font-black tracking-widest">
                                Belum Ada Berkas
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Tombol Aksi Berkas -->
            <div class="flex items-center gap-2">
                @if($berkas && $berkas->file_path !== '-')
                    <!-- Link Lihat/Download -->
                    <a href="{{ route('kinerja.berkas.download', $berkas->id) }}" 
                       class="bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-300 px-4 py-2 rounded-lg text-[10px] font-bold uppercase border border-emerald-500/30 transition-all flex items-center">
                        <i class="fas fa-eye mr-2"></i> Lihat Berkas
                    </a>

                    <!-- Tombol Hapus Berkas -->
                    <form action="{{ route('kinerja.berkas.destroy', $berkas->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-500/20 hover:bg-red-500 text-red-100 px-4 py-2 rounded-lg text-[10px] font-bold uppercase border border-red-500/30 transition-all flex items-center">
                            <i class="fas fa-trash-alt mr-2"></i> Hapus
                        </button>
                    </form>
                @endif

                <!-- Tombol Upload -->
                <button onclick="openModalUpload('{{ $tupoksi->id }}', '{{ addslashes($tupoksi->nama_tupoksi) }}')" 
                        class="bg-blue-600 hover:bg-blue-500 px-5 py-2 rounded-lg text-[10px] font-black uppercase text-white shadow-lg shadow-blue-500/20 transition-all flex items-center">
                    <i class="fas fa-upload mr-2"></i> {{ $berkas && $berkas->file_path !== '-' ? 'Ganti Berkas' : 'Unggah Berkas' }}
                </button>
            </div>
        </div>

        <!-- Tabel Rincian Kriteria & Nilai -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-black/20 text-[10px] uppercase text-blue-200 font-bold">
                    <tr>
                        <th class="px-6 py-4 text-left">Rincian Kriteria Penilaian</th>
                        <th class="px-6 py-4 text-center">Hasil Penilaian Atasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($tupoksi->kriteria as $k)
                    <tr class="hover:bg-white/5 transition-all">
                        <td class="px-6 py-4">
                            <div class="font-bold text-white">{{ $k->nama_kriteria }}</div>
                            <div class="text-[10px] text-blue-300 opacity-60 mt-1 italic">Tugas Triwulanan</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php 
                                $nilai = \App\Models\Penilaian::where('kriteria_id', $k->id)
                                         ->where('user_id', auth()->id())
                                         ->where('triwulan', $triwulanAktif)
                                         ->where('tahun', $tahunAktif)->first();
                            @endphp
                            @if($nilai)
                                <div class="flex flex-col items-center">
                                    <span class="text-2xl font-black text-blue-300">{{ $nilai->skor }}</span>
                                    <span class="text-[8px] uppercase font-bold opacity-50 tracking-tighter">Skor Tercatat</span>
                                </div>
                            @else
                                <span class="text-white/20 italic text-[10px]">Belum Dinilai Atasan</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-center text-white/20 italic">Tidak ada rincian kriteria untuk tupoksi ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <!-- Kondisi Jika Tupoksi Benar-benar Kosong -->
    <div class="glass-strong p-20 text-center border-2 border-dashed border-white/10">
        <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 text-white/20">
            <i class="fas fa-clipboard-list fa-3x"></i>
        </div>
        <h4 class="text-xl font-bold text-white/60">Tugas Belum Tersedia</h4>
        <p class="text-white/40 max-w-sm mx-auto mt-2 text-sm">Butir Tupoksi dan Kriteria Anda belum ditetapkan oleh Kepala Dinas untuk tahun {{ $tahunAktif }}.</p>
    </div>
    @endforelse
</div>

<!-- Modal Unggah Berkas -->
<div id="modalUpload" class="fixed inset-0 bg-black/80 backdrop-blur-md hidden z-[100] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-md p-8 border border-white/30 text-left">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-xl font-bold text-white">Unggah Bukti Dukung</h3>
                <p id="label_tupoksi" class="text-[10px] text-blue-300 italic mt-1 font-medium uppercase"></p>
            </div>
            <button onclick="closeModalUpload()" class="text-white/30 hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <form action="{{ route('kinerja.upload.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tupoksi_id" id="modal_tupoksi_id">
            <input type="hidden" name="triwulan" value="{{ $triwulanAktif }}">
            
            <div class="space-y-6">
                <div class="border-2 border-dashed border-white/20 rounded-2xl p-8 text-center bg-black/20 hover:border-blue-500/50 transition-all group">
                    <input type="file" name="file_bukti" id="file_bukti" class="hidden" accept=".pdf,.jpg,.jpeg,.png" required 
                           onchange="document.getElementById('display_name').innerText = this.files[0].name; document.getElementById('display_name').classList.add('text-blue-300')">
                    <label for="file_bukti" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt fa-3x text-white/20 group-hover:text-blue-400 transition-colors mb-3"></i>
                        <p class="text-sm font-medium text-white" id="display_name">Klik untuk pilih file bukti</p>
                        <p class="text-[10px] text-white/40 mt-1 uppercase">Format: PDF, JPG, PNG (Max 5MB)</p>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-xl font-black text-xs text-white uppercase tracking-widest shadow-lg transition-all active:scale-95">
                    Kirim Dokumen
                </button>
                <button type="button" onclick="closeModalUpload()" class="w-full text-white/40 text-[10px] font-black uppercase tracking-widest mt-2 hover:text-white transition">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalUpload(id, nama) {
        document.getElementById('modal_tupoksi_id').value = id;
        document.getElementById('label_tupoksi').innerText = "Tupoksi: " + nama;
        const m = document.getElementById('modalUpload');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeModalUpload() {
        const m = document.getElementById('modalUpload');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // Klik di luar modal untuk menutup
    window.onclick = function(event) {
        if (event.target == document.getElementById('modalUpload')) closeModalUpload();
    }
</script>
@endsection
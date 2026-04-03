@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <!-- Header: Profil & Skor -->
    <div class="flex flex-col lg:flex-row items-center justify-between mb-10 gap-6 flex-nowrap overflow-x-auto pb-4">
        <!-- Info Pegawai (Kiri) -->
        <div class="flex items-center gap-4 min-w-0 flex-1">
            <div class="w-16 h-16 glass-strong p-1 shrink-0">
                <img src="{{ $pegawai->avatar ? asset('storage/' . $pegawai->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($pegawai->nama).'&background=3b82f6&color=fff' }}" class="w-full h-full rounded-xl object-cover">
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold tracking-tight text-white truncate lg:whitespace-normal">{{ $pegawai->nama }}</h1>
                <p class="text-blue-200 text-sm font-medium opacity-80 whitespace-normal break-words leading-relaxed mt-1">
                    <span class="inline-block"><i class="fas fa-id-card mr-1"></i> NIP. {{ $pegawai->nip }}</span>
                    <span class="mx-1 hidden md:inline">|</span>
                    <span class="inline-block text-blue-100"><i class="fas fa-briefcase mr-1"></i> {{ $pegawai->jabatan }}</span>
                </p>
            </div>
        </div>
        
        <!-- Action & Stats (Kanan) -->
        <div class="flex items-center gap-4 flex-nowrap shrink-0">
            @if (auth()->user()->role == 'kadis')
            <button onclick="openModalTupoksi()" class="bg-blue-600 hover:bg-blue-500 px-6 py-3 rounded-xl text-xs font-bold shadow-xl transition-all whitespace-nowrap text-white">
                <i class="fas fa-plus mr-2"></i> TAMBAH TUPOKSI
            </button>
            @endif

            <div class="glass-strong px-6 py-3 text-center min-w-[140px] border-l-4 border-blue-500">
                <p class="text-[9px] uppercase text-blue-200 mb-1 font-black tracking-widest">SKOR T{{ $triwulanAktif }}</p>
                <p class="text-3xl font-black text-white">{{ number_format($skorAngka, 2) }}</p>
            </div>
            
            <div class="glass-strong px-6 py-3 text-center min-w-[160px] border-l-4 border-emerald-500">
                <p class="text-[9px] uppercase text-blue-200 mb-1 font-black tracking-widest">PREDIKAT</p>
                <p class="text-[10px] font-black text-blue-100 uppercase mt-2 tracking-tighter leading-tight">{{ $predikat }}</p>
            </div>
        </div>
    </div>

    <!-- TABEL UTAMA TUPOKSI -->
    @forelse($pegawai->tupoksis->where('tahun', date('Y')) as $tupoksi)
        <div class="glass-strong mb-8 overflow-hidden">
            {{-- Header Tupoksi --}}
            <div class="p-5 bg-white/10 flex justify-between items-center border-b border-white/20">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg flex items-center justify-center mr-4 shadow-lg text-white">
                        <i class="fas fa-tasks fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white leading-tight">{{ $tupoksi->nama_tupoksi }}</h3>
                        <div class="flex items-center gap-3 mt-1">
                            <p class="text-[10px] text-blue-200 opacity-60 uppercase tracking-widest">Target Tahunan {{ date('Y') }}</p>
                            
                            {{-- LOGIKA LIHAT BERKAS PER TUPOKSI --}}
                            @php
                            $berkas = $tupoksi->berkasKinerja ? $tupoksi->berkasKinerja->where('triwulan', $triwulanAktif)->where('user_id', $pegawai->id)->first() : null; 
                            @endphp
                            @if($berkas && $berkas->file_path !== '-')
                                <span class="text-white/20">|</span>
                                <a href="{{ route('kinerja.berkas.download', $berkas->id) }}" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center transition-colors">
                                    <i class="fas fa-file-pdf mr-1.5"></i> LIHAT BERKAS DUKUNG
                                </a>
                            @else
                                <span class="text-white/20">|</span>
                                <span class="text-[10px] text-white/30 italic"> Berkas belum tersedia</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    @if(auth()->user()->role == 'kadis')
                    <button onclick="openModalKriteria('{{ $tupoksi->id }}', '{{ addslashes($tupoksi->nama_tupoksi) }}')" class="text-[10px] bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-bold border border-white/20 transition-all flex items-center">
                        <i class="fas fa-plus-circle mr-1 text-sm text-blue-400"></i> Tambah Rincian
                    </button>

                    <!-- Tombol Hapus Tupoksi -->
                    <form action="{{ route('tupoksi.destroy', $tupoksi->id) }}" method="POST" 
              onsubmit="return confirm('PERINGATAN SANGAT KERAS!\n\nMenghapus Butir Tupoksi ini akan menghapus:\n1. Seluruh Rincian Kriteria di dalamnya.\n2. Seluruh Berkas Bukti yang sudah diunggah staff.\n3. Seluruh NILAI SKOR dari Triwulan 1 sampai 4.\n\nData tidak bisa dikembalikan. Lanjutkan?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-9 h-9 bg-red-500/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all flex items-center justify-center border border-red-500/30" title="Hapus Seluruh Tupoksi">
                            <i class="fas fa-trash-alt text-sm"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-black/20 text-[10px] uppercase text-blue-200 font-bold">
                        <tr>
                            <th class="px-6 py-4 text-left" style="width: 50%">Rincian Kriteria Penilaian</th>
                            <th class="px-6 py-4 text-center">Penilaian Atasan (0-3)</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @php $kriteriaAktif = $tupoksi->kriteria->where('t'.$triwulanAktif, true); @endphp
                        
                        @forelse($kriteriaAktif as $kriteria)
                        <tr class="hover:bg-white/5 transition-all group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white group-hover:text-blue-200 transition">{{ $kriteria->nama_kriteria }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('penilaian.simpan') }}" method="POST" class="flex justify-center items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="kriteria_id" value="{{ $kriteria->id }}">
                                    <input type="hidden" name="pegawai_id" value="{{ $pegawai->id }}">

                                    <div class="flex bg-black/40 p-1 rounded-full border border-white/10">
                                        @php 
                                            $nilaiExist = \App\Models\Penilaian::where('kriteria_id', $kriteria->id)
                                                            ->where('user_id', $pegawai->id)
                                                            ->where('triwulan', $triwulanAktif)
                                                            ->first();
                                            $currentSkor = $nilaiExist->skor ?? -1;
                                        @endphp
                                        @for($i=0; $i<=3; $i++)
                                            <label class="cursor-pointer w-8 h-8 flex items-center justify-center rounded-full text-[10px] transition-all {{ $currentSkor == $i ? 'bg-blue-600 font-bold text-white shadow-lg' : 'hover:bg-white/10 text-white/40' }}">
                                                <input type="radio" name="skor" value="{{ $i }}" class="hidden" onchange="this.form.submit()" {{ $currentSkor == $i ? 'checked' : '' }}>
                                                {{ $i }}
                                            </label>
                                        @endfor
                                    </div>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                @if(auth()->user()->role == 'kadis')
                                <div class="flex justify-end gap-2">
                                <!-- Tombol Edit (Membuka Modal Edit) -->
                                    <button onclick="openModalEditKriteria('{{ $kriteria->id }}', '{{ $kriteria->nama_kriteria }}', {{ $kriteria->t1 }}, {{ $kriteria->t2 }}, {{ $kriteria->t3 }}, {{ $kriteria->t4 }})" 
                                            class="w-9 h-9 rounded-lg bg-blue-500 hover:bg-blue-400 text-white shadow-lg shadow-blue-500/40 transition-all flex items-center justify-center transform active:scale-90" title="Edit Kriteria">
                                        <i class="fas fa-pencil-alt text-xs"></i>
                                    </button>

                                    <form action="{{ route('kriteria.destroy', $kriteria->id) }}" method="POST" onsubmit="return confirm('PERINGATAN KERAS!\n\nMenghapus kriteria ini akan menghapus SELURUH NILAI (Skor 0-3) yang sudah diberikan atasan dari Triwulan 1 sampai 4 untuk pegawai ini.\n\nData yang dihapus tidak dapat dikembalikan. Apakah Anda yakin?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-9 h-9 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center border border-red-500/20">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-white/20 italic text-xs">
                                Belum ada rincian kriteria aktif untuk Triwulan {{ $triwulanAktif }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="glass-strong p-20 text-center border-2 border-dashed border-white/10">
            <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 text-white/20">
                <i class="fas fa-folder-open fa-3x"></i>
            </div>
            <h4 class="text-xl font-bold text-white/60">Belum Ada Tupoksi</h4>
            <p class="text-white/40 max-w-sm mx-auto mt-2 text-sm">Target tahunan belum ditetapkan untuk pegawai ini.</p>
            @if (auth()->user()->role == 'kadis')
                <button onclick="openModalTupoksi()" class="mt-6 bg-blue-600 px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest text-white transition-all hover:bg-blue-500 shadow-xl">Tambahkan Tupoksi Baru</button>
            @endif
        </div>
    @endforelse
</div>

<!-- MODAL TAMBAH TUPOKSI -->
<div id="modalTupoksi" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[100] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-md p-8 border border-white/30 text-left">
        <h3 class="text-xl font-bold text-white mb-2">Tambah Tupoksi Baru</h3>
        <form action="{{ route('tupoksi.store') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $pegawai->id }}">
            <input type="hidden" name="tahun" value="{{ date('Y') }}">
            <div class="space-y-5">
                <div>
                    <label class="text-[10px] uppercase font-bold text-blue-200 tracking-widest mb-2 block">Deskripsi Tugas Utama</label>
                    <textarea name="nama_tupoksi" required class="w-full bg-black/40 border border-white/20 rounded-xl p-4 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                    <button type="button" onclick="closeModalTupoksi()" class="text-xs font-bold text-white/50 hover:text-white px-4">BATAL</button>
                    <button type="submit" class="bg-blue-600 px-8 py-3 rounded-xl text-xs font-bold text-white uppercase tracking-widest shadow-lg">SIMPAN TUPOKSI</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH KRITERIA -->
<div id="modalKriteria" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[100] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-md p-8 border border-white/30 text-left">
        <h3 class="text-xl font-bold text-white mb-2">Tambah Rincian Kriteria</h3>
        <p id="label_tupoksi_parent" class="text-[10px] text-blue-300 italic mb-6 font-medium uppercase"></p>

        <form action="{{ route('kriteria.store') }}" method="POST">
            @csrf
            <input type="hidden" name="tupoksi_id" id="modal_tupoksi_id">
            <div class="space-y-5">
                <div>
                    <label class="text-[10px] uppercase font-bold text-blue-200 tracking-widest mb-2 block">Nama Kriteria Penilaian</label>
                    <input type="text" name="nama_kriteria" required class="w-full bg-black/40 border border-white/20 rounded-xl p-4 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-[10px] uppercase font-bold text-blue-200 tracking-widest mb-3 block text-center">Aktifkan Pada Periode:</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach(['t1','t2','t3','t4'] as $t)
                        <label class="flex flex-col items-center bg-white/5 p-3 rounded-xl border border-white/10 cursor-pointer hover:bg-blue-500/20 transition-all">
                            <input type="checkbox" name="{{ $t }}" value="1" checked class="rounded border-white/20 text-blue-500 mb-2">
                            <span class="text-[10px] uppercase font-black text-white">{{ $t }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 py-4 rounded-xl font-black text-xs text-white uppercase tracking-widest transition-all mt-4">KONFIRMASI</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kriteria -->
<div id="modalEditKriteria" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[110] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-md p-8 border border-white/30 text-left">
        <h3 class="text-xl font-bold text-white mb-6">Edit Rincian Kriteria</h3>

        <form id="formEditKriteria" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-5 text-left">
                <div>
                    <label class="text-[10px] uppercase font-bold text-blue-200 tracking-widest mb-2 block">Nama Kriteria Penilaian</label>
                    <input type="text" name="nama_kriteria" id="edit_nama_kriteria" required 
                        class="w-full bg-black/40 border border-white/20 rounded-xl p-4 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-[10px] uppercase font-bold text-blue-200 tracking-widest mb-3 block text-center">Aktifkan Pada Periode:</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach(['t1','t2','t3','t4'] as $t)
                        <label class="flex flex-col items-center bg-white/5 p-3 rounded-xl border border-white/10 cursor-pointer hover:bg-blue-500/20 transition-all">
                            <input type="checkbox" name="{{ $t }}" value="1" id="edit_{{ $t }}" class="rounded border-white/20 text-blue-500 mb-2">
                            <span class="text-[10px] uppercase font-black text-white">{{ $t }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeModalEditKriteria()" class="flex-1 py-4 text-xs font-bold text-white/50 hover:text-white uppercase">Batal</button>
                    <button type="submit" class="flex-[2] bg-blue-600 hover:bg-blue-500 py-4 rounded-xl font-black text-xs text-white uppercase tracking-widest transition-all shadow-lg shadow-blue-500/20">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalTupoksi() {
        const m = document.getElementById('modalTupoksi');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeModalTupoksi() {
        const m = document.getElementById('modalTupoksi');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    function openModalKriteria(id, namaTupoksi) {
        document.getElementById('modal_tupoksi_id').value = id;
        document.getElementById('label_tupoksi_parent').innerText = "Tupoksi: " + namaTupoksi;
        const m = document.getElementById('modalKriteria');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeModalKriteria() {
        const m = document.getElementById('modalKriteria');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    function openModalEditKriteria(id, nama, t1, t2, t3, t4) {
        // Set Action URL Form secara dinamis
        document.getElementById('formEditKriteria').action = "/kriteria/update/" + id;
        
        // Isi field nama
        document.getElementById('edit_nama_kriteria').value = nama;
        
        // Set checkbox periode (1 = checked, 0 = unchecked)
        document.getElementById('edit_t1').checked = t1 == 1;
        document.getElementById('edit_t2').checked = t2 == 1;
        document.getElementById('edit_t3').checked = t3 == 1;
        document.getElementById('edit_t4').checked = t4 == 1;

        const m = document.getElementById('modalEditKriteria');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeModalEditKriteria() {
        const m = document.getElementById('modalEditKriteria');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    window.onclick = function(event) {
        if (event.target.id == 'modalTupoksi') closeModalTupoksi();
        if (event.target.id == 'modalKriteria') closeModalKriteria();
        if (event.target.id == 'modalEditKriteria') closeModalEditKriteria();
    }
</script>
@endsection
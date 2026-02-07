@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Data Pegawai</h1>
        <div class="flex gap-3">
            <button class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg text-sm font-semibold flex items-center transition">
                <i class="fas fa-filter mr-2"></i> Filter Pencarian
            </button>
            <button onclick="openModalPegawai()" class="btn-primary px-6 py-2.5 rounded-lg text-sm font-bold flex items-center transition">
            <i class="fas fa-plus mr-2"></i> Tambah pegawai baru
            </button>
        </div>
    </div>

    <div class="glass overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-white/10 text-xs uppercase text-blue-100 border-b-2 border-white/20">
                <tr>
                <th class="px-6 py-4 text-left font-bold">NAMA</th>
                <th class="px-6 py-4 text-left font-bold">NIP</th>
                <th class="px-6 py-4 text-left font-bold">JABATAN</th>
                <th class="px-6 py-4 text-left font-bold">GOLONGAN</th>
                <th class="px-6 py-4 text-left font-bold">UNIT KERJA</th>
                <th class="px-6 py-4 text-left font-bold">ROLE</th>
                <th class="px-6 py-4 text-left font-bold">STATUS</th>
                <th class="px-6 py-4 text-center font-bold">AKSI</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($pegawai as $p)
                <tr class="hover:bg-white/10 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($p->nama) }}&background=0ea5e9&color=fff" class="w-10 h-10 rounded-full mr-3 border-2 border-blue-300/50 shadow-lg">
                            <span class="font-medium text-white">{{ $p->nama }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-blue-200">{{ $p->nip }}</td>
                    <td class="px-6 py-4 text-blue-100">{{ $p->jabatan }}</td>
                    <td class="px-6 py-4 text-blue-100">{{ $p->golongan }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 bg-blue-500/30 text-blue-200 rounded text-[10px] text-xs font-semibold">{{ $p->unitKerja->nama_unit ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-[10px] font-bold uppercase">{{ $p->role }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($p->is_active)
                            <span class="px-3 py-1 bg-green-500/30 text-green-200 rounded-full text-xs font-semibold">Aktif</span>
                        @else
                            <span class="text-red-400 text-[10px] font-bold bg-red-500/10 px-2 py-1 rounded-full border border-red-500/20">Non-Aktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button class="text-blue-300 hover:text-blue-100 transition">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pegawai -->
<div id="modalPegawai" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[100] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-2xl p-8 border border-white/30 animate-scale-up text-left">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white uppercase tracking-wider">Pendaftaran Pegawai Baru</h3>
            <button onclick="closeModalPegawai()" class="text-white/50 hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <form action="{{ route('pegawai.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Kolom Kiri -->
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Nama Lengkap</label>
                        <input type="text" name="nama" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">NIP (Username)</label>
                        <input type="text" name="nip" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Jabatan</label>
                        <input type="text" name="jabatan" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Golongan</label>
                        <select name="golongan" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                            <option value="II/a">II/a</option>
                            <option value="II/b">II/b</option>
                            <option value="II/c">II/c</option>
                            <option value="III/a">III/a</option>
                            <option value="III/b">III/b</option>
                            <option value="III/c">III/c</option>
                            <option value="III/d">III/d</option>
                            <option value="IV/a">IV/a</option>
                            <option value="IV/b">IV/b</option>
                            <option value="IV/c">IV/c</option>
                        </select>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Unit Kerja</label>
                        <select name="unit_id" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->nama_unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Role Akses</label>
                        <select name="role" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            <option value="staff">Staff</option>
                            <option value="kasie">Kepala Seksi / Kasubbag</option>
                            <option value="kabag">Kepala Bidang / Sekretaris</option>
                            <option value="kadis">Kepala Dinas</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-blue-200 mb-1 block">Atasan Langsung (Penilai)</label>
                        <select name="parent_id" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            <option value="">-- Tanpa Atasan (Kadin) --</option>
                            @foreach($atasans as $a)
                                <option value="{{ $a->id }}">{{ $a->nama }} ({{ $a->jabatan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-6">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 py-4 rounded-xl font-black text-xs text-white uppercase tracking-widest transition-all shadow-lg shadow-emerald-500/20">
                            Simpan Pegawai
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalPegawai() {
        const m = document.getElementById('modalPegawai');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeModalPegawai() {
        const m = document.getElementById('modalPegawai');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
</script>
@endsection
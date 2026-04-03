@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Data Pegawai</h1>
            
            <button onclick="openModalPegawai()" class="btn-primary px-6 py-2.5 rounded-lg text-sm font-bold flex items-center transition">
                <i class="fas fa-plus mr-2"></i> Tambah pegawai baru
            </button>
    </div>

     <!-- Panel Filter Pencarian -->
    <div class="glass-strong p-6 mb-8 border border-white/20">
        <form action="{{ route('pegawai.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- Search Nama/NIP -->
            <div class="md:col-span-1">
                <label class="text-[9px] font-black uppercase text-blue-200 mb-1 block">Cari Nama / NIP</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Ketik keyword...">
            </div>

            <!-- Unit Kerja -->
            <div>
                <label class="text-[9px] font-black uppercase text-blue-200 mb-1 block">Unit Kerja</label>
                <select name="unit_id" class="w-full bg-black/30 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white">
                    <option value="">Semua Unit</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_unit }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Golongan -->
            <div>
                <label class="text-[9px] font-black uppercase text-blue-200 mb-1 block">Golongan</label>
                <select name="golongan" class="w-full bg-black/30 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white">
                    <option value="">Semua Golongan</option>
        
                    @php
                        $daftarGolongan = [
                            'I/a', 'I/b', 'I/c', 'I/d',
                            'II/a', 'II/b', 'II/c', 'II/d',
                            'III/a', 'III/b', 'III/c', 'III/d',
                            'IV/a', 'IV/b', 'IV/c', 'IV/d', 'IV/e', 'PPPK / V', 'PPPK / VI', 'PPPK / VII', 'PPPK / IX', 'PPPK / X'
                        ];
                    @endphp

                    @foreach($daftarGolongan as $gol)
                        <option value="{{ $gol }}" {{ request('golongan') == $gol ? 'selected' : '' }}>
                            {{ $gol }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Aktif -->
            <div>
                <label class="text-[9px] font-black uppercase text-blue-200 mb-1 block">Status Akun</label>
                <select name="status" class="w-full bg-black/30 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Non-Aktif</option>
                </select>
            </div>

            <!-- Tombol Aksi Filter -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg">
                    Filter
                </button>
                <a href="{{ route('pegawai.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-all border border-white/10" title="Reset Filter">
                    <i class="fas fa-sync-alt text-xs"></i>
                </a>
            </div>
        </form>
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
                            <img src="{{ $p->avatar ? asset('storage/' . $p->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($p->nama).'&background=0ea5e9&color=fff' }}" class="w-10 h-10 rounded-full mr-3 border-2 border-blue-300/50 shadow-lg object-cover">
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
                        <button onclick="openModalEditPegawai({{ json_encode($p) }})" 
                                class="text-blue-300 hover:text-white transition p-2 bg-white/5 rounded-lg border border-white/10">
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
                            <option value="">-- Pilih Golongan --</option>
                            <option value="I/a">I/a</option>
                            <option value="I/b">I/b</option>
                            <option value="I/c">I/c</option>
                            <option value="I/d">I/d</option>
                            <option value="II/a">II/a</option>
                            <option value="II/b">II/b</option>
                            <option value="II/c">II/c</option>
                            <option value="II/d">II/d</option>
                            <option value="III/a">III/a</option>
                            <option value="III/b">III/b</option>
                            <option value="III/c">III/c</option>
                            <option value="III/d">III/d</option>
                            <option value="IV/a">IV/a</option>
                            <option value="IV/b">IV/b</option>
                            <option value="IV/c">IV/c</option>
                            <option value="IV/d">IV/d</option>
                            <option value="IV/e">IV/e</option>
                            <option value="IV/e">PPPK / V</option>
                            <option value="IV/e">PPPK / VI</option>
                            <option value="IV/e">PPPK / VII</option>
                            <option value="IV/e">PPPK / IX</option>
                            <option value="IV/e">PPPK / X</option>
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
                            <option value="">Kepala Dinas</option>
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

<!-- Modal Edit Pegawai -->
<div id="modalEditPegawai" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[110] items-center justify-center p-4 text-left">
    <div class="glass-strong w-full max-w-2xl p-8 border border-white/30">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white uppercase">Edit Data Pegawai</h3>
            <button onclick="closeModalEditPegawai()" class="text-white/50 hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <form id="formEditPegawai" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_nama" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">NIP</label>
                        <input type="text" name="nip" id="edit_nip" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Jabatan</label>
                        <input type="text" name="jabatan" id="edit_jabatan" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Golongan</label>
                        <select name="golongan" id="edit_golongan" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            @foreach(['II/a','II/b','II/c','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c', 'PPPK / V', 'PPPK / VI', 'PPPK / VII', 'PPPK / IX', 'PPPK / X'] as $gol)
                                <option value="{{ $gol }}">{{ $gol }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Unit Kerja</label>
                        <select name="unit_id" id="edit_unit_id" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->nama_unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Role</label>
                        <select name="role" id="edit_role" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            <option value="staff">Staff</option>
                            <option value="kasie">Kepala Seksi</option>
                            <option value="kabag">Kepala Bidang</option>
                            <option value="kadis">Kepala Dinas</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Status Akun</label>
                        <select name="is_active" id="edit_is_active" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-blue-200 uppercase">Atasan Langsung</label>
                        <select name="parent_id" id="edit_parent_id" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white">
                            <option value="">-- Tanpa Atasan --</option>
                            @foreach($atasans as $a)
                                <option value="{{ $a->id }}">{{ $a->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full btn-primary py-4 rounded-xl font-bold text-xs uppercase mt-6 tracking-widest shadow-lg">
                Simpan Perubahan
            </button>
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
    function openModalEditPegawai(user) {
        // 1. Ambil elemen form
        const form = document.getElementById('formEditPegawai');
        
        // 2. Set action form secara dinamis
        // Gunakan template literal (backtick) untuk memasukkan ID
        form.action = `{{ url('/pegawai/update') }}/${user.id}`;

        // 3. Isi data ke dalam input
        document.getElementById('edit_nama').value = user.nama;
        document.getElementById('edit_nip').value = user.nip;
        document.getElementById('edit_jabatan').value = user.jabatan;
        document.getElementById('edit_golongan').value = user.golongan;
        document.getElementById('edit_unit_id').value = user.unit_id;
        document.getElementById('edit_role').value = user.role;
        
        // Status Akun (is_active biasanya 0/1 atau true/false di database)
        document.getElementById('edit_is_active').value = user.is_active ? "1" : "0";
        
        // Atasan Langsung
        document.getElementById('edit_parent_id').value = user.parent_id || "";

        // 4. Tampilkan modal
        const m = document.getElementById('modalEditPegawai');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeModalEditPegawai() {
        const m = document.getElementById('modalEditPegawai');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // Klik luar modal untuk tutup
    window.onclick = function(event) {
        if (event.target.id == 'modalPegawai') closeModalPegawai();
        if (event.target.id == 'modalEditPegawai') closeModalEditPegawai();
    }
</script>
@endsection
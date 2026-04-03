@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight">Pengaturan Profil</h1>
        <p class="text-blue-200 text-sm opacity-80 mt-1">Kelola informasi akun dan keamanan kata sandi Anda.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bagian Kiri: Update Foto & Info Dasar -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass-strong p-8">
                <h3 class="text-lg font-bold mb-6 flex items-center">
                    <i class="fas fa-user-circle mr-3 text-blue-400"></i> Informasi Pribadi
                </h3>
                
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- Preview Foto -->
                        <div class="flex flex-col items-center space-y-4">
                            <div class="w-32 h-32 glass-card p-1 relative group">
                                @if(auth()->user()->avatar)
                                    <img id="profile-preview" src="{{ asset('storage/' . auth()->user()->avatar) }}" class="w-full h-full object-cover rounded-xl shadow-2xl">
                                @else
                                    <img id="profile-preview" src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->nama).'&background=3b82f6&color=fff' }}" class="w-full h-full rounded-xl object-cover">
                                @endif
                                <label for="avatar" class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-xl">
                                    <i class="fas fa-camera text-white"></i>
                                </label>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-blue-300 uppercase font-black">Klik foto untuk ganti</p>
                                <p class="text-[9px] text-blue-300 italic mt-1 leading-tight">
                                    Rekomendasi: Foto formal<br>dengan rasio persegi (1:1)
                                </p>
                                <input type="file" name="avatar" id="avatar" class="hidden" accept="image/*" onchange="previewImage(this)">
                            </div>
                        </div>

                        <!-- Input Teks -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ auth()->user()->nama }}" class="w-full bg-blue-900/30 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block opacity-50">NIP (Username)</label>
                                    <input type="text" value="{{ auth()->user()->nip }}" disabled class="w-full bg-white/5 border border-white/10 rounded-xl p-3 text-sm text-white/50 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block opacity-50">Jabatan</label>
                                    <input type="text" value="{{ auth()->user()->jabatan }}" disabled class="w-full bg-white/5 border border-white/10 rounded-xl p-3 text-sm text-white/50 cursor-not-allowed">
                                </div>
                            </div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-500/20 transition-all active:scale-95">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bagian Kanan: Ubah Password -->
        <div class="lg:col-span-1">
            <div class="glass-strong p-8">
                <h3 class="text-lg font-bold mb-6 flex items-center">
                    <i class="fas fa-shield-alt mr-3 text-emerald-400"></i> Keamanan Akun
                </h3>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block">Password Saat Ini</label>
                            <input type="password" name="current_password" required class="w-full bg-blue-900/30 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                            <x-input-error :messages="$errors->get('current_password')" class="mt-1 text-[10px]" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block">Password Baru</label>
                            <input type="password" name="password" required class="w-full bg-blue-900/30 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                            <x-input-error :messages="$errors->get('password')" class="mt-1 text-[10px]" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-blue-200 mb-1 block">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" required class="w-full bg-blue-900/30 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 py-4 rounded-xl font-black text-xs uppercase tracking-widest transition-all mt-4">
                            Ganti Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('profile-preview');
            const file = input.files[0];

            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Tolong pilih file gambar (JPG, PNG, JPEG)');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
@endsection
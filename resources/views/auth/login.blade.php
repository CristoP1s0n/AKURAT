<x-guest-layout>
    <div class="glass-strong p-8 sm:p-10 text-white relative overflow-hidden">
        <!-- Dekorasi Cahaya (Opsional) -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <!-- Logo & Judul -->
            <div class="text-center mb-10">
                <img src="{{ asset('img/logo-manado.webp') }}" class="w-20 mx-auto mb-4 drop-shadow-2xl" alt="Logo">
                <h2 class="text-3xl font-black tracking-tighter uppercase italic">AKURAT</h2>
                <p class="text-blue-200 text-[10px] font-bold uppercase tracking-[0.2em] opacity-80">Dinas Kesehatan Kota Manado</p>
            </div>

            <!-- Status Session -->
            <x-auth-session-status class="mb-4 text-emerald-300 text-xs text-center font-bold" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Input NIP -->
                <div class="mb-6">
                    <label for="nip" class="text-[10px] font-black uppercase text-blue-200 tracking-widest mb-2 block">Nomor Induk Pegawai (NIP)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-blue-300">
                            <i class="fas fa-user text-xs"></i>
                        </span>
                        <input id="nip" type="text" name="nip" value="{{ old('nip') }}" required autofocus
                            class="w-full bg-blue-900/30 border border-white/20 rounded-xl py-3.5 pl-11 pr-4 text-sm text-white placeholder-blue-300/50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-inner" 
                            placeholder="Masukkan NIP Anda">
                    </div>
                    <x-input-error :messages="$errors->get('nip')" class="mt-2 text-[10px] text-red-300 font-bold" />
                </div>

                <!-- Input Password -->
                <div class="mb-8">
                    <label for="password" class="text-[10px] font-black uppercase text-blue-200 tracking-widest mb-2 block">Kata Sandi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-blue-300">
                            <i class="fas fa-lock text-xs"></i>
                        </span>
                        <input id="password" type="password" name="password" required
                            class="w-full bg-blue-900/30 border border-white/20 rounded-xl py-3.5 pl-11 pr-4 text-sm text-white placeholder-blue-300/50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-inner" 
                            placeholder="••••••••">
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] text-red-300 font-bold" />
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 hover:from-blue-500 hover:to-blue-300 py-4 rounded-xl font-black text-xs text-white uppercase tracking-widest shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center justify-center">
                    Masuk ke Sistem <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
                </button>

                <!-- Info Bantuan & Credit Eksternal -->
                <div class="mt-8 text-center border-t border-white/10 pt-6">
                    <!-- Tagline Dinas -->
                    <p class="text-[9px] text-blue-300 italic opacity-60 mb-4 uppercase tracking-wider">
                        "Kinerja Terukur, Pelayanan Kesehatan Masyarakat Unggul"
                    </p>

                    <!-- Baris Credit -->
                    <div class="flex flex-col items-center gap-2">
                        <p class="text-[8px] text-white/30 uppercase font-medium tracking-widest">
                            &copy; 2026 Dinas Kesehatan Kota Manado
                        </p>
                        
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[9px] text-blue-200 opacity-50 uppercase font-bold">Developed by</span>
                            <a href="https://www.linkedin.com/in/acintya-sudarsono" target="_blank" 
                            class="flex items-center gap-1.5 px-3 py-1 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full transition-all group">
                                <span class="text-[10px] font-black text-blue-100 group-hover:text-white uppercase tracking-tighter">
                                    Acintya Edria Sudarsono
                                </span>
                                <i class="fab fa-linkedin text-[10px] text-[#0077B5] group-hover:text-white transition-colors"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
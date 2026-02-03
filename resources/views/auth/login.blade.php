<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-[#e52d27] to-[#b31217]">
        
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white/15 backdrop-blur-xl border border-white/20 shadow-2xl rounded-2xl overflow-hidden">
            
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-white tracking-tight">AKURAT - Aplikasi Kinerja Terukur</h2>
                <p class="text-white/60 text-sm">Silakan login dengan NIP Anda</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <label for="nip" class="block font-medium text-sm text-white/80">NIP</label>
                    <input id="nip" type="text" name="nip" value="{{ old('nip') }}" 
                        class="block mt-1 w-full bg-white/10 border border-white/20 rounded-lg py-2.5 px-4 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 transition" 
                        required autofocus placeholder="Masukkan NIP">
                    <x-input-error :messages="$errors->get('nip')" class="mt-2 text-red-200" />
                </div>

                <div class="mt-6">
                    <label for="password" class="block font-medium text-sm text-white/80">Password</label>
                    <input id="password" type="password" name="password" 
                        class="block mt-1 w-full bg-white/10 border border-white/20 rounded-lg py-2.5 px-4 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 transition" 
                        required placeholder="••••••••">
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-200" />
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-white text-red-700 hover:bg-red-50 py-3 rounded-xl font-bold uppercase text-xs tracking-widest shadow-lg transition-all active:scale-95">
                        {{ __('Log in') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
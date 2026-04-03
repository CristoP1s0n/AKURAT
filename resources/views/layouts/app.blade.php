<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AKURAT - Aplikasi Kinerja Terukur') }}</title>

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%);
                background-attachment: fixed;
                min-height: 100vh;
                color: white;
            }

            .glass {
                background: rgba(255, 255, 255, 0.12);
                backdrop-filter: blur(25px);
                -webkit-backdrop-filter: blur(25px);
                border: 1.5px solid rgba(255, 255, 255, 0.3);
                border-radius: 16px;
                box-shadow: 
                    0 8px 32px rgba(0, 0, 0, 0.2),
                    inset 0 1px 0 rgba(255, 255, 255, 0.4);
            }

            .glass-strong {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(30px);
                -webkit-backdrop-filter: blur(30px);
                border: 2px solid rgba(255, 255, 255, 0.35);
                border-radius: 18px;
                box-shadow: 
                    0 10px 40px rgba(0, 0, 0, 0.25),
                    inset 0 2px 0 rgba(255, 255, 255, 0.5),
                    inset 0 -2px 0 rgba(0, 0, 0, 0.1);
                position: relative;
                overflow: hidden;
            }

            aside {
                background: linear-gradient(180deg, #1e3a8a 0%, #0f172a 100%) !important;
                box-shadow: 4px 0 20px rgba(0,0,0,0.3);
            }

            .sidebar-item-active {
                background: linear-gradient(90deg, rgba(59, 130, 246, 0.4) 0%, rgba(96, 165, 250, 0.6) 100%);
                border-radius: 0 25px 25px 0;
                color: white !important;
                border-left: 4px solid #60a5fa;
                box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
            }

            .sidebar-item-active i {
                color: #93c5fd;
            }

            .sidebar-item:not(.sidebar-item-active):hover {
                background: rgba(59, 130, 246, 0.15);
                border-radius: 0 25px 25px 0;
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1.5px solid rgba(147, 197, 253, 0.25);
                border-radius: 14px;
                box-shadow: 
                    0 4px 20px rgba(0, 0, 0, 0.15),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }

            .btn-primary {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
            }

            /* Shimmer effect untuk glass cards */
            .glass-strong::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s;
            }
            
            .glass-strong:hover::before {
                left: 100%;
            }
        </style>
    </head>

    <body class="overflow-x-hidden antialiased">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 fixed h-full border-r border-blue-400/10">
            <div class="p-6 text-center border-b border-blue-400/10">
                <img src="{{ asset('img/logo-manado.webp') }}" class="w-16 mx-auto mb-3" alt="Logo">
                <h2 class="font-bold text-[11px] uppercase tracking-widest text-blue-100 leading-tight">
                    Dinas Kesehatan<br>Kota Manado
                </h2>
            </div>

            <!-- Profil di sidebar -->
            <div class="mt-6 flex items-center p-4 mx-4 glass-card mb-6">
                @php
                    // Logika untuk menampilkan foto asli atau inisial jika foto kosong
                    $userAvatar = auth()->user()->avatar 
                        ? asset('storage/' . auth()->user()->avatar) 
                        : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->nama ?? 'User') . '&background=3b82f6&color=fff';
                @endphp

                <img src="{{ $userAvatar }}" 
                    class="w-11 h-11 rounded-full border-2 border-blue-300/50 object-cover shadow-lg">
                
                <div class="ml-3 overflow-hidden flex-1">
                    <p class="text-[11px] font-bold truncate text-blue-100">{{ auth()->user()->nama ?? 'User' }}</p>
                    <p class="text-[10px] text-blue-300">NIP {{ auth()->user()->nip ?? '-' }}</p>
                </div>
            </div>

            <nav class="space-y-1 pr-4">
                <a href="{{ route('dashboard') }}" 
                   class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-th-large w-5"></i> <span class="ml-3">Dashboard</span>
                </a>
                <!-- PENILAIAN BAWAHAN: Hanya untuk Atasan -->
                @if(in_array(auth()->user()->role, ['kadis', 'kabag', 'kasie']))
                <a href="{{ route('penilaian.index') }}" 
                class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('penilaian.*') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-file-signature w-5"></i> <span class="ml-3">Penilaian & Kriteria</span>
                </a>
                @endif

                <!-- UNGGAH BERKAS SAYA: Untuk Staff & Semua Pegawai -->
                @if(auth()->user()->role !== 'kadis')
                <a href="{{ route('kinerja.index') }}" 
                class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('kinerja.index') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-cloud-upload-alt w-5"></i> <span class="ml-3">Unggah Berkas</span>
                </a>
                @endif

                <!-- Menu khusus kadis -->
                @if(auth()->user()->role == 'kadis')
                <a href="{{ route('pegawai.index') }}" 
                class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('pegawai.*') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-users w-5"></i> <span class="ml-3">Data Pegawai</span>
                </a>
                @endif

                <!-- UNIT KERJA: Semua Role (Transparansi Struktur) -->
                <a href="{{ route('unit-kerja.index') }}" 
                class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('unit-kerja.*') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-sitemap w-5"></i> <span class="ml-3">Unit Kerja</span>
                </a>

                <!-- LAPORAN KINERJA: Semua Role (Melihat Rekap Tahunan) -->
                <a href="{{ route('laporan.index') }}" 
                class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('laporan.*') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                    <i class="fas fa-chart-line w-5"></i> <span class="ml-3">Laporan Kinerja</span>
                </a>

                <a href="{{ route('profile.edit') }}" 
                    class="sidebar-item flex items-center px-6 py-3 text-xs {{ request()->routeIs('profile.*') ? 'sidebar-item-active' : 'text-blue-200 hover:text-white' }}">
                        <i class="fas fa-user-cog w-5"></i> <span class="ml-3">Profil Saya</span>
                    </a>
            </nav>
        </aside>


        <main class="flex-1 ml-64 min-h-screen relative">
            <!-- Header -->
            <header class="sticky top-0 z-40 p-4 flex justify-between items-center bg-blue-900/40 backdrop-blur-md border-b border-blue-400/10">
                <!-- Form Search Global -->
                <form action="{{ route('penilaian.index') }}" method="GET" class="relative w-1/3">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-300">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-blue-900/30 border border-blue-400/20 rounded-lg py-2 pl-9 pr-4 text-xs text-white placeholder-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-400/50 transition-all" 
                        placeholder="Cari Nama atau NIP Pegawai...">
                </form>

                <!-- Right Header -->
                <div class="flex items-center space-x-6">
                    <!-- TOMBOL NOTIFIKASI (Bell) -->
                    <div class="relative">
                        <button onclick="toggleNotifDropdown()" id="btnNotif" class="relative p-2 text-blue-200 hover:text-white transition">
                            <i class="fas fa-bell text-lg"></i>
                            @if($totalNotif > 0)
                                <span class="absolute top-0 right-0 bg-red-500 text-[8px] font-black px-1.5 py-0.5 rounded-full border border-blue-900 animate-pulse">
                                    {{ $totalNotif }}
                                </span>
                            @endif
                        </button>

                        <!-- Dropdown Notifikasi -->
                        <div id="dropdownNotifMenu" class="absolute right-0 mt-3 w-80 glass-strong hidden z-[100] shadow-2xl border border-white/20 overflow-hidden" style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
                            <div class="p-4 border-b border-white/10 bg-white/5">
                                <h4 class="text-xs font-black uppercase tracking-widest text-blue-100">Pemberitahuan</h4>
                                <p class="text-[10px] text-blue-300 opacity-70">Ada {{ $totalNotif }} berkas menunggu penilaian</p>
                            </div>
                            
                            <div class="max-h-80 overflow-y-auto custom-scrollbar">
                                @forelse($notifikasiBerkas as $notif)
                                    <a href="{{ route('penilaian.detail', $notif->user->id) }}" class="block p-4 border-b border-white/5 hover:bg-white/10 transition">
                                        <div class="flex items-center gap-3">
                                            {{-- LOGIKA AVATAR STAFF --}}
                                            @php
                                                $avatarStaff = $notif->user->avatar 
                                                    ? asset('storage/' . $notif->user->avatar) 
                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($notif->user->nama) . '&background=0ea5e9&color=fff';
                                            @endphp

                <img src="{{ $avatarStaff }}" class="w-8 h-8 rounded-lg shadow-lg object-cover">
                                            <div class="flex-1">
                                                <p class="text-xs font-bold text-white mb-0.5">{{ $notif->user->nama }}</p>
                                                <p class="text-[9px] text-blue-300 italic">Mengunggah berkas baru untuk triwulan ini</p>
                                                <p class="text-[8px] text-white/40 mt-1 uppercase">{{ $notif->created_at->diffForHumans() }}</p>
                                            </div>
                                            <i class="fas fa-chevron-right text-[10px] text-white/20"></i>
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-8 text-center">
                                        <i class="fas fa-check-circle text-emerald-400/20 text-3xl mb-3"></i>
                                        <p class="text-xs text-white/40">Semua berkas sudah dinilai!</p>
                                    </div>
                                @endforelse
                            </div>

                            @if($totalNotif > 0)
                                <a href="{{ route('penilaian.index') }}" class="block text-center py-3 text-[9px] font-black text-blue-400 hover:text-white bg-black/20 border-t border-white/10 transition">
                                    LIHAT SEMUA DAFTAR PENILAIAN
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- DROP-DOWN PEMILIHAN PERIODE -->
                    <div class="relative">
                        @php
                            $defaultTriwulan = ceil(date('n') / 3);
                            $currentPeriode = session('periode_pilihan', $defaultTriwulan);
                        @endphp
                        
                        <!-- Tombol Pemicu (Sekarang menggunakan onclick) -->
                        <button onclick="togglePeriodeDropdown()" id="btnPeriode" class="flex items-center space-x-2 bg-blue-900/40 hover:bg-blue-800/60 px-4 py-2 rounded-lg border border-blue-400/20 transition text-white">
                            <i class="fas fa-calendar-alt text-blue-300 text-xs"></i>
                            <span class="text-[11px] font-bold uppercase tracking-widest">
                                Periode: T{{ $currentPeriode }}
                                @if($currentPeriode != $defaultTriwulan)
                                    <span class="text-[8px] text-yellow-400 ml-1">(Manual)</span>
                                @endif
                            </span>
                            <i class="fas fa-chevron-down text-[8px] text-blue-300 ml-2"></i>
                        </button>

                        <!-- Dropdown Menu (Class 'hidden' dikontrol JS) -->
                        <div id="dropdownPerioseMenu" class="absolute right-0 mt-2 w-56 hidden z-[100] shadow-2xl border border-white/20 rounded-xl overflow-hidden"
     style="background: #111827; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                            <div class="p-3 border-b border-white/10 text-center">
                                <p class="text-[9px] font-black text-blue-200 uppercase tracking-tighter">Pilih Periode Tinjauan</p>
                            </div>
                            
                            <div class="py-1">
                                @foreach([1,2,3,4] as $t)
                                <button onclick="confirmPeriodeChange('{{ $t }}')" 
                                    class="w-full text-left px-4 py-3 text-xs transition flex justify-between items-center {{ $currentPeriode == $t ? 'bg-blue-600 text-white font-bold' : 'text-blue-100 hover:bg-white/10' }}">
                                    <span>Triwulan {{ $t }}</span>
                                    @if($t == $defaultTriwulan) 
                                        <span class="text-[7px] bg-white/20 px-1.5 py-0.5 rounded text-white">DEFAULT</span> 
                                    @endif
                                </button>
                                @endforeach
                            </div>
                            @if($currentPeriode != $defaultTriwulan)
                            <a href="{{ route('periode.reset') }}" class="block text-center py-3 text-[9px] font-black text-yellow-400 hover:text-white border-t border-white/10 bg-black/20">
                                <i class="fas fa-sync-alt mr-1"></i> KEMBALI KE DEFAULT SISTEM
                            </a>
                            @endif
                        </div>

                        <!-- Form Tersembunyi untuk Proses Submit -->
                        <form id="formSetPeriode" action="{{ route('periode.set') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="triwulan" id="input_triwulan_hidden">
                        </form>
                    </div>
    

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" 
                                class="bg-red-500/20 hover:bg-red-500/40 px-4 py-2 rounded-lg text-[11px] font-bold flex items-center border border-red-400/20 transition text-red-100 uppercase tracking-tighter">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Alert Message -->
            <div class="px-8 mt-2">
                @if(session('success'))
                    <div class="glass-card bg-green-500/20 border-green-400/50 p-3 text-xs mb-4 text-green-100">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="glass-card bg-red-500/20 border-red-400/50 p-3 text-xs mb-4 text-red-100">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="p-8 pt-6">
                @if(isset($slot)) {{ $slot }} @endif
                <div class="max-w-full">
                    @yield('content')
                </div>
            </div>

            <!-- FOOTER CREDIT -->
            <footer class="p-8 pt-0">
                <div class="glass-card p-4 text-center border border-white/10 shadow-lg">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] uppercase tracking-widest font-bold">
                        
                        <!-- Sisi Kiri: Copyright -->
                        <div class="text-blue-200 opacity-60">
                            &copy; {{ date('Y') }} <span class="text-white">AKURAT</span> - DINAS KESEHATAN KOTA MANADO
                        </div>

                        <!-- Sisi Tengah: Status Sistem (Opsional) -->
                        <div class="hidden lg:block text-blue-300 italic">
                            "Kinerja Terukur, Pelayanan Unggul"
                        </div>

                        <!-- Sisi Kanan: Credit & LinkedIn -->
                        <div class="flex items-center gap-4">
                            <span class="text-blue-200 opacity-80">Designed & Developed by</span>
                            <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-full border border-white/10 hover:bg-white/10 transition-all group">
                                <a href="https://www.linkedin.com/in/acintya-sudarsono" target="_blank" class="text-blue-100 hover:text-white transition-colors duration-300 flex items-center gap-2">
                                    <span>Acintya Edria Sudarsono</span>
                                    <i class="fab fa-linkedin text-sm text-[#0077B5] group-hover:text-white group-hover:scale-110 transition-all"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </footer>
        </main>
    </div>

    <script>
        function togglePeriodeDropdown() {
            const menu = document.getElementById('dropdownPerioseMenu');
            menu.classList.toggle('hidden');
        }

        function confirmPeriodeChange(triwulan) {
            const msg = "PERINGATAN!\n\n" +
                        "Anda akan mengubah tinjauan data ke Triwulan " + triwulan + ".\n" +
                        "Halaman akan memuat data kinerja pada periode tersebut saja.\n\n" +
                        "Lanjutkan?";
            
            if (confirm(msg)) {
                document.getElementById('input_triwulan_hidden').value = triwulan;
                document.getElementById('formSetPeriode').submit();
            }
        }

        // Menutup dropdown jika klik di tempat lain
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('dropdownPerioseMenu');
            const btn = document.getElementById('btnPeriode');
            
            if (!btn.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        function toggleNotifDropdown() {
            const menu = document.getElementById('dropdownNotifMenu');
            // Tutup dropdown periode jika sedang terbuka
            document.getElementById('dropdownPerioseMenu').classList.add('hidden');
            menu.classList.toggle('hidden');
        }

        // Tambahkan logika klik di luar untuk menutup notif
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('dropdownNotifMenu');
            const btn = document.getElementById('btnNotif');
            
            if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
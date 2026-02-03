<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AKURAT - Aplikasi Kinerja Terukur') }}</title>
e

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #e52d27 0%, #b31217 100%);
                background-attachment: fixed;
                min-height: 100vh;
                color: white;
            }

            .glass {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 15px;
            }

            .sidebar-item-active {
                background: rgba(239, 68, 68, 0.8);
                border-radius: 0 25px 25px 0;
                color: white !important;
            }
        </style>
    </head>

    <body class="overflow-x-hidden antialiased">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 fixed h-full bg-black/20 backdrop-blur-lg border-r border-white/10">
            <div class="p-6 text-center">
                <!-- logo di public/img/logo-manado.png -->
                <img src="{{ asset('img/logo-manado.png') }}" class="w-14 mx-auto mb-3" alt="Logo">
                <h2 class="font-bold text-[10px] uppercase tracking-widest text-white/80 leading-tight">Dinas Kesehatan<br>Kota Manado
                </h2>
            </div>

            <!-- Profil di sidebar -->
            <div class="mt-4 flex items-center p-3 mx-4 glass mb-6">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}" class="w-10 h-10 rounded-full border border-white/30">
                <div class="ml-3 overflow-hidden">
                    <p class="text-[11px] font-bold truncate">{{ auth()->user()->nama }}</p>
                    <p class="text-[11px] text-white/70">NIP. {{ auth()->user()->nip ?? '-' }}</p>
                </div>
            </div>

            <nav class="space-y-1 pr-4">
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-xs {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : 'text-white/60 hover:text-white' }}">
                    <i class="fas fa-th-large w-5"></i> <span class="ml-3">Dashboard</span>
                </a>
                <a href="{{ route('penilaian.index') }}" class="flex items-center px-6 py-3 text-xs {{ request()->routeIs('penilaian.*') ? 'sidebar-item-active' : 'text-white/60 hover:text-white' }}">
                    <i class="fas fa-file-signature w-5"></i> <span class="ml-3">Penilaian & Kriteria</span>
                </a>

                <!-- Menu khusus kadis -->
                @if(auth()->user()->role == ('kadis'))
                <a href="#" class="flex items-center px-6 py-3 text-xs text-white/60 hover:text-white">
                    <i class="fas fa-users w-5"></i> <span class="ml-3">Unit Kerja</span>
                </a>
                @endif

                <a href="#" class="flex items-center px-6 py-3 text-xs text-white/60 hover:text-white>
                    <i class="fas fa-chart-bar w-5"></i> <span class="ml-3">Laporan Kinerja</span>
                </a>
                </nav>
        </aside>


        <main class="flex-1 ml-64 min-h-screen">
            <!-- Header -->
            <header class="p-4 flex justify-between items-center sticky top-0 z-50 bg-transparent">
                <div class="relative w-1/3">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-white/50">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" class="w-full bg-white/10 border border-white/20 rounded-lg py-1.5 pl-9 pr-4 text-xs text-white placeholder-white/50 focus:outline-none focus:ring-1 focus:ring-white/30" placeholder="Cari Nama, NIP...">
                </div>

                <!-- Right Header -->
                <div class="flex items-center space-x-6">
                    <i class="fas fa-bell text-white/50 hover:text-white cursor-pointer transition"></i>

                    <div class="flex items-center space-x-2 bg-white/10 px-3 py-1.5 rounded-lg border border-white/20">
                        <i class="fas fa-calendar-alt text-white/40 text-xs"></i>
                        <span class="text-[11px] font-semibold">Periode: T{{DB::table('settings')->where('key', 'triwulan_aktif')->value('value')}}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-black/20 hover:bg-red-600/50 px-4 py-1.5 rounded-lg text-[11px] font-bold flex items-center border border-white/10 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Alert Message -->
            <div class="px-8 mt-2">
                @if(session('success'))
                    <div class="glass bg-green-500/20 border-green-500/50 p-3 text-xs mb-4">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="glass bg-red-500/20 border-red-500/50 p-3 text-xs mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="p-8 pt-2">
                @if(isset($slot)) {{ $slot }} @endif
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
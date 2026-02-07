@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-white">Dashboard <span class="text-blue-300 opacity-50">T{{ $triwulan }} - {{ $tahun }}</span></h1>
    <div class="glass px-4 py-2 rounded-xl text-[10px] font-bold text-blue-200 uppercase tracking-widest">
        Update Terakhir: {{ now()->format('d M Y H:i') }}
    </div>
</div>

<!-- Statistik Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <div class="glass-strong p-6 text-center hover:scale-105 transition-transform">
        <p class="text-blue-100 text-xs mb-2 uppercase tracking-wider font-semibold">Total Pegawai</p>
        <h2 class="text-5xl font-bold text-white mb-1">{{ $totalPegawai }}</h2>
        <div class="w-12 h-1 bg-blue-400/50 mx-auto mt-3 rounded-full"></div>
    </div>
    <div class="glass-strong p-6 text-center hover:scale-105 transition-transform">
        <p class="text-blue-100 text-xs mb-2 uppercase tracking-wider font-semibold">Progress Unggah Berkas</p>
        <h2 class="text-5xl font-bold text-white mb-1">{{ $progressUpload }}%</h2>
        <div class="w-12 h-1 bg-blue-400/50 mx-auto mt-3 rounded-full"></div>
    </div>
    <div class="glass-strong p-6 text-center hover:scale-105 transition-transform">
        <p class="text-blue-100 text-xs mb-2 uppercase tracking-wider font-semibold">Rata-rata Skor Kinerja</p>
        <h2 class="text-5xl font-bold text-white mb-1">{{ $rataRataDinas }}</h2>
        <div class="w-12 h-1 bg-blue-400/50 mx-auto mt-3 rounded-full"></div>
    </div>
    <div class="glass-strong p-6 text-center border-l-4 border-red-400 hover:scale-105 transition-transform">
        <p class="text-blue-100 text-xs mb-2 uppercase tracking-wider font-semibold">Berkas Belum Dinilai</p>
        <h2 class="text-5xl font-bold text-red-200 bg-red-600/30 rounded-lg inline-block px-5 py-2 mb-1">
            {{ $berkasBelumDinilai }}
        </h2>
        <div class="w-12 h-1 bg-red-400/50 mx-auto mt-3 rounded-full"></div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
    <!-- Bar Chart -->
    <div class="glass-strong p-8 flex flex-col">
        <h3 class="font-bold text-white text-lg mb-6 items-center">
            <i class="fas fa-chart-bar mr-3 text-blue-400"></i> Performa rata-rata per Bidang (%)
        </h3>
        <div class="w-full h-[300px]">
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <!-- Donut Chart -->
    <div class="glass-strong p-8 flex flex-col">
        <h3 class="font-bold text-white text-lg mb-6 flex items-center">
           <i class="fas fa-chart-pie mr-3 text-blue-400"></i>Komposisi Predikat
        </h3>
        <div class="flex flex-col md:flex-row items-center justify-center gap-8">
            <div class="w-full md:w-1/2 h-[250px] flex justify-center">
                <canvas id="donutChart"></canvas>
            </div>
            <div class="flex-1 space-y-4 w-full">
                @foreach($predikats as $label => $count)
                    <div class="flex items-center text-xs">
                        <span class="w-3 h-3 rounded-full mr-2 {{ $loop->index == 0 ? 'bg-blue-400' : ($loop->index == 1 ? 'bg-blue-300' : ($loop->index == 2 ? 'bg-blue-200' : 'bg-white')) }}"></span>
                        <span class="text-blue-100">{{ $label }}: <span class="font-bold text-white">{{ $count }} Org</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Ranking Tables -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Top 5 -->
    <div class="glass-card overflow-hidden">
        <div class="p-5 border-b border-white/20 bg-blue-500/10"><h3 class="font-bold text-white text-center">5 Performa Teratas</h3></div>
        <table class="w-full text-sm text-left">
            <tbody class="divide-y divide-white/5">
                @foreach($top5 as $p)
                <tr class="hover:bg-white/5 transition">
                    <td class="px-6 py-4 font-bold text-blue-200">#{{ $loop->iteration }}</td>
                    <td class="px-6 py-4 text-white">{{ $p['nama'] }}</td>
                    <td class="px-6 py-4 text-green-400 font-black text-right">{{ $p['skor'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Bottom 5 -->
    <div class="glass-card overflow-hidden">
        <div class="p-5 border-b border-white/20 bg-red-500/10 text-center"><h3 class="font-bold text-white">5 Performa Terendah</h3></div>
        <table class="w-full text-sm text-left">
            <tbody class="divide-y divide-white/5">
                @foreach($bottom5 as $p)
                <tr class="hover:bg-white/5 transition">
                    <td class="px-6 py-4 font-bold text-red-300">#{{ $loop->iteration }}</td>
                    <td class="px-6 py-4 text-white">{{ $p['nama'] }}</td>
                    <td class="px-6 py-4 text-red-400 font-black text-right">{{ $p['skor'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Ambil data dari PHP
    const bidangLabels = {!! json_encode(collect($bidangAverages)->pluck('label')) !!};
    const bidangValues = {!! json_encode(collect($bidangAverages)->pluck('value')) !!};
    const predikatData = {!! json_encode(array_values($predikats)) !!};
    // Debugging (Cek di Console Browser)
    console.log("Labels Bidang:", bidangLabels);
    console.log("Data Bidang:", bidangValues);

    // Common Options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false, // Wajib agar mengikuti tinggi div parent
        plugins: { legend: { display: false } }
    };

    // Render Bar Chart
    const barCtx = document.getElementById('barChart');
    if (document.getElementById('barChart')) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: bidangLabels,
                datasets: [{
                    data: bidangValues,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 8,
                    barThickness: 25
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: { 
                        beginAtZero: true, max: 100, 
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#93c5fd' }
                    },
                    x: { ticks: { color: '#93c5fd', font: { size: 9 }  } }
                }
            }
        });
    }

    // Render Donut Chart
    const donutCtx = document.getElementById('donutChart');
    if (donutCtx) {
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($predikats)) !!},
                datasets: [{
                    data: predikatData,
                    backgroundColor: ['#60a5fa', '#93c5fd', '#bfdbfe', 'rgba(255,255,255,0.1)'],
                    borderWidth: 0
                }]
            },
            options: {
                ...commonOptions,
                cutout: '75%'
            }
        });
    }
</script>
@endpush
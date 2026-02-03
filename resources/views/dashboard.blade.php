@extends('layouts.app')

@section('content')
<h1 class="text-3xl font-bold mb-8">Dashboard</h1>

<!-- Statistik Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 text-white">
    <div class="glass p-6 text-center">
        <p class="text-white/60 text-xs mb-2 uppercase tracking-wider">Total Pegawai</p>
        <h2 class="text-4xl font-bold">{{ \App\Models\User::count() }}</h2>
    </div>
    <div class="glass p-6 text-center">
        <p class="text-white/60 text-xs mb-2 uppercase tracking-wider">Progress Unggah Berkas</p>
        <h2 class="text-4xl font-bold">85%</h2>
    </div>
    <div class="glass p-6 text-center">
        <p class="text-white/60 text-xs mb-2 uppercase tracking-wider">Rata-rata Skor Kinerja</p>
        <h2 class="text-4xl font-bold">90</h2>
    </div>
    <div class="glass p-6 text-center border-l-4 border-red-500">
        <p class="text-white/60 text-xs mb-2 uppercase tracking-wider">Berkas Belum Dinilai</p>
        <h2 class="text-4xl font-bold text-red-200 bg-red-600/40 rounded-lg inline-block px-4 py-1">
            {{ \App\Models\BerkasKinerja::where('status_penilaian', 'belum')->count() }}
        </h2>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
    <div class="glass p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold">Performa rata-rata per Bidang</h3>
            <button class="text-[10px] bg-white text-red-600 px-3 py-1 rounded shadow-lg uppercase font-bold">View Report</button>
        </div>
        <canvas id="barChart" height="200"></canvas>
    </div>
    <div class="glass p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold">Komposisi Predikat</h3>
            <button class="text-[10px] bg-white text-red-600 px-3 py-1 rounded shadow-lg uppercase font-bold">View Report</button>
        </div>
        <canvas id="donutChart" height="200"></canvas>
    </div>
</div>

<!-- Tables Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="glass overflow-hidden">
        <div class="p-4 border-b border-white/10 bg-white/5">
            <h3 class="text-center font-bold">5 Performa Teratas</h3>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-white/10 text-xs uppercase text-white/50">
                <tr>
                    <th class="px-4 py-2">No</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">NIP</th>
                    <th class="px-4 py-2">Skor Kinerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @for($i=1; $i<=5; $i++)
                <tr class="hover:bg-white/5 transition">
                    <td class="px-4 py-3">{{ $i }}</td>
                    <td class="px-4 py-3 font-medium">Pegawai Terbaik {{ $i }}</td>
                    <td class="px-4 py-3 text-white/60">1980010100{{ $i }}</td>
                    <td class="px-4 py-3 font-bold text-green-300">95.5</td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="glass overflow-hidden">
        <div class="p-4 border-b border-white/10 bg-white/5 text-center">
            <h3 class="font-bold">5 Performa Terendah</h3>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-white/10 text-xs uppercase text-white/50">
                <tr>
                    <th class="px-4 py-2">No</th>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">NIP</th>
                    <th class="px-4 py-2">Skor Kinerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @for($i=1; $i<=5; $i++)
                <tr class="hover:bg-white/5 transition">
                    <td class="px-4 py-3">{{ $i }}</td>
                    <td class="px-4 py-3 font-medium">Pegawai Kurang {{ $i }}</td>
                    <td class="px-4 py-3 text-white/60">1985010100{{ $i }}</td>
                    <td class="px-4 py-3 font-bold text-red-300">45.0</td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bar Chart
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: ['Bidang A', 'Bidang B', 'Bidang C'],
            datasets: [{
                label: 'Performa %',
                data: [65, 45, 60],
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } },
            plugins: { legend: { display: false } }
        }
    });

    // Donut Chart
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Sangat Baik', 'Baik', 'Buruk', 'Sangat Buruk'],
            datasets: [{
                data: [15, 38, 9, 30],
                backgroundColor: ['#b91c1c', '#f87171', '#fb923c', '#ffffff'],
                borderWidth: 0
            }]
        },
        options: {
            plugins: { legend: { position: 'right', labels: { color: '#fff' } } }
        }
    });
</script>
@endpush
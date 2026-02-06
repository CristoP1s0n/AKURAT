@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Struktur Organisasi</h1>
            <p class="text-blue-200 text-sm opacity-80 mt-1">Klik kotak untuk menyembunyikan/menampilkan bawahan</p>
        </div>
        <div class="text-xs glass p-2 font-bold text-blue-300">
            Daftar Unit & Staff Terdaftar
        </div>
    </div>

    <!-- Wrapper dengan Zoom Otomatis -->
    <div class="glass-strong p-4 overflow-hidden relative" style="min-height: 70vh;">
        <div id="chart_div" class="flex justify-center transform origin-top transition-transform duration-500 scale-[0.8] sm:scale-100">
            {{-- Diagram Muncul di Sini --}}
        </div>
    </div>
</div>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {packages:["orgchart"]});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Entity');
        data.addColumn('string', 'Parent');

        data.addRows([
            // 1. Render Unit Kerja (Bidang & Seksi)
            @foreach($units as $u)
                [{
                    v:'unit_{{ $u->id }}', 
                    f:'<div class="node-unit"><div class="font-black text-[10px] uppercase">{{ $u->nama_unit }}</div><div class="text-[7px] opacity-60">UNIT KERJA</div></div>'
                }, '{{ $u->parent_id ? "unit_".$u->parent_id : "" }}'],
            @endforeach

            // 2. Render Staff (Di bawah unit masing-masing)
            @foreach($users as $user)
                [{
                    v:'user_{{ $user->id }}', 
                    f:'<div class="node-staff"><div class="font-bold text-[9px]">{{ $user->nama }}</div><div class="text-[7px] text-blue-300">{{ $user->jabatan }}</div></div>'
                }, 'unit_{{ $user->unit_id }}'],
            @endforeach
        ]);

        var chart = new google.visualization.OrgChart(document.getElementById('chart_div'));
        
        // allowCollapse: true memungkinkan klik untuk zoom-in/out bawahan
        chart.draw(data, {
            'allowHtml':true, 
            'allowCollapse':true, 
            'size': 'small',
            'nodeClass': 'glass-node'
        });
    }
</script>

<style>
    /* Hilangkan Outline Default Google */
    .google-visualization-orgchart-node {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 2px !important;
    }

    /* Style Kotak Unit Kerja */
    .node-unit {
        background: rgba(30, 58, 138, 0.4) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        padding: 10px;
        min-width: 140px;
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    /* Style Kotak Staff (Lebih kecil/berbeda warna) */
    .node-staff {
        background: rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 6px;
        min-width: 120px;
        color: #dbeafe;
    }

    /* Garis Penghubung */
    .google-visualization-orgchart-lineleft,
    .google-visualization-orgchart-lineright,
    .google-visualization-orgchart-linebottom,
    .google-visualization-orgchart-linetop {
        border-color: rgba(147, 197, 253, 0.2) !important;
    }

    /* Responsivitas: Mengecilkan skala jika di HP */
    @media (max-width: 768px) {
        #chart_div { transform: scale(0.6); }
    }
</style>
@endsection
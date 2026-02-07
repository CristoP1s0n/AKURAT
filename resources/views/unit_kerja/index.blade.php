@extends('layouts.app')

@section('content')
<div class="container-fluid text-white">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Struktur Organisasi</h1>
            <p class="text-blue-200 text-sm opacity-80 mt-1">Klik kotak untuk memperluas (Expand) struktur di bawahnya</p>
        </div>
        <div class="glass px-4 py-2 rounded-xl border border-white/10">
            <span class="text-[10px] font-black uppercase text-blue-300 tracking-widest">Mode Interaktif</span>
        </div>
    </div>

    <!-- Wrapper Diagram -->
    <div class="glass-strong p-4 overflow-hidden relative" style="min-height: 80vh;">
        <!-- Area Scroll -->
        <div class="overflow-auto max-h-[75vh]" id="scroll-wrapper">
            <div id="chart_div" class="flex justify-center p-10 transition-all duration-500 transform origin-top scale-90">
                {{-- Diagram Muncul di Sini --}}
            </div>
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
        data.addColumn('string', 'ToolTip');

        // Array untuk menyimpan data mentah agar bisa di-loop untuk collapse
        var rawData = [];

        // 1. Masukkan Unit Kerja
        @foreach($units as $u)
            rawData.push({
                id: 'unit_{{ $u->id }}',
                parent: '{{ $u->parent_id ? "unit_".$u->parent_id : "" }}',
                level: '{{ $u->level }}',
                html: '<div class="node-unit {{ $u->level == "seksi" ? "is-seksi" : "is-top" }}">' +
                      '<div class="font-black text-[10px] uppercase">{{ $u->nama_unit }}</div>' +
                      '<div class="text-[7px] opacity-60 mt-1 uppercase">{{ $u->level }}</div>' +
                      '</div>'
            });
        @endforeach

        // 2. Masukkan Staff
        @foreach($users as $user)
            rawData.push({
                id: 'user_{{ $user->id }}',
                parent: 'unit_{{ $user->unit_id }}',
                level: 'staff',
                html: '<div class="node-staff">' +
                      '<div class="font-bold text-[9px]">{{ $user->nama }}</div>' +
                      '<div class="text-[7px] text-blue-300">{{ $user->jabatan }}</div>' +
                      '</div>'
            });
        @endforeach

        // Masukkan ke DataTable Google
        rawData.forEach(function(item) {
            data.addRow([{ v: item.id, f: item.html }, item.parent, '']);
        });

        var chart = new google.visualization.OrgChart(document.getElementById('chart_div'));

        // Pengaturan Awal
        chart.draw(data, {
            'allowHtml': true,
            'allowCollapse': true, // Mengaktifkan fitur klik untuk buka/tutup
            'size': 'medium',
            'nodeClass': 'glass-node'
        });

        /**
         * LOGIKA DEFAULT STATE:
         * Kita loop semua baris. Jika level-nya adalah 'seksi' atau 'staff',
         * kita panggil fungsi collapse pada PARENT-nya.
         */
        for (var i = 0; i < data.getNumberOfRows(); i++) {
            var nodeId = data.getValue(i, 0);
            
            // Cari data asli untuk cek level
            var originalData = rawData.find(r => r.id === nodeId);
            
            // Jika ini adalah Seksi atau Staff, tutup parent-nya agar mereka tersembunyi di awal
            if (originalData && (originalData.level === 'seksi' || originalData.level === 'staff')) {
                chart.collapse(i, true);
            }
        }
    }
</script>

<style>
    /* Reset Google Chart Nodes */
    .google-visualization-orgchart-node {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 4px !important;
        cursor: pointer;
    }

    /* Node Unit (Bidang/Sekretariat/Subbag) */
    .node-unit {
        background: rgba(30, 58, 138, 0.5) !important;
        backdrop-filter: blur(15px);
        border: 1.5px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 12px 15px;
        min-width: 170px;
        color: white;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }

    .node-unit.is-seksi {
        background: rgba(30, 58, 138, 0.3) !important;
        border-color: rgba(59, 130, 246, 0.3);
    }

    /* Node Staff */
    .node-staff {
        background: rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 8px;
        min-width: 140px;
        color: #dbeafe;
    }

    .node-unit:hover, .node-staff:hover {
        transform: translateY(-5px);
        border-color: rgba(255, 255, 255, 0.5);
        background: rgba(59, 130, 246, 0.4) !important;
    }

    /* Garis Penghubung */
    .google-visualization-orgchart-lineleft,
    .google-visualization-orgchart-lineright,
    .google-visualization-orgchart-linebottom,
    .google-visualization-orgchart-linetop {
        border-color: rgba(147, 197, 253, 0.3) !important;
        border-width: 2px !important;
    }

    /* Scrollbar Styling */
    #scroll-wrapper::-webkit-scrollbar { width: 8px; height: 8px; }
    #scroll-wrapper::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
</style>
@endsection
@extends('layouts.app')

@section('content')
<div class="text-white">
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight">Struktur Organisasi</h1>
        <p class="text-blue-200 text-sm opacity-80 mt-1">Klik kotak untuk Expand/Collapse (melihat bawahan).</p>
    </div>

    <!-- BOX WRAPPER: Menjaga diagram tetap di dalam kotak biru -->
    <div class="glass-strong p-4 sm:p-8 relative w-full overflow-hidden" style="height: 75vh;">
        
        <!-- AREA SCROLL INTERNAL: Hanya area ini yang boleh geser kanan-kiri -->
        <div class="w-full h-full overflow-auto custom-scrollbar" id="chart-container">
            <div id="chart_div" class="flex justify-center items-start origin-top transition-all duration-500">
                <!-- Google Chart Render -->
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

        var allNodes = [];

        // 1. Data Unit Kerja
        @foreach($units as $u)
            allNodes.push({
                id: 'unit_{{ $u->id }}',
                parent: '{{ $u->parent_id ? "unit_".$u->parent_id : "" }}',
                level: '{{ $u->level }}',
                html: '<div class="node-box {{ $u->level == "seksi" ? "node-seksi" : "node-bidang" }}">' +
                      '<div class="text-[10px] font-black uppercase text-white">{{ $u->nama_unit }}</div>' +
                      '<div class="text-[7px] text-blue-300 font-bold mt-1 uppercase">{{ $u->level }}</div>' +
                      '</div>'
            });
        @endforeach

        // 2. Data Staff
        @foreach($users as $user)
            @if($user->role == 'staff')
            allNodes.push({
                id: 'user_{{ $user->id }}',
                parent: 'unit_{{ $user->unit_id }}',
                level: 'staff',
                html: '<div class="node-box node-staff">' +
                      '<div class="text-[9px] font-bold text-white">{{ $user->nama }}</div>' +
                      '<div class="text-[7px] text-blue-100 opacity-70 italic">{{ $user->jabatan }}</div>' +
                      '</div>'
            });
            @endif
        @endforeach

        allNodes.forEach(function(n) {
            data.addRow([{ v: n.id, f: n.html }, n.parent, '']);
        });

        var chart = new google.visualization.OrgChart(document.getElementById('chart_div'));

        // Event Listener: Sesuaikan ukuran SETELAH render atau klik
        google.visualization.events.addListener(chart, 'ready', function() {
            fitToBox();
        });
        google.visualization.events.addListener(chart, 'select', function() {
            // Berikan sedikit jeda agar animasi collapse selesai baru dihitung ulang ukurannya
            setTimeout(fitToBox, 200); 
        });

        chart.draw(data, {
            'allowHtml': true,
            'allowCollapse': true,
            'size': 'small',
            'nodeClass': 'google-node-reset'
        });

        /**
         * DEFAULT STATE: Sembunyikan Seksi dan Staff
         */
        for (var i = 0; i < data.getNumberOfRows(); i++) {
            var id = data.getValue(i, 0);
            var info = allNodes.find(n => n.id === id);
            // Jika unit tingkat seksi atau individu staff, tutup induknya
            if (info && (info.level === 'seksi' || info.level === 'staff')) {
                chart.collapse(i, true);
            }
        }
    }

    // Fungsi sakti agar diagram tidak pernah memotong screen
    function fitToBox() {
        const container = document.getElementById('chart-container');
        const chartDiv = document.getElementById('chart_div');
        
        // Hitung rasio lebar
        const ratio = (container.offsetWidth - 40) / chartDiv.scrollWidth;
        
        // Jika diagram lebih lebar dari kotak induknya, kecilkan skalanya (Zoom Out)
        if (ratio < 1) {
            chartDiv.style.transform = `scale(${ratio})`;
        } else {
            chartDiv.style.transform = `scale(1)`;
        }
    }

    window.onresize = fitToBox;
</script>

<style>
    /* Reset Google Chart */
    .google-node-reset { border: none !important; background: transparent !important; padding: 4px !important; }
    .google-visualization-orgchart-table { border-collapse: separate !important; border-spacing: 15px 10px !important; }

    /* Kotak Glassmorphism */
    .node-box {
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 10px;
        min-width: 150px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .node-bidang { background: rgba(30, 58, 138, 0.7) !important; border-color: rgba(59, 130, 246, 0.5); }
    .node-seksi { background: rgba(30, 58, 138, 0.4) !important; border-color: rgba(59, 130, 246, 0.3); }
    .node-staff { background: rgba(255, 255, 255, 0.08) !important; border: 1px dashed rgba(255,255,255,0.2); }

    /* Garis Penghubung */
    .google-visualization-orgchart-lineleft, .google-visualization-orgchart-lineright, 
    .google-visualization-orgchart-linebottom, .google-visualization-orgchart-linetop {
        border-color: rgba(147, 197, 253, 0.3) !important;
    }

    /* Scrollbar Tipis untuk Area Diagram */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
</style>
@endsection
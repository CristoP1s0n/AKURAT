@extends('layouts.app')

@section('content')
<div class="text-white">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Struktur Organisasi</h1>
            <p class="text-blue-200 text-sm opacity-80 mt-1">Klik kotak pimpinan untuk melihat bawahan langsung (Seksi atau Staff)</p>
        </div>
        
        <div id="breadcrumb" class="glass-strong px-4 py-2 rounded-xl hidden">
            <div class="flex items-center gap-2 text-sm">
                <button onclick="resetView()" class="text-blue-300 hover:text-white transition font-black uppercase text-[10px]">
                    <i class="fas fa-home mr-1"></i> Utama
                </button>
                <span id="breadcrumb-path" class="flex items-center"></span>
            </div>
        </div>
    </div>

    <!-- BOX WRAPPER -->
    <div class="glass-strong p-4 relative w-full overflow-hidden flex flex-col items-center justify-center" style="height: 70vh; max-width: calc(100vw - 300px);">
        
        <!-- AREA DIAGRAM: Kita gunakan div ini sebagai jangkar ukuran -->
        <div class="w-full h-full flex justify-center items-start overflow-hidden pt-12" id="chart-area">
            <div id="chart_div" class="origin-top transition-transform duration-500 ease-in-out">
                <!-- Rendered Chart -->
            </div>
        </div>

    </div>
</div>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {packages:["orgchart"]});
    google.charts.setOnLoadCallback(initData);

    let allUnits = [];
    let allStaff = [];
    let chart = null;

    const unitsData = @json($units);
    const usersData = @json($users);

    function initData() {
        allUnits = [];
        allStaff = [];

        unitsData.forEach(u => {
            const manager = usersData.find(user => 
                user.unit_id === u.id && (user.role === 'kadis' || user.role === 'kabag' || user.role === 'kasie')
            );

            allUnits.push({
                slug: 'unit_' + u.id,
                parentSlug: u.parent_id ? 'unit_' + u.parent_id : null,
                name: u.nama_unit,
                level: u.level,
                managerName: manager ? manager.nama : 'Posisi Belum Terisi',
                managerJabatan: manager ? manager.jabatan : u.nama_unit,
                realId: u.id
            });
        });

        usersData.forEach(user => {
            if (user.role === 'staff') {
                allStaff.push({
                    slug: 'staff_' + user.id,
                    parentSlug: 'unit_' + user.unit_id,
                    name: user.nama,
                    jabatan: user.jabatan,
                    level: 'staff'
                });
            }
        });

        renderView('root');
    }

    function renderView(mode, targetSlug = null) {
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Entity');
        data.addColumn('string', 'Parent');
        data.addColumn('string', 'ToolTip');

        let nodesToRender = [];

        if (mode === 'root') {
            const roots = allUnits.filter(n => n.parentSlug === null);
            const childs = allUnits.filter(n => roots.some(r => r.slug === n.parentSlug));
            nodesToRender = [...roots, ...childs];
            document.getElementById('breadcrumb').classList.add('hidden');
        } else {
            const target = allUnits.find(n => n.slug === targetSlug);
            if (!target) return;

            nodesToRender.push({ ...target, parentSlug: '' });

            // LOGIKA PENCARIAN BAWAHAN:
            // 1. Cari Unit di bawahnya (Seksi)
            const subUnits = allUnits.filter(n => n.parentSlug === target.slug);
            // 2. Cari Staff langsung di unit ini
            const directStaff = allStaff.filter(s => s.parentSlug === target.slug);

            nodesToRender = [...nodesToRender, ...subUnits, ...directStaff];
            updateBreadcrumb(target);
        }

        nodesToRender.forEach(n => {
            data.addRow([{ v: n.slug, f: createNodeHTML(n) }, n.parentSlug || '', '']);
        });

        const container = document.getElementById('chart_div');
        chart = new google.visualization.OrgChart(container);
        
        google.visualization.events.addListener(chart, 'ready', fitToScreen);

        chart.draw(data, {
            'allowHtml': true,
            'allowCollapse': false,
            'size': 'medium',
            'nodeClass': 'google-node-reset'
        });
    }

    function createNodeHTML(node) {
        const isUnit = node.level !== 'staff';
        // Unit bisa diklik jika punya Seksi di bawahnya ATAU punya Staff langsung
        const hasChildren = allUnits.some(u => u.parentSlug === node.slug) || 
                            allStaff.some(s => s.parentSlug === node.slug);
        
        const canClick = isUnit && hasChildren;
        const clickAttr = canClick ? `onclick="renderView('drill', '${node.slug}')"` : '';

        if (!isUnit) {
            return `<div class="node-box node-staff animate-fade-in">
                        <div class="text-[9px] font-bold text-white leading-tight">${node.name}</div>
                        <div class="text-[7px] text-blue-200 opacity-70 mt-1 uppercase">${node.jabatan}</div>
                    </div>`;
        }

        return `<div class="node-box node-${node.level} ${canClick ? 'cursor-pointer' : ''}" ${clickAttr}>
                    <div class="text-[8px] text-blue-300 font-bold uppercase tracking-tighter opacity-70 mb-1">${node.name}</div>
                    <div class="text-[11px] font-black text-white leading-tight">${node.managerName}</div>
                    <div class="text-[7px] text-blue-100 mt-1 opacity-80 uppercase">${node.managerJabatan}</div>
                    ${canClick ? '<div class="mt-2 pt-1 border-t border-white/10 text-[7px] text-blue-300 font-bold uppercase tracking-widest"><i class="fas fa-search-plus mr-1"></i> Expand</div>' : ''}
                </div>`;
    }

    function fitToScreen() {
        const area = document.getElementById('chart-area');
        const chartDiv = document.getElementById('chart_div');
        
        // Reset dulu agar dapat ukuran aslinya
        chartDiv.style.transform = 'scale(1)';
        
        setTimeout(() => {
            const areaWidth = area.offsetWidth - 20; // Padding safety
            const chartWidth = chartDiv.scrollWidth;

            if (chartWidth > areaWidth) {
                const scale = areaWidth / chartWidth;
                chartDiv.style.transform = `scale(${scale})`;
            }
        }, 50);
    }

    function updateBreadcrumb(node) {
        const breadcrumb = document.getElementById('breadcrumb');
        const path = document.getElementById('breadcrumb-path');
        breadcrumb.classList.remove('hidden');
        path.innerHTML = `<span class="text-white flex items-center"><i class="fas fa-chevron-right mx-2 text-blue-400"></i> ${node.name}</span>`;
    }

    function resetView() { renderView('root'); }
    window.onresize = fitToScreen;
</script>

<style>
    /* Reset Mutlak Google Charts */
    .google-node-reset { border: none !important; background: transparent !important; padding: 4px !important; }
    .google-visualization-orgchart-table { border-collapse: separate !important; border-spacing: 20px 15px !important; width: auto !important; }

    /* Kotak Node Glassmorphism */
    .node-box {
        backdrop-filter: blur(15px);
        border: 1.5px solid rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        padding: 15px;
        min-width: 170px;
        max-width: 170px; /* Lock lebar */
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
    }

    .node-box:hover {
        transform: translateY(-4px);
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }

    .node-bidang, .node-bagian { background: rgba(30, 58, 138, 0.8) !important; border-color: rgba(59, 130, 246, 0.5); }
    .node-seksi { background: rgba(30, 58, 138, 0.5) !important; border-color: rgba(59, 130, 246, 0.3); }
    .node-staff { background: rgba(255, 255, 255, 0.05) !important; border: 1px dashed rgba(255,255,255,0.3); }

    /* Garis Penghubung */
    .google-visualization-orgchart-lineleft, .google-visualization-orgchart-lineright, 
    .google-visualization-orgchart-linebottom, .google-visualization-orgchart-linetop {
        border-color: rgba(147, 197, 253, 0.3) !important;
        border-width: 2px !important;
    }
</style>
@endsection
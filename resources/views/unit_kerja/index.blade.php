@extends('layouts.app')

@section('content')
<div class="text-white">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Struktur Organisasi</h1>
            <p class="text-blue-200 text-sm opacity-80 mt-1">Klik kotak pimpinan untuk melihat bawahan langsung (Seksi atau Staff)</p>
        </div>

        <div class="flex items-center gap-3">
            <div id="breadcrumb" class="glass-card px-4 py-2 rounded-xl hidden">
                <div class="flex items-center gap-2 text-sm">
                    <button onclick="resetView()" class="text-blue-300 hover:text-white transition font-black uppercase text-[10px]">
                        <i class="fas fa-home mr-1"></i> Utama
                    </button>
                    <span id="breadcrumb-path" class="flex items-center"></span>
                </div>
            </div>

            @if(auth()->user()->role === 'kadis')
            <button onclick="openModalTambahUnit()"
                class="btn-primary px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition shadow-lg">
                <i class="fas fa-plus"></i> Tambah Unit Kerja
            </button>
            @endif
        </div>
    </div>

    <!-- BOX WRAPPER: overflow-x scroll so wide charts stay readable instead of zooming out -->
    <div class="glass-card relative w-full overflow-hidden" style="max-width: calc(100vw - 300px);">

        <!-- Scroll hint badge -->
        <div id="scroll-hint" class="hidden absolute top-3 right-3 z-10 bg-blue-600/80 text-white text-[9px] font-bold px-3 py-1 rounded-full backdrop-blur-sm border border-blue-400/40 pointer-events-none">
            <i class="fas fa-arrows-alt-h mr-1"></i> Geser untuk melihat lebih
        </div>

        <!-- AREA DIAGRAM: scrollable container -->
        <div class="w-full overflow-x-auto overflow-y-auto pt-8 pb-6 px-4" id="chart-area" style="max-height: 72vh; min-height: 320px;">
            <div id="chart_div" class="inline-block origin-top-left">
                <!-- Rendered Chart -->
            </div>
        </div>

    </div>
</div>

@if(auth()->user()->role === 'kadis')
<!-- ===================== MODAL TAMBAH UNIT KERJA ===================== -->
<div id="modalTambahUnit" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[120] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-lg p-8 border border-white/30 animate-scale-up text-left">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white uppercase tracking-wider">
                <i class="fas fa-plus-circle mr-2 text-emerald-400"></i>Tambah Unit Kerja
            </h3>
            <button onclick="closeModalTambahUnit()" class="text-white/50 hover:text-white text-xl transition"><i class="fas fa-times"></i></button>
        </div>

        <form action="{{ route('unit-kerja.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Nama Unit Kerja</label>
                <input type="text" name="nama_unit" required
                    placeholder="cth: Bidang Pencegahan dan Pengendalian Penyakit"
                    class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 transition placeholder-white/30">
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Level</label>
                <select name="level" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-emerald-400 transition">
                    <option value="">-- Pilih Level --</option>
                    <option value="bidang">Bidang</option>
                    <option value="seksi">Seksi / SubBag</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Unit Induk (Parent)</label>
                <select name="parent_id" required class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-emerald-400 transition">
                    <option value="">-- Pilih Unit Induk --</option>
                    @foreach($units->where('level', '!=', 'seksi') as $u)
                        <option value="{{ $u->id }}">{{ $u->nama_unit }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-500 py-3.5 rounded-xl font-black text-sm text-white uppercase tracking-widest transition-all shadow-lg shadow-emerald-500/20 mt-2">
                <i class="fas fa-save mr-2"></i>Simpan Unit Kerja
            </button>
        </form>
    </div>
</div>

<!-- ===================== MODAL EDIT UNIT KERJA ===================== -->
<div id="modalEditUnit" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[120] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-lg p-8 border border-white/30 text-left">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-white uppercase tracking-wider">
                <i class="fas fa-pen mr-2 text-blue-400"></i>Edit Unit Kerja
            </h3>
            <button onclick="closeModalEditUnit()" class="text-white/50 hover:text-white text-xl transition"><i class="fas fa-times"></i></button>
        </div>

        <form id="formEditUnit" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Nama Unit Kerja</label>
                <input type="text" name="nama_unit" id="edit_unit_nama" required
                    class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition">
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Level</label>
                <select name="level" id="edit_unit_level" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-400 transition">
                    <option value="bidang">Bidang / Bagian Utama</option>
                    <option value="bagian">Bagian</option>
                    <option value="seksi">Seksi / Subbag / UPTD</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-blue-200 mb-1.5 block">Unit Induk (Parent)</label>
                <select name="parent_id" id="edit_unit_parent_id" class="w-full bg-black/40 border border-white/20 rounded-xl p-3 text-sm text-white focus:ring-2 focus:ring-blue-400 transition">
                    <option value="">-- Langsung di bawah Dinas (Tidak Ada Induk) --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}">{{ $u->nama_unit }} ({{ ucfirst($u->level) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="bg-yellow-500/10 border border-yellow-400/30 rounded-xl p-3">
                <p class="text-[10px] text-yellow-200"><i class="fas fa-info-circle mr-1.5"></i>Seluruh pegawai di unit ini akan otomatis mengikuti nama baru. Tidak ada data yang hilang.</p>
            </div>
            <button type="submit"
                class="w-full btn-primary py-3.5 rounded-xl font-black text-sm text-white uppercase tracking-widest transition-all shadow-lg mt-2">
                <i class="fas fa-save mr-2"></i>Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endif

@if(auth()->user()->role === 'kadis')
<!-- ===================== MODAL KONFIRMASI HAPUS UNIT ===================== -->
<div id="modalHapusUnit" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden z-[130] items-center justify-center p-4">
    <div class="glass-strong w-full max-w-md p-8 border border-red-500/30 text-center">
        <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-5 border border-red-400/30">
            <i class="fas fa-trash-alt text-2xl text-red-400"></i>
        </div>
        <h3 class="text-lg font-black text-white uppercase tracking-wider mb-2">Hapus Unit Kerja?</h3>
        <p class="text-sm text-blue-200 mb-1">Anda akan menghapus:</p>
        <p id="hapus_unit_nama" class="text-base font-bold text-white bg-white/10 rounded-xl px-4 py-2 mb-5 break-words"></p>
        <p class="text-[11px] text-yellow-300/80 mb-6"><i class="fas fa-exclamation-triangle mr-1"></i>Unit hanya dapat dihapus jika tidak ada pegawai atau sub-unit di dalamnya.</p>
        <div class="flex gap-3">
            <button onclick="closeModalHapusUnit()" class="flex-1 py-3 rounded-xl font-bold text-sm bg-white/10 hover:bg-white/20 transition border border-white/10">
                Batal
            </button>
            <form id="formHapusUnit" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-8 py-3 rounded-xl font-black text-sm bg-red-600 hover:bg-red-500 transition shadow-lg shadow-red-500/20 text-white uppercase tracking-wider">
                    <i class="fas fa-trash mr-2"></i>Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {packages:["orgchart"]});
    google.charts.setOnLoadCallback(initData);

    let allUnits = [];
    let allStaff = [];
    let chart = null;
    const isKadis = {{ auth()->user()->role === 'kadis' ? 'true' : 'false' }};

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
                realId: u.id,
                realParentId: u.parent_id
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

            const subUnits = allUnits.filter(n => n.parentSlug === target.slug);
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
        const hasChildren = allUnits.some(u => u.parentSlug === node.slug) ||
                            allStaff.some(s => s.parentSlug === node.slug);

        const canClick = isUnit && hasChildren;
        const clickAttr = canClick ? `onclick="renderView('drill', '${node.slug}')"` : '';

        const editBtn = isKadis && isUnit
            ? `<button onclick="event.stopPropagation(); openModalEditUnit(${node.realId}, '${node.name.replace(/'/g, "\\'")}', '${node.level}', ${node.realParentId ?? 'null'})"
                class="edit-unit-btn absolute top-1.5 right-1.5 w-6 h-6 bg-white/10 hover:bg-blue-500/60 rounded-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 shadow-lg border border-white/20"
                title="Edit unit kerja ini">
                <i class="fas fa-pen text-[8px] text-white"></i>
              </button>`
            : '';

        const deleteBtn = isKadis && isUnit
            ? `<button onclick="event.stopPropagation(); openModalHapusUnit(${node.realId}, '${node.name.replace(/'/g, "\\'")}')"
                class="delete-unit-btn absolute top-1.5 left-1.5 w-6 h-6 bg-white/10 hover:bg-red-500/70 rounded-lg flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 shadow-lg border border-white/20"
                title="Hapus unit kerja ini">
                <i class="fas fa-trash text-[8px] text-white"></i>
              </button>`
            : '';

        if (!isUnit) {
            return `<div class="node-box node-staff animate-fade-in">
                        <div class="text-[9px] font-bold text-white leading-tight">${node.name}</div>
                        <div class="text-[7px] text-blue-200 opacity-70 mt-1 uppercase">${node.jabatan}</div>
                    </div>`;
        }

        return `<div class="node-box node-${node.level} ${canClick ? 'cursor-pointer' : ''} relative group" ${clickAttr}>
                    ${editBtn}
                    ${deleteBtn}
                    <div class="text-[8px] text-blue-300 font-bold uppercase tracking-tighter opacity-70 mb-1">${node.name}</div>
                    <div class="text-[11px] font-black text-white leading-tight">${node.managerName}</div>
                    <div class="text-[7px] text-blue-100 mt-1 opacity-80 uppercase">${node.managerJabatan}</div>
                    ${canClick ? '<div class="mt-2 pt-1 border-t border-white/10 text-[7px] text-blue-300 font-bold uppercase tracking-widest"><i class="fas fa-search-plus mr-1"></i> Expand</div>' : ''}
                </div>`;
    }

    function fitToScreen() {
        const area = document.getElementById('chart-area');
        const chartDiv = document.getElementById('chart_div');
        const scrollHint = document.getElementById('scroll-hint');

        // Always reset to natural size first
        chartDiv.style.transform = 'scale(1)';
        chartDiv.style.transformOrigin = 'top left';

        setTimeout(() => {
            const areaWidth = area.clientWidth - 32; // minus padding
            const chartWidth = chartDiv.scrollWidth;

            if (chartWidth > areaWidth) {
                const scale = Math.max(areaWidth / chartWidth, 0.65);
                if (scale < 1) {
                    chartDiv.style.transform = `scale(${scale})`;
                    // Adjust the container height to account for the scale shrink
                    chartDiv.style.marginBottom = `-${chartDiv.scrollHeight * (1 - scale)}px`;
                }
                // Show scroll hint if chart is still wider than container after scaling
                if (chartDiv.scrollWidth * scale > areaWidth + 20) {
                    scrollHint.classList.remove('hidden');
                    setTimeout(() => scrollHint.classList.add('hidden'), 3000);
                }
            }
        }, 60);
    }

    function updateBreadcrumb(node) {
        const breadcrumb = document.getElementById('breadcrumb');
        const path = document.getElementById('breadcrumb-path');
        breadcrumb.classList.remove('hidden');
        path.innerHTML = `<span class="text-white flex items-center"><i class="fas fa-chevron-right mx-2 text-blue-400"></i> ${node.name}</span>`;
    }

    function resetView() { renderView('root'); }
    window.onresize = fitToScreen;

    // ===================== MODAL HELPERS =====================
    function openModalTambahUnit() {
        const m = document.getElementById('modalTambahUnit');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeModalTambahUnit() {
        const m = document.getElementById('modalTambahUnit');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    function openModalEditUnit(id, nama, level, parentId) {
        const form = document.getElementById('formEditUnit');
        form.action = `{{ url('/unit-kerja/update') }}/${id}`;
        document.getElementById('edit_unit_nama').value = nama;
        document.getElementById('edit_unit_level').value = level;
        const parentSelect = document.getElementById('edit_unit_parent_id');
        parentSelect.value = parentId ?? '';
        // Disable the current unit from parent options to prevent self-reference
        Array.from(parentSelect.options).forEach(opt => {
            opt.disabled = opt.value == id;
        });
        const m = document.getElementById('modalEditUnit');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeModalEditUnit() {
        const m = document.getElementById('modalEditUnit');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    function openModalHapusUnit(id, nama) {
        document.getElementById('hapus_unit_nama').textContent = nama;
        document.getElementById('formHapusUnit').action = `{{ url('/unit-kerja/hapus') }}/${id}`;
        const m = document.getElementById('modalHapusUnit');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeModalHapusUnit() {
        const m = document.getElementById('modalHapusUnit');
        m.classList.add('hidden'); m.classList.remove('flex');
    }

    // Close modals on backdrop click
    window.addEventListener('click', function(e) {
        if (e.target.id === 'modalTambahUnit') closeModalTambahUnit();
        if (e.target.id === 'modalEditUnit') closeModalEditUnit();
        if (e.target.id === 'modalHapusUnit') closeModalHapusUnit();
    });
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
        max-width: 170px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
    }

    .node-box:hover {
        transform: translateY(-4px);
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }

    /* Show edit button on node hover */
    .node-box:hover .edit-unit-btn {
        opacity: 1 !important;
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

    @keyframes scale-up {
        from { opacity: 0; transform: scale(0.95); }
        to   { opacity: 1; transform: scale(1); }
    }
    .animate-scale-up { animation: scale-up 0.2s ease-out; }

    /* Custom scrollbar for the chart area */
    #chart-area::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    #chart-area::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
    }
    #chart-area::-webkit-scrollbar-thumb {
        background: rgba(96, 165, 250, 0.4);
        border-radius: 10px;
    }
    #chart-area::-webkit-scrollbar-thumb:hover {
        background: rgba(96, 165, 250, 0.7);
    }

    /* Also show delete btn on node hover */
    .node-box:hover .delete-unit-btn {
        opacity: 1 !important;
    }
</style>
@endsection
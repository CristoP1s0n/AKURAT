<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. SEED UNIT KERJA (Sesuai Bagan) ---
        
        // Root: Dinas Kesehatan
        $dinkes = UnitKerja::create(['nama_unit' => 'Dinas Kesehatan Kota Manado', 'level' => 'bidang']);

        // Sekretariat (Dibawah Kadin)
        $sekretariat = UnitKerja::create(['nama_unit' => 'Sekretariat Dinas', 'level' => 'bidang', 'parent_id' => $dinkes->id]);
        
        // Sub Bagian (Dibawah Sekretariat)
        $subbagKeuangan = UnitKerja::create(['nama_unit' => 'Sub Bagian Keuangan', 'level' => 'seksi', 'parent_id' => $sekretariat->id]);
        $subbagUmum = UnitKerja::create(['nama_unit' => 'Sub Bagian Umum dan Kepegawaian', 'level' => 'seksi', 'parent_id' => $sekretariat->id]);

        // Bidang-Bidang (Sejajar Sekretariat, Dibawah Kadin)
        $bidangKesmas = UnitKerja::create(['nama_unit' => 'Bidang Kesehatan Masyarakat', 'level' => 'bidang', 'parent_id' => $dinkes->id]);
        $bidangP2P = UnitKerja::create(['nama_unit' => 'Bidang Pencegahan dan Pengendalian Penyakit', 'level' => 'bidang', 'parent_id' => $dinkes->id]);
        $bidangYankes = UnitKerja::create(['nama_unit' => 'Bidang Pelayanan Kesehatan', 'level' => 'bidang', 'parent_id' => $dinkes->id]);
        $bidangSDK = UnitKerja::create(['nama_unit' => 'Bidang Sumber Daya Kesehatan', 'level' => 'bidang', 'parent_id' => $dinkes->id]);

        // Unit Tambahan
        $uptd = UnitKerja::create(['nama_unit' => 'UPTD', 'level' => 'seksi', 'parent_id' => $dinkes->id]);
        $kjf = UnitKerja::create(['nama_unit' => 'KJF (Kelompok Jabatan Fungsional)', 'level' => 'seksi', 'parent_id' => $dinkes->id]);


        // --- 2. SEED USERS (Hierarki Pelaporan / Parent-Child) ---

        // 1. Kepala Dinas (Top)
        $kadis = User::create([
            'nip' => '198505302005011001', 'nama' => 'Boby K. Kereh, SH, M.Si', 'password' => Hash::make('password'),
            'role' => 'kadis', 'jabatan' => 'Kepala Dinas', 'golongan' => 'IV/c', 'unit_id' => $dinkes->id
        ]);

        // 2. Sekretaris Dinas (Lapor ke Kadin)
        $sekdin = User::create([
            'nip' => '197205032000032006', 'nama' => 'Meity E. Pangkerego, SE, ME', 'password' => Hash::make('password'),
            'role' => 'kabag', 'parent_id' => $kadis->id, 'jabatan' => 'Sekretaris Dinas', 'golongan' => 'IV/a', 'unit_id' => $sekretariat->id
        ]);

        // 3. Kepala Bidang P2P (Lapor ke Kadin)
        $kabidP2P = User::create([
            'nip' => '198104022005012012', 'nama' => 'dr. Sicilia Kumaat, MPH', 'password' => Hash::make('password'),
            'role' => 'kabag', 'parent_id' => $kadis->id, 'jabatan' => 'Kabid Pencegahan dan Pengendalian Penyakit', 'golongan' => 'IV/a', 'unit_id' => $bidangP2P->id
        ]);

        // 4. Kepala Sub Bagian Keuangan (Lapor ke Sekretaris Dinas)
        $kasubbagKeu = User::create([
            'nip' => '197406072008011011', 'nama' => 'Fiktor R. Sinadia, SE', 'password' => Hash::make('password'),
            'role' => 'kasie', 'parent_id' => $sekdin->id, 'jabatan' => 'Kasubbag Keuangan', 'golongan' => 'III/d', 'unit_id' => $subbagKeuangan->id
        ]);

        // 5. Staff Keuangan (Lapor ke Kasubbag Keuangan)
        $staffKeu = User::create([
            'nip' => '000000000001', 'nama' => 'Staff Administrasi Keuangan', 'password' => Hash::make('password'),
            'role' => 'staff', 'parent_id' => $kasubbagKeu->id, 'jabatan' => 'Pengelola Data', 'golongan' => 'III/a', 'unit_id' => $subbagKeuangan->id
        ]);

        // 6. Staff Bidang P2P (Langsung Lapor ke Kabid)
        $staffP2P = User::create([
            'nip' => '198010112005212017', 'nama' => 'dr. WIWINDA TJELENI', 'password' => Hash::make('password'),
            'role' => 'staff', 'parent_id' => $kabidP2P->id, 'jabatan' => 'Fungsional Adminkes Pertama', 'golongan' => 'IX', 'unit_id' => $bidangP2P->id
        ]);
    }
}
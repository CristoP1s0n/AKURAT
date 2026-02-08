<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\Tupoksi;
use App\Models\KriteriaTupoksi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        
        // DB::statement('TRUNCATE users, unit_kerja, tupoksi, kriteria_tupoksi RESTART IDENTITY CASCADE');

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
        $seksiPTM = UnitKerja::create([
            'nama_unit' => 'Seksi Pencegahan dan Pengendalian Penyakit Tidak Menular', 
            'level' => 'seksi', 
            'parent_id' => $bidangP2P->id
        ]);
        $seksiSurvim = UnitKerja::create([
            'nama_unit' => 'Seksi Surveilans dan Imunisasi', 
            'level' => 'seksi', 
            'parent_id' => $bidangP2P->id
        ]);

        $bidangYankes = UnitKerja::create(['nama_unit' => 'Bidang Pelayanan Kesehatan', 'level' => 'bidang', 'parent_id' => $dinkes->id]);
        $bidangSDK = UnitKerja::create(['nama_unit' => 'Bidang Sumber Daya Kesehatan', 'level' => 'bidang', 'parent_id' => $dinkes->id]);

        // Unit Tambahan
        $uptd = UnitKerja::create(['nama_unit' => 'UPTD', 'level' => 'seksi', 'parent_id' => $dinkes->id]);
        $kjf = UnitKerja::create(['nama_unit' => 'KJF (Kelompok Jabatan Fungsional)', 'level' => 'seksi', 'parent_id' => $dinkes->id]);


        // --- 2. SEED USERS (Hierarki Pelaporan / Parent-Child) ---

        // 1. Kepala Dinas (Top)
        $kadis = User::create([
            'nip' => '198505302005011001', 'nama' => 'Boby K. Kereh, SH, M.Si', 'password' => Hash::make('198505302005011001'),
            'role' => 'kadis', 'jabatan' => 'Kepala Dinas', 'golongan' => 'IV/c', 'unit_id' => $dinkes->id
        ]);

        // 2. Sekretaris Dinas (Lapor ke Kadin)
        $sekdin = User::create([
            'nip' => '197205032000032006', 'nama' => 'Meity E. Pangkerego, SE, ME', 'password' => Hash::make('197205032000032006'),
            'role' => 'kabag', 'parent_id' => $kadis->id, 'jabatan' => 'Sekretaris Dinas', 'golongan' => 'IV/a', 'unit_id' => $sekretariat->id
        ]);

        // 4. Kepala Sub Bagian Keuangan (Lapor ke Sekretaris Dinas)
        $kasubbagKeu = User::create([
            'nip' => '197406072008011011', 'nama' => 'Fiktor R. Sinadia, SE', 'password' => Hash::make('197406072008011011'),
            'role' => 'kasie', 'parent_id' => $sekdin->id, 'jabatan' => 'Kasubbag Keuangan', 'golongan' => 'placeholder', 'unit_id' => $subbagKeuangan->id
        ]);

        // 5. Kepala Sub Bagian UMUM DAN KEPEGAWAIAN (Lapor ke Sekretaris Dinas)
        $kasubbagKepeg = User::create([
            'nip' => '198604132010012005', 'nama' => 'Adriana V. Kumontoy, SH', 'password' => Hash::make('198604132010012005'),
            'role' => 'kasie', 'parent_id' => $sekdin->id, 'jabatan' => 'Kasubbag Umum dan Kepegawaian', 'golongan' => 'placeholder', 'unit_id' => $subbagKeuangan->id
        ]);

        /* 5. Staff Keuangan (Lapor ke Kasubbag Keuangan) -- placeholder
        $staffKeu = User::create([
            'nip' => '000000000001', 'nama' => 'Staff Administrasi Keuangan', 'password' => Hash::make('password'),
            'role' => 'staff', 'parent_id' => $kasubbagKeu->id, 'jabatan' => 'Pengelola Data', 'golongan' => 'III/a', 'unit_id' => $subbagKeuangan->id
        ]); */

        // 3. Kepala Bidang P2P (Lapor ke Kadis)
        $kabidP2P = User::create([
            'nip'       => '198104022005012012', 
            'nama'      => 'dr. Sicilia Kumaat, MPH', 
            'password'  => Hash::make('198104022005012012'), // Pastikan baris ini lengkap
            'role'      => 'kabag', 
            'parent_id' => $kadis->id, 
            'jabatan'   => 'Kabid Pencegahan dan Pengendalian Penyakit', 
            'golongan'  => 'IV/a', 
            'unit_id'   => $bidangP2P->id,
            'is_active' => true,
        ]);

        // Kepala Seksi PTM (Lapor ke Kabid P2P)
        $kasiePTM = User::create([
            'nip' => '19721224199603200', 
            'nama' => 'DEISY LAPIAN SKM', 
            'password' => Hash::make('19721224199603200'),
            'role' => 'kasie', 
            'parent_id' => $kabidP2P->id, // Melapor ke Kabid
            'jabatan' => 'FUNGSIONAL EPIDEMIOLOG KESEHATAN MADYA / KORDINATOR SEKSI PENYAKIT TIDAK MENULAR', 
            'golongan' => 'IV/a', 
            'unit_id' => $seksiPTM->id
        ]);

        // Staff Seksi PTM (Bawahan Kasie PTM)
        $staffPTM1 = User::create([
            'nip' => '197702031996032000', 
            'nama' => 'YESSY SAMPEL S.Kep. Ns', 
            'password' => Hash::make('197702031996032000'),
            'role' => 'staff', 
            'parent_id' => $kasiePTM->id, // Melapor ke Kasie
            'jabatan' => 'PENATA KELOLA LAYANAN KESEHATAN', 
            'golongan' => 'IV/a', 
            'unit_id' => $seksiPTM->id
        ]);

        // Staff Seksi PTM2 (Bawahan Kasie PTM)
        $staffPTM2 = User::create([
            'nip' => '199103012024212000', 
            'nama' => 'IKA FITRIANA DAHLAN  SKM', 
            'password' => Hash::make('199103012024212000'),
            'role' => 'staff', 
            'parent_id' => $kasiePTM->id, // Melapor ke Kasie
            'jabatan' => 'FUNGSIONAL ADMINKES PERTAMA', 
            'golongan' => 'PPPK / IX', 
            'unit_id' => $seksiPTM->id
        ]);

        // Staff Seksi PTM3 (Bawahan Kasie PTM)
        $staffPTM3 = User::create([
            'nip' => '198010112005212017', 
            'nama' => 'dr. WIWINDA TJELENI', 
            'password' => Hash::make('198010112005212017'),
            'role' => 'staff', 
            'parent_id' => $kasiePTM->id, // Melapor ke Kasie
            'jabatan' => 'FUNGSIONAL ADMINKES PERTAMA', 
            'golongan' => 'PPPK / IX', 
            'unit_id' => $seksiPTM->id
        ]);

        // Staff Seksi PTM4 (Bawahan Kasie PTM)
        $staffPTM4 = User::create([
            'nip' => '198705282025211028', 
            'nama' => 'ROLES RUMENGAN S.Ik', 
            'password' => Hash::make('198705282025211028'),
            'role' => 'staff', 
            'parent_id' => $kasiePTM->id, // Melapor ke Kasie
            'jabatan' => 'PENATA LAYANAN OPERASIONAL', 
            'golongan' => 'PPPK / IX', 
            'unit_id' => $seksiPTM->id
        ]);

        $kasieSurvim = User::create([
            'nip' => '197212241996032000', 
            'nama' => 'FANNY D. BARENDS, S.Psi', 
            'password' => Hash::make('197212241996032000'),
            'role' => 'kasie', 
            'parent_id' => $kabidP2P->id, // Melapor ke Kabid
            'jabatan' => 'ADMINISTRATOR KESEHATAN AHLI MADYA', 
            'golongan' => 'IV/a', 
            'unit_id' => $seksiSurvim->id
        ]);

        // Staff Seksi Survim (Bawahan Kasie Survim)
        $staffSurvim1 = User::create([
            'nip' => '198406292009012002', 
            'nama' => 'JEIN JESSIE KAPOJOS, S.Kep', 
            'password' => Hash::make('198406292009012002'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'PELAKSANA PROGRAM', 
            'golongan' => 'III/a', 
            'unit_id' => $seksiSurvim->id
        ]);

        // Staff Seksi Survim (Bawahan Kasie Survim)
        $staffSurvim2 = User::create([
            'nip' => '19920527202421015', 
            'nama' => 'SRIFERAWATI ABDURRAHMAN, S.Kep', 
            'password' => Hash::make('19920527202421015'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'FUNGSIONAL ADMINKES PERTAMA', 
            'golongan' => 'PPPK /IX', 
            'unit_id' => $seksiSurvim->id
        ]);

        // Staff Seksi Survim (Bawahan Kasie Survim)
        $staffSurvim3 = User::create([
            'nip' => '199405012024212043', 
            'nama' => 'MELANI BOKY S.Kep', 
            'password' => Hash::make('199405012024212043'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'FUNGSIONAL ADMINKES PERTAMA', 
            'golongan' => 'PPPK /IX', 
            'unit_id' => $seksiSurvim->id
        ]);

        // Staff Seksi Survim (Bawahan Kasie Survim)
        $staffSurvim4 = User::create([
            'nip' => '198107052025212022', 
            'nama' => 'STEYVIE J. PUSOH, SP', 
            'password' => Hash::make('198107052025212022'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'PENATA LAYANAN OPERASIONAL', 
            'golongan' => 'PPPK / IX', 
            'unit_id' => $seksiSurvim->id
        ]);

        $staffSurvim5 = User::create([
            'nip' => '197803282025211042', 
            'nama' => 'CHARLES BRANDO LALAMENTIK', 
            'password' => Hash::make('197803282025211042'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'PENATA LAYANAN OPERASIONAL', 
            'golongan' => 'PPPK / V', 
            'unit_id' => $seksiSurvim->id
        ]);

        $staffSurvim6 = User::create([
            'nip' => '199505052025211157', 
            'nama' => 'MAIGELS TOMI PINONTOAN RUMBA', 
            'password' => Hash::make('199505052025211157'),
            'role' => 'staff', 
            'parent_id' => $kasieSurvim->id, // Melapor ke Kasie
            'jabatan' => 'PENATA LAYANAN OPERASIONAL', 
            'golongan' => 'PPPK / V', 
            'unit_id' => $seksiSurvim->id
        ]);


        // Kepala Bidang KesMas (Lapor ke Kadis)
        $kabidKesmas = User::create([
            'nip' => '196712041991031011',
            'nama' => 'Ronny I. P. Suoth, SKM, S.Psi, M.Kes',
            'password' => Hash::make('196712041991031011'),
            'role' => 'kabag',
            'parent_id' => $kadis->id,
            'jabatan' => 'Kabid Kesehatan Masyarakat',
            'golongan' => 'IV/a',
            'unit_id' => $bidangKesmas->id, // Ganti dengan variabel ID bidang Kesmas Anda
        ]);

        // Kepala Bidang Yankes (Lapor ke Kadis)
        $kabidYankes = User::create([
            'nip' => '197009232000121002', 'nama' => 'dr. Jimmy Lalita, M.Kes', 'password' => Hash::make('197009232000121002'),
            'role' => 'kabag', 'parent_id' => $kadis->id, 'jabatan' => 'Kabid Pelayanan Kesehatan', 'golongan' => 'placeholder', 'unit_id' => $bidangYankes->id
        ]);

        // Kepala Bidang SDK (Lapor ke Kadis)
        $kabidSDK = User::create([
            'nip' => '00000000000000000001', 'nama' => 'dr. Berty Rumondor', 'password' => Hash::make('00000000000000000001'),
            'role' => 'kabag', 'parent_id' => $kadis->id, 'jabatan' => 'Kabid Sumber Daya Kesehatan', 'golongan' => 'placeholder', 'unit_id' => $bidangSDK->id
        ]);
    }
}
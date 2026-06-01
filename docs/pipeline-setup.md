# Pipeline Setup Documentation — AKURAT

> **Proyek**: AKURAT — Aplikasi Kinerja Terukur  
> **Developer**: Cristo Manalu  
> **Institusi**: Dinas Kesehatan Kota Manado  
> **Pipeline**: GitHub Actions CI/CD (Week 3 Prototype)
> **Infrastruktur**: Azure Virtual Machine + Docker + Cloudflare Tunnel

---

## Daftar Isi

1. [Arsitektur Pipeline CI/CD](#1-arsitektur-pipeline-cicd)
2. [Prasyarat Lokal](#2-prasyarat-lokal)
3. [Cara Menjalankan Pipeline Secara Lokal](#3-cara-menjalankan-pipeline-secara-lokal)
4. [Penjelasan Setiap Job CI](#4-penjelasan-setiap-job-ci)
5. [Konfigurasi CD ke Azure VM](#5-konfigurasi-cd-ke-azure-vm)
6. [Konfigurasi Branch Protection](#6-konfigurasi-branch-protection)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Arsitektur Pipeline CI/CD

Pipeline berjalan otomatis di **GitHub Actions** setiap kali terjadi:
- `push` ke branch `main` atau `feature/**`
- `pull_request` yang menargetkan branch `main`

```
Developer (lokal)
       │  git push / pull request
       ▼
┌─────────────────────────────────────────────┐
│         GitHub Actions Runner               │
│      ubuntu-latest + PHP 8.4                │
│  Composer Install + .env + key:generate     │
└────────┬──────────────┬──────────────┬──────┘
         │              │              │
   ┌─────▼───┐   ┌──────▼───┐  ┌──────▼────┐
   │  lint   │   │ migrate  │  │   test    │
   │ (Pint)  │   │(SQLite)  │  │(PHPUnit)  │
   └─────────┘   └──────────┘  └───────────┘
         │              │              │
         └──────┬────────────────────── ┘
                ▼
         Quality Gate
      (SEMUA HARUS HIJAU)
                │
     ┌──────────┴──────────┐
     │  Hanya jika push    │
     │  ke branch: main    │
     └──────────┬──────────┘
                ▼
   ┌────────────────────────────┐
   │  deploy (SSH ke Azure VM)  │
   │  git pull origin main      │
   │  bash deploy.sh            │
   │    └─ docker compose build │
   │    └─ migrate --force      │
   │    └─ config:cache         │
   │    └─ optimize             │
   └────────────────────────────┘
                │
                ▼
      Azure VM (PHP 8.4-fpm)
      PostgreSQL 15 (Docker)
      Nginx + Cloudflare Tunnel
            🌐 LIVE
```

**File konfigurasi**: `.github/workflows/ci.yml`  
**Script deployment**: `deploy.sh`

---


## 2. Prasyarat Lokal

Untuk menjalankan pipeline di mesin lokal, pastikan sudah terinstal:

| Tool | Versi Minimum | Verifikasi |
|------|--------------|------------|
| PHP | 8.4 | `php -v` |
| Composer | 2.x | `composer -V` |
| SQLite Extension | bawaan PHP | `php -m \| findstr sqlite` |
| Git | 2.x | `git --version` |

> **Catatan Docker**: Di lingkungan produksi, PHP 8.4 dijalankan via Docker (lihat `Dockerfile`).
> Untuk pipeline CI, GitHub Actions runner menggunakan `ubuntu-latest` dengan PHP yang diinstal
> via `shivammathur/setup-php@v2`.

---

## 3. Cara Menjalankan Pipeline Secara Lokal

Urutan perintah ini meniru persis apa yang dilakukan GitHub Actions runner.

### Step 1 — Siapkan Environment

```powershell
# Di direktori proyek AKURAT
cd c:\PSO\AKURAT

# Salin env file (jika belum ada)
Copy-Item .env.example .env

# Generate application key
php artisan key:generate
```

### Step 2 — Install Dependensi

```powershell
composer install --prefer-dist --no-interaction --optimize-autoloader
```

### Step 3a — Jalankan Linting (Job: lint)

```powershell
# --test = hanya tampilkan error, tidak auto-fix
vendor/bin/pint --test
```

Jika ada pelanggaran PSR-12, Pint akan menampilkan file dan baris yang bermasalah.
Untuk auto-fix:

```powershell
# Auto-fix (gunakan di lokal, jangan di CI)
vendor/bin/pint
```

### Step 3b — Jalankan Migration Test (Job: migrate)

```powershell
# Paksa SQLite in-memory via env override
$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; php artisan migrate:fresh --no-interaction
```

Perintah ini akan menjalankan seluruh 14 migration secara berurutan pada database bersih.
Jika ada error foreign key atau urutan migrasi yang salah, akan terdeteksi di sini.

### Step 3c — Jalankan Test Suite (Job: test)

```powershell
# Jalankan semua unit dan feature test
php artisan test --verbose

# Atau jalankan test case spesifik
php artisan test --filter PerformanceServiceTest
php artisan test --filter CheckQuarterLockTest
```

Konfigurasi database testing ada di `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 4. Penjelasan Setiap Job CI

### Job `lint` — Laravel Pint (PSR-12)

**Tujuan**: Memastikan seluruh kode PHP mengikuti standar PSR-12 secara konsisten.

**Mengapa dibutuhkan**:
- Mencegah perbedaan gaya kode antara commit yang berbeda
- Code reviewer lebih mudah fokus pada logika, bukan formatting
- Pint adalah tool resmi Laravel, sudah termasuk di `composer.json`

**Perintah di CI**:
```yaml
run: vendor/bin/pint --test
```

Flag `--test` membuat Pint **hanya memeriksa** tanpa mengubah file (mode dry-run).
Pipeline akan gagal jika ada file yang tidak sesuai PSR-12.

---

### Job `migrate` — Migration Integrity Test

**Tujuan**: Memverifikasi bahwa seluruh 14 file migrasi dapat dijalankan secara berurutan
pada database bersih tanpa error.

**Mengapa dibutuhkan**:
Proyek AKURAT memiliki migrasi yang saling bergantung (foreign key constraints):
```
unit_kerja → users → tupoksi → kriteria_tupoksi → penilaian
```
Jika migrasi baru ditambahkan dengan urutan timestamp yang salah, job ini akan
mendeteksi error `SQLSTATE[HY000]` sebelum kode masuk ke `main`.

**Konfigurasi Database**:
```yaml
env:
  DB_CONNECTION: sqlite
  DB_DATABASE: ':memory:'
```

SQLite in-memory dipilih karena:
- Tidak memerlukan server database eksternal di runner
- Proses migrasi selesai dalam < 5 detik
- Setiap job mendapat database bersih yang terisolasi

---

### Job `test` — PHPUnit 11 (Unit + Feature)

**Tujuan**: Menguji logika bisnis inti dan keamanan middleware setiap kali ada perubahan kode.

**Test yang dijalankan**:

| File Test | Tipe | Jumlah Case | Risiko yang Dimitigasi |
|-----------|------|-------------|----------------------|
| `PerformanceServiceTest.php` | Unit | 9 | Pergeseran perhitungan skor float |
| `CheckQuarterLockTest.php` | Feature | 6 | Bypass middleware penguncian periode |

**Desain Test — `PerformanceServiceTest`**:

Test ini menggunakan `RefreshDatabase` (database di-reset setiap test method) dan
menyiapkan data secara langsung via Eloquent tanpa seeder. Ini memastikan setiap test
berdiri sendiri dan deterministik.

Skenario kritis yang diuji:
- Formula `(skorDiperoleh / (totalKriteria * 3)) * 100` akurat untuk edge case (0 dan 100)
- Kriteria triwulan berbeda tidak saling mempengaruhi perhitungan
- Threshold predikat dibaca dari tabel `settings` (bukan hardcode)

**Desain Test — `CheckQuarterLockTest`**:

Test ini mendaftarkan route uji secara inline di `setUp()` menggunakan `Route::middleware(['web', 'auth', 'active', 'lock'])`. Pendekatan ini dipilih karena:
- Menghindari kebutuhan mock `KinerjaController` yang kompleks
- Test terfokus pada perilaku middleware, bukan controller
- Lebih cepat dan mudah di-maintain

---

## 5. Konfigurasi CD ke Azure VM

Bagian ini menjelaskan langkah-langkah yang harus dilakukan **sekali** untuk mengaktifkan
Continuous Deployment dari GitHub Actions ke Azure VM yang sudah berjalan.

### 5.1 — Prasyarat di Azure VM

Pastikan kondisi berikut sudah terpenuhi di Azure VM:

```bash
# Cek Docker & Docker Compose terinstal
docker --version           # Docker 24+
docker compose version     # Docker Compose v2

# Cek git sudah tersedia
git --version

# Cek direktori proyek sudah ada
ls /var/www/akurat
```

Jika direktori belum ada, clone repository ke VM:
```bash
sudo mkdir -p /var/www/akurat
sudo chown $USER:$USER /var/www/akurat
git clone https://github.com/<username>/akurat.git /var/www/akurat
```

### 5.2 — Buat SSH Key Pair untuk GitHub Actions

Di mesin lokal atau langsung di Azure VM, buat SSH key pair khusus untuk GitHub Actions
(jangan gunakan key pribadi):

```bash
# Di Azure VM — buat key pair baru
ssh-keygen -t ed25519 -C "github-actions-akurat" -f ~/.ssh/github_actions_key -N ""

# Daftarkan public key ke authorized_keys di VM
cat ~/.ssh/github_actions_key.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Tampilkan private key (akan disalin ke GitHub Secret)
cat ~/.ssh/github_actions_key
```

### 5.3 — Konfigurasi GitHub Secrets

Buka repository AKURAT di GitHub → **Settings** → **Secrets and variables** → **Actions**

Tambahkan **3 secret** berikut:

| Secret Name | Nilai | Cara Mendapatkan |
|-------------|-------|-----------------|
| `AZURE_VM_HOST` | IP publik Azure VM | Azure Portal → VM → Overview → Public IP |
| `AZURE_VM_USER` | Username SSH (misal: `azureuser`) | Saat membuat VM di Azure |
| `AZURE_VM_SSH_KEY` | Isi seluruh private key | `cat ~/.ssh/github_actions_key` |

> ⚠️ **Keamanan**: Private key harus dimulai dengan `-----BEGIN OPENSSH PRIVATE KEY-----`.
> Salin seluruh isinya termasuk baris header dan footer ke dalam GitHub Secret.

### 5.4 — Konfigurasi GitHub Environment `production`

Job `deploy` di `ci.yml` menggunakan `environment: production`. Ini memberikan:
- Approval manual opsional sebelum deploy (untuk keamanan tambahan)
- Log deploy yang terpisah dari CI
- Kemampuan menambahkan environment-specific secrets

Cara membuat:
1. GitHub → Settings → Environments → **New environment**
2. Nama: `production`
3. Opsional: aktifkan **Required reviewers** jika ingin approval manual

### 5.5 — Konfigurasi `.env` di Azure VM

File `.env` **tidak** di-push ke GitHub (ada di `.gitignore`).
File ini harus dibuat manual di VM satu kali:

```bash
cd /var/www/akurat
cp .env.example .env

# Edit sesuai konfigurasi produksi
nano .env
```

Ubah nilai-nilai berikut di `.env` produksi:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://akurat.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=akurat_db        # Nama container Docker
DB_PORT=5432
DB_DATABASE=db_akurat
DB_USERNAME=postgres
DB_PASSWORD=dinkesmanado2026

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Lalu generate app key:
```bash
docker exec akurat_app php artisan key:generate
```

### 5.6 — Verifikasi Pipeline CD

Setelah semua konfigurasi selesai, coba dengan push ke `main`:

```bash
git push origin main
```

Buka tab **Actions** di GitHub. Anda harus melihat:

```
✅ lint          (~ 30 detik)
✅ migrate       (~ 20 detik)
✅ test          (~ 45 detik)
       ↓ (semua hijau)
✅ deploy        (~ 60-120 detik)
```

### 5.7 — Diagram Aliran Rahasia (Secret Flow)

```
GitHub Secrets (terenkripsi)
  AZURE_VM_HOST ──────┐
  AZURE_VM_USER ──────┤──► appleboy/ssh-action ──► SSH ke Azure VM
  AZURE_VM_SSH_KEY ───┘         (di runner)              │
                                                         ▼
                                                   git pull origin main
                                                   bash deploy.sh
                                                         │
                                                         ▼
                                                   🌐 AKURAT LIVE
```

---

## 6. Konfigurasi Branch Protection

Branch `main` dilindungi untuk memastikan tidak ada kode rusak yang masuk ke production.

### Cara Mengaktifkan di GitHub

1. Buka repository AKURAT di GitHub
2. Klik **Settings** → **Branches**
3. Klik **Add branch protection rule**
4. Isi **Branch name pattern**: `main`
5. Aktifkan opsi berikut:

| Opsi | Nilai | Alasan |
|------|-------|--------|
| Require status checks to pass | ✅ | Semua 4 job (lint, migrate, test, deploy) harus hijau |
| Required status checks | `lint`, `migrate`, `test` | Job CI — deploy dikecualikan agar PR tetap bisa dibuat |
| Do not allow bypassing | ✅ | Berlaku juga untuk admin/solo dev |
| Require pull request before merging | ✅ | Audit trail setiap perubahan |
| Dismiss stale PR reviews | ✅ | Review ulang jika ada perubahan baru |

> **Catatan Solo Developer**: Meskipun proyek ini dikerjakan sendiri, Branch Protection
> tetap diterapkan sebagai praktik profesional. Alur kerja yang benar adalah:
> `feature/nama-fitur` → PR ke `main` → CI hijau → Merge → CD otomatis ke Azure VM.

---

## 6. Troubleshooting

### ❌ Error: `SQLSTATE[HY000]: General error: 1 no such table`

**Penyebab**: Ada model yang mengakses tabel yang belum termigrasikan, atau test tidak menggunakan `RefreshDatabase`.

**Solusi**:
```php
// Pastikan trait ini ada di setiap test class yang mengakses DB
use Illuminate\Foundation\Testing\RefreshDatabase;
```

---

### ❌ Error: `Integrity constraint violation: FOREIGN KEY constraint failed`

**Penyebab**: Test membuat `User` sebelum membuat row di `unit_kerja`.

**Solusi**: Pastikan `UnitKerja::create(['id' => 1, ...])` dipanggil di `setUp()` sebelum `User::factory()->create()`.

---

### ❌ Pint melaporkan banyak error di file yang tidak diubah

**Penyebab**: Vendor dan file generated tidak dikecualikan.

**Solusi**: Buat atau periksa file `pint.json` di root proyek:
```json
{
    "exclude": ["vendor", "bootstrap/cache", "storage"]
}
```

---

### ❌ CI gagal di step `key:generate` dengan error "APP_KEY is missing"

**Penyebab**: `.env` belum dibuat sebelum `key:generate` dijalankan.

**Solusi**: Pastikan step `cp .env.example .env` ada **sebelum** `php artisan key:generate` di workflow.

---

*Dokumen ini dibuat sebagai bagian dari deliverable Week 3 — Pipeline Prototype.*  
*Last updated: Juni 2026*

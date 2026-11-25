# Checkpoint P0 Packaging ZIP — Handover Agent 1
Tanggal: 25 November 2026
Branch: `work/limited-zip-p0-20261125`
Status: **IMPLEMENTASI PACKAGING AWAL — CI/ARTIFACT BELUM TERVERIFIKASI**

## 1. AUDIT

### 1.1 Identitas source
- Repository: `zane0184/cbt_november`
- HEAD sebelum perubahan packaging: `7a89acf5e1ea9227f12b48a1f13e5d69ff0dcd05`
- Main pada pemeriksaan: `f1665d0feffe04e6581150d1fd7ff8433a80b83a`
- Branch Agent 2: `audit/independent-qa-admin-guru-siswa-20261125`
- Workflow awal: `.github/workflows/build-web-deployment.yml`
- Blob workflow awal: `c2ea4c907001ce7f12aa347f354f8099dffa07e3`

Perbandingan GitHub membuktikan branch implementer identik dengan HEAD `7a89acf...` sebelum patch. Branch implementer dan main diverged; pada pemeriksaan terakhir sebelum checkpoint, branch implementer 1.993 commit ahead dan 41 commit behind main. Jangan merge atau rebase otomatis.

### 1.2 Temuan packaging sebelum patch
- Branch implementer belum tercantum pada `on.push.branches`.
- Rsync hanya mengecualikan `backup_before_patch/`, belum seluruh variasi backup dan file `.before`.
- Denylist environment belum menutup semua `.env.*` dengan allowlist `.env.example`.
- Pemeriksaan secret, private key/certificate, credential/token, dump/database, nested archive, temporary/cache/log/session/swap/crash belum menyeluruh.
- ZIP hanya menjalani `unzip -t` dan pemeriksaan pola path terbatas.
- Belum ada clean extraction, full manifest coverage, verifikasi README hasil ekstraksi, atau Browser E2E.

### 1.3 Dokumen sebelumnya
- P0-01 Installer/System Tenant: PASS/CLOSED sebagaimana handover; laporan sumber P0-01 terpisah belum diverifikasi ulang dalam tahap ini.
- P0-02A.1: checkpoint aktual dibaca; Run #182 ID `45538552057`, PHP 8.3 dan 8.4 / MySQL 8.0 InnoDB, masing-masing 643 PASS, 1 SKIPPED, 3.327 assertions. Installer E2E, Student Import acceptance, dan benchmark denial-path PASS.
- CP resmi tetap BLOCKED sampai artefak resmi dan checksum terverifikasi.
- Run #182 bukan packaging run dan artifact list yang diperiksa kosong. Run ID `45540331755` juga tidak memiliki artifact pada pemeriksaan yang dilakukan; run tersebut bukan bukti ZIP packaging.

### 1.4 Ketersediaan workspace dan baseline
- Repository remote diperiksa melalui GitHub.
- Direktori lokal `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan pada runtime sesi ini.
- ZIP baseline `CBT REVISI 6 NOPEMBER 2026.zip` tidak ditemukan pada mount lokal atau hasil pencarian Project Files/Library yang tersedia. Laporan Tahap 1 lama menyatakan ZIP tersebut pernah diperiksa, berisi 16.080 entri, dan lolos `testzip`; bytes ZIP tidak tersedia untuk audit ulang saat checkpoint ini dibuat.
- Karena workspace lokal tidak ter-mount, status working tree lokal tidak dapat diperiksa. Source remote branch adalah basis perubahan yang digunakan.

## 2. DESAIN

Packaging dipertahankan sebagai satu ZIP Web CBT mandiri per madrasah, satu instalasi dan satu database terisolasi. Tidak mengubah arsitektur, tidak memasukkan Android atau portal Publisher Super Admin.

Pengamanan yang diterapkan pada workflow:
1. Memicu branch implementer melalui push dan mempertahankan `workflow_dispatch`.
2. Mempertahankan hanya `.env.example` dan menolak file environment lain.
3. Mengecualikan direktori backup dan file backup dengan berbagai ekstensi/pola.
4. Mengecualikan dump/database, private key/certificate, log, cache, session, temporary, swap, dan artefak runtime.
5. Memeriksa path, case-collision, symlink, nested archive, ekstensi terlarang, serta pola secret/credential.
6. Membuat `SHA256SUMS.txt` untuk seluruh file paket selain manifest.
7. Memeriksa ZIP, mengekstrak ke direktori baru yang kosong, menjalankan verifikasi checksum, dan memastikan cakupan manifest sama dengan seluruh file hasil ekstraksi.
8. Memeriksa README dari hasil ekstraksi untuk topik instalasi wajib.

## 3. IMPLEMENTASI

### 3.1 Backup byte-exact sebelum patch

**Workflow**
- Asli: `.github/workflows/build-web-deployment.yml`
- Backup: `backend-laravel/backup_before_patch/P0_PACKAGING_WORKFLOW_20261125/build-web-deployment.yml.before`
- Manifest: `backend-laravel/backup_before_patch/P0_PACKAGING_WORKFLOW_20261125/MANIFEST.md`
- Ukuran asli: 8.834 byte
- Git blob SHA-1: `c2ea4c907001ce7f12aa347f354f8099dffa07e3`
- SHA-256: `9bb3b4cee00493407fa2f8537ebaff7f736ee6bf6a1922258794b7b3b710f44c`
- Verifikasi fetch ulang: konten identik, ukuran sama, blob SHA-1 sama, SHA-256 sama.

**README**
- Asli: `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`
- Backup: `docs/backup_before_patch/P0_PACKAGING_README_20261125/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md.before`
- Manifest: `docs/backup_before_patch/P0_PACKAGING_README_20261125/MANIFEST.md`
- Ukuran asli: 7.935 byte
- Git blob SHA-1: `fb4b5dd9824813253a4974828f80678ced5bee3a`
- SHA-256: `dab482cd12106925f11574af39b5614372d20fda603e08489925ae4fc36af99f`
- Verifikasi fetch ulang: konten identik, ukuran sama, blob SHA-1 sama, SHA-256 sama.

### 3.2 File source yang diubah
1. `.github/workflows/build-web-deployment.yml`
   - Menambahkan branch implementer ke trigger push.
   - Memperluas exclusion untuk environment, backup, temporary, dump/database, credential/certificate, dan artefak runtime.
   - Menambahkan audit rekursif pada staging dan setiap ZIP entry.
   - Menambahkan clean extraction dan verifikasi seluruh checksum serta coverage manifest.
   - Menambahkan validasi README dari hasil ekstraksi.
2. `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`
   - Menambahkan instruksi ekstraksi melalui Windows Explorer, termasuk Extract All dan penggunaan direktori kosong.

### 3.3 Commit implementasi
- Backup workflow: `c55fcd1731cf1aafc0e694444438370cee9b6bf9`
- Manifest backup workflow: `f9251d60b50996d7f39cb57c7476fdd9a5a66366`
- Backup README: `30b870e5390234c2f44f8f22561a01c4cc1552d0`
- Manifest backup README: `5190fe09b3a88fd1363d02b84ebcd5cca3285ac6`
- README update: `ef1aef07d46a69e6127ddae5d2ddee0f9cf6cefa`
- Workflow hardening awal: `33ba2e0211a5f8d5bec17fdf9f94ea996e1d8e65`
- Scanner/checksum correction: `e3f23c4318fe050b1c9b1033aa637c9da9ebdf6c`
- Regex quoting correction: `76f30bf13dc5dad5c0f7d78b3ad5a28e1fe0cfa7`
- Credential/nested archive detection: `fe3e2ef60243b8123a9c1a1257f475ece3ed7248`

HEAD terakhir yang diverifikasi sebelum checkpoint: `fe3e2ef60243b8123a9c1a1257f475ece3ed7248`.
Blob workflow terakhir: `00930f28d7ba0915e72051747720be97a12518aa`.
Blob README terakhir: `a84c1d991c556557b101915f27071f5cfd3eff45`.

Tidak ada perubahan pada branch `main` atau branch Agent 2.

## 4. PENGUJIAN

### Pemeriksaan yang benar-benar dilakukan
- Fetch ulang kedua file backup dan membandingkan konten byte-equivalent: PASS.
- Ukuran, Git blob SHA-1, dan SHA-256 backup: PASS.
- Fetch ulang workflow dan README setelah patch: PASS.
- Branch implementer dibandingkan dengan commit terakhir: identik pada saat pemeriksaan.
- `get_commit_combined_status` untuk commit implementasi terakhir mengembalikan daftar status kosong; belum ada bukti CI packaging selesai.

### Belum dijalankan / belum diverifikasi
- GitHub Actions packaging pada exact HEAD.
- Composer/build package dalam workflow baru.
- ZIP artifact nyata, ukuran, dan SHA-256 ZIP.
- ZIP recursive audit terhadap bytes artifact.
- Clean extraction dan full checksum dari artifact nyata.
- Laravel runtime setelah ekstraksi.
- Browser E2E.
- Independent Agent 2 audit.
- Deployment VPS dan UAT madrasah.

Tidak ada database yang dihubungkan, migration yang dijalankan, deployment, atau Gradle.

## 5. STATUS

**P0 PACKAGING: IMPLEMENTASI AWAL SELESAI — CI/ARTIFACT NOT VERIFIED.**

Workflow sekarang memiliki guard yang lebih luas, tetapi belum boleh dinyatakan lulus sampai workflow berjalan pada exact HEAD dan bytes ZIP nyata lolos pemeriksaan. ZIP belum dinyatakan siap uji operasional terbatas.

## 6. CHECKPOINT LANJUTAN

1. Verifikasi GitHub Actions yang dipicu dari branch implementer dan exact commit terbaru setelah checkpoint ini.
2. Jika workflow gagal, audit log langkah yang gagal dan patch dengan backup byte-exact baru.
3. Jika workflow lulus, catat run ID, artifact ID, nama/ukuran ZIP, SHA-256, exact source SHA, dan manifest.
4. Serahkan identitas artifact kepada Agent 2; Agent 2 tetap READ-ONLY.
5. Agent 2 melakukan Gerbang A-C. Browser E2E baru dilakukan setelah A-C PASS.
6. Jangan merge ke main, deployment VPS, migration produksi, atau klaim siap operasional terbatas sebelum semua gate dan persetujuan koordinator terpenuhi.

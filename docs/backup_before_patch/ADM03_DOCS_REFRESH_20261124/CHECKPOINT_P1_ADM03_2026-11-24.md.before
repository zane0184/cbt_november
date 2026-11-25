# Checkpoint P1 — ADM-03 Tenant Branding Assets
Tanggal checkpoint: 24 November 2026  
Repository: `zane0184/cbt_november`  
PR #23: OPEN / DRAFT / NOT MERGED  
Branch: `work/p1-seven-targets-20261124`  
Source SHA teruji: `87ba3a8042d0e75822b0b04349957075b6a1edb0`

## Posisi terakhir yang benar-benar terbukti
- Full Tenant Backend CI #45436535839: PASS.
- PHPUnit: 586 passed, 0 failed, 2.772 assertions.
- MySQL 8.0.46/InnoDB disposable.
- PHP lint, Composer/platform, identity migration, migration matrix, two-installation branding/storage, benchmark, dan ReportPolicy: PASS.
- ZIP build #45436535921: SUCCESS, tetapi bukan bukti ZIP final diterima.
- Source kerja adalah GitHub branch aktif. Workspace lokal tidak tersedia pada runtime; baseline ZIP asli tidak dimodifikasi.

## Status ADM-03
**PARTIAL / NOT ACCEPTED.**

Matriks 41 item: 38 PASS, 1 PASS legacy regression only, 2 PARTIAL.
Dua PARTIAL adalah verifikasi header HTTP Content-Type PNG/WebP dari Nginx/aaPanel. Content MIME dari bytes, preview, dan deklarasi HTML type telah diuji di CI; header HTTP aktual belum dibuktikan pada staging.

Rujuk:
- `docs/MATRIX_ADM_03_ACCEPTANCE_41_2026-11-24.md`
- `docs/REPORT_ADM_03_TENANT_BRANDING_2026-11-24.md`

## Keputusan arsitektur yang harus dipertahankan
- Satu instalasi aplikasi mandiri untuk satu madrasah.
- Database, storage, .env, domain, dan identity sendiri per instalasi.
- Super Admin pusat hanya untuk lisensi/aktivasi/entitlement; tidak memiliki akses akademik.
- `LocalInstallationIdentity` mengikat authenticated user dan TenantContext.
- TenantContext/tenant_id/TenantScoped dipertahankan sebagai mekanisme internal/legacy; tidak ada penghapusan massal.
- Shared database test hanya legacy regression, bukan acceptance antar-madrasah.
- Role aktif: ADMIN_MADRASAH, GURU, SISWA. Proktor hanya capability/penugasan sementara.
- Tidak ada portal Operator/Kamad. Room bukan admission gate. Kamera bukan syarat CBT standar.
- Android native dan Web Super Admin pusat ditunda sampai Juni 2027.

## Batas operasi
- Jangan merge PR #23 atau mengubah status draft tanpa persetujuan owner.
- Jangan force push, hapus branch/commit/backup.
- Jangan jalankan migration pada database produksi.
- Jangan ubah .env produksi atau deploy VPS.
- Jangan jalankan Gradle melalui terminal.

## Langkah berikutnya
1. Siapkan staging disposable (bukan production) dengan prosedur backup/rollback.
2. Verifikasi HTTP Content-Type untuk PNG, WebP, dan fallback SVG dari URL storage melalui Nginx/aaPanel.
3. Uji missing/corrupt favicon dan fallback pada login serta layout authenticated.
4. Lampirkan log/hasil response header ke matriks ADM-03.
5. Jika semua 41 item PASS, ajukan hasil ke owner; jangan melanjutkan ADM-04/05 sebelum acceptance dan persetujuan.
6. Setelah ADM-03 accepted, lanjutkan tujuh target P1 sesuai dependency, bukan hanya ZIP build.

## Handover
Belum perlu ganti coding agent. Agent berikutnya harus mulai dari branch dan SHA di atas, membaca dua dokumen ADM-03 yang dirujuk, memeriksa source aktual, dan tidak mengulang patch yang sudah tercatat.

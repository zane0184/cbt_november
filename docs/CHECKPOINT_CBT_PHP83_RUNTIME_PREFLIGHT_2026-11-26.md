# Checkpoint — CBT PHP 8.3 Runtime Preflight — 26 November 2026

## Posisi
Persiapan runtime khusus PHP 8.3 dan MySQL 8 disposable sudah ditambahkan pada branch terpisah:
`work/cbt-curriculum-flow-php83-20261126`.

## Status
- Workflow dibuat: `.github/workflows/cbt-curriculum-flow-php83.yml`.
- Trigger: manual `workflow_dispatch`.
- Runtime: PHP 8.3.x, Composer 2, `pdo_mysql`.
- Database: MySQL 8.0 ephemeral, database `cbt_curriculum_flow_test`, engine InnoDB.
- PHPUnit: tidak dipanggil.
- Migrasi: hanya ke database ephemeral yang namanya diverifikasi.
- Source aplikasi: tidak diubah.
- VPS/produksi: tidak disentuh.
- Eksekusi workflow: **BELUM DILAKUKAN**.
- Integrasi alur Admin → Guru → TP/ATP → Bank Soal → Snapshot Ujian → Siswa: **BELUM DIUJI**.

## Bukti commit
`4003688375f1a24e8442c61c69e5e26861295c4f` — penambahan workflow preflight.

## Lanjutkan dari sini
Jalankan workflow manual. Jika preflight lulus, buat runner integrasi aplikasi non-PHPUnit berdasarkan implementasi route/controller/service/model aktual. Jangan menggunakan fixture CP sintetis sebagai bukti CP resmi. Pertahankan isolasi database disposable dan verifikasi teardown.

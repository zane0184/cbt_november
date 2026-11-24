# Checkpoint ADM-02 — Profil Madrasah — 24 November 2026

## Posisi

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Source SHA diuji: `1e4f2c044609cf11b8a29eb8be381016508cbc98`
- PR #23: OPEN / DRAFT / NOT MERGED
- PR base: `work/phpunit-41-root-cause-20261123`

## Milestone selesai secara kode dan CI

ADM-02 PARTIAL:
- Profil madrasah di route `/admin/school-profile`.
- Field resmi/singkat, NSM/NPSN, jenjang, negeri/swasta, alamat terstruktur, kontak, website, nama Kepala Madrasah, informasi tambahan.
- Permission `tenant.manage` dan exact role ADMIN_MADRASAH.
- Penyimpanan dibatasi tenant yang login.
- Audit hanya menyimpan daftar nama kolom berubah.
- Migration menambah kolom nullable tanpa backfill.

Bukti:
- CI #45402844847: SUCCESS.
- Full PHPUnit: 565 passed, 2.456 assertions.
- Test TenantProfileManagement: PASS.
- ZIP build #45402844935: SUCCESS, artifact kandidat ID 14922728949.

Laporan: `docs/REPORT_ADM_02_TENANT_PROFILE_2026-11-24.md`.

## Belum selesai

- ADM-03 logo/favicon.
- ADM-04 branding aplikasi/login/footer/social media.
- ADM-05 kop dokumen, preview, integrasi PDF.
- ADM-06 edit/detail profil Guru, riwayat perubahan/penugasan.
- ADM-07 CRUD/status/transisi Siswa.
- ADM-08 kebijakan first-login password change dan seluruh akses.
- ADM-09–23 sesuai matriks.
- Staging, fresh install/upgrade, backup/restore, E2E portal, ZIP final.

## Next action

Lanjutkan ADM-03, kemudian ADM-04 dan ADM-05. Jangan mengulang ADM-02 atau account lifecycle. Jangan merge PR #23 tanpa persetujuan. Jangan jalankan migration staging/produksi tanpa preflight, backup database, dan persetujuan owner.

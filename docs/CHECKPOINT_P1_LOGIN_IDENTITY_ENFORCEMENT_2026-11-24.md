# Checkpoint CBT Madrasah — 24 November 2026

## Posisi source

- Repository: `zane0184/cbt_november`
- Branch integrasi aktif: `work/p1-seven-targets-20261124`
- PR: #23 OPEN / DRAFT / NOT MERGED
- HEAD terverifikasi: `c669ad26942abbb65508875f75ae92e493dff6ae`
- Base: `work/phpunit-41-root-cause-20261123` / `1c33e5d422175f9bd85d206230271a9fe5a4eecd`

## Tahap terakhir yang benar-benar selesai

### P1 Login Identity Enforcement — code + CI PASS

- Admin: username.
- Guru PNS/PPPK: NIP 18 digit.
- Guru Honorer: NIK 16 digit.
- Siswa: NISN 10 digit.
- NUPTK opsional, bukan fallback login.
- MySQL CHECK + SQLite trigger untuk format/kategori.
- Forward-only migration 190001 mewajibkan employee_identifier non-NULL.
- Preflight read-only menghentikan proses jika ada data legacy yang belum memenuhi aturan.
- Form Admin mengikuti status kepegawaian dan membersihkan identitas kategori yang tidak berlaku.
- Test MySQL/SQLite, resolver login, preflight, dan Admin form lulus pada exact SHA di atas.

### Bukti

- Backend CI #45397205018: SUCCESS.
- 556 PHPUnit passed, 2.416 assertions, 179,37 detik.
- Migration MySQL 8.0.46/InnoDB: PASS.
- SQLite trigger/preflight: PASS.
- Disposable benchmark: PASS.
- ZIP build #45397204873: SUCCESS, artifact kandidat ID 14922091140.

Laporan lengkap: `docs/REPORT_P1_LOGIN_IDENTITY_ENFORCEMENT_2026-11-24.md`.

## Hal yang belum selesai

- P1 identity belum acceptance pada database instalasi nyata; migration tidak dijalankan.
- Staging dua VPS masih BLOCKED; tidak ada bukti akses SSH/aaPanel.
- Backend keseluruhan belum dinyatakan selesai.
- ADM-01–23 belum diterima melalui E2E.
- Web Guru belum lengkap; DOCX import prioritas.
- Web Siswa belum lengkap; browser/offline/recovery E2E belum lulus.
- Kurikulum lintas portal dan immutable snapshot belum diterima.
- Device Lease/fencing bukan acceptance produksi pada proyek web saat ini; Android ditunda Juni 2027.
- ZIP kandidat bukan ZIP distribusi final.

## Aturan saat melanjutkan

1. Refresh PR #23, HEAD, workflow, dan checkpoint ini.
2. Source aktual adalah kebenaran; jangan ulangi patch yang sudah ada.
3. Jangan merge PR tanpa persetujuan owner.
4. Jangan jalankan migration pada staging/produksi sebelum preflight, backup database, dan persetujuan.
5. Lanjutkan ke matriks backend dan ADM-01–23, lalu Web Guru DOCX, Web Siswa, integrasi portal, staging clone, dan ZIP final.
6. Tidak ada klaim siap produksi/siap distribusi sampai seluruh acceptance criteria terpenuhi.

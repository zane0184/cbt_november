# Checkpoint Web Admin — Account Lifecycle — 24 November 2026

## Posisi source

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- PR #23: OPEN / DRAFT / NOT MERGED
- Code SHA diuji: `19b0e230a42192fcdcae706043ec8dc6539cb48f`
- PR base: `work/phpunit-41-root-cause-20261123`

## Milestone terakhir selesai

Admin account lifecycle code dan CI PASS:
- `is_active` dan `last_login_at`.
- Login resolver + web/API login hanya menerima akun aktif.
- Middleware `active.user` membaca status kanonis dari database per request terlindungi.
- Deaktivasi mencabut Sanctum token, web session database, dan remember token.
- Aktivasi akun.
- Reset password acak, ditampilkan sekali, tidak dicatat ke audit log.
- Audit lifecycle.
- Siswa dengan attempt ujian aktif tidak dapat dinonaktifkan/reset melalui jalur biasa.
- Hanya role tunggal GURU/SISWA dalam tenant yang sama yang dapat dikelola oleh Admin.

## Bukti

- Tenant Backend CI #45401251292: SUCCESS.
- Full PHPUnit: 561 passed, 2.440 assertions, 167,86 detik.
- MySQL 8.0.46/InnoDB migration gate: PASS.
- UserAccountLifecycleTest: PASS.
- Disposable benchmark: PASS.
- ZIP build #45401251207: SUCCESS, artifact kandidat ID 14923211658.

Laporan: `docs/REPORT_ADM_ACCOUNT_LIFECYCLE_2026-11-24.md`.

## Belum selesai

- Edit/detail profil akun.
- Import/export Guru/Siswa.
- ADM-02–05 profil madrasah dan branding.
- ADM-09–23.
- Web Guru DOCX import dan alur penuh.
- Web Siswa offline/recovery E2E.
- Integrasi kurikulum lintas portal.
- Staging dua instalasi, backup/restore, fresh install/upgrade.
- ZIP distribusi final.

## Langkah berikutnya

1. Mulai ADM-02–05 berdasarkan master specification dan source aktual.
2. Lanjutkan ADM-06–23 dengan implementasi, test, dan bukti.
3. Jangan mengulang identity enforcement atau account lifecycle patch.
4. Jangan merge PR #23 tanpa persetujuan owner.
5. Jangan jalankan migration staging/produksi tanpa preflight, backup database, dan persetujuan.
6. Android native dan Web Super Admin tetap ditunda sampai Juni 2027.

Tidak ada klaim siap produksi atau siap distribusi.

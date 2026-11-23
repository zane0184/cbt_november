# Checkpoint — CBT Madrasah W2 Continuation

Tanggal: 24 November 2026 (WIB)
Branch: `work/solo-web-cbt-completion-20261123-execution`
Patch commit: `1b25c3bbed07fba6def2dfe869d686a8559be1cc`

## Posisi terakhir yang benar-benar dikerjakan
1. Membaca prompt W2 final, report pembaruannya, AGENT_START_HERE, master spesifikasi Admin/Guru/Siswa, Issue #20 dan Issue #10 beserta keputusan owner yang relevan.
2. Memperbaiki quoting query post-check InnoDB pada `.github/workflows/tenant-backend-ci.yml` dengan prepared statement; enforcement InnoDB tetap fail-closed.
3. Membuat backup byte-exact file workflow sebelum patch dan memverifikasi blob SHA backup sama dengan blob SHA sumber.
4. Belum ada bukti Actions baru pada patch commit saat checkpoint ini dibuat. Jangan tandai workflow PASS sebelum run selesai.

## Area yang tidak boleh diulang
- Full PHPUnit remediation 42 failure sudah CLOSED.
- CI #1714 pada SHA `e894d3f...` membuktikan 536 passed / 2,231 assertions / 0 failed; kegagalan workflow hanya post-check InnoDB.
- Profil dasar Admin sudah diimplementasikan; jangan buat ulang.
- W1 Livewire reauthorization subset Admin/Guru sudah terverifikasi; jangan patch ulang tanpa regresi konkret.

## Backlog berikutnya
1. Verifikasi GitHub Actions pada source commit patch CI; hanya tangani kegagalan aktual.
2. Lanjutkan profil Admin: metadata tambahan dengan desain schema kompatibel, logo/private asset, branding login, kop surat/preview.
3. Lanjutkan ADM-01..ADM-23 dan Web Guru/Web Siswa berdasarkan master spec serta issue owner.
4. Satukan kontrak Kurmer + KBC end-to-end; jangan membuat schema/DTO paralel atau data resmi sintetis.
5. Tutup P0/P1 security, browser E2E, MySQL 8/InnoDB, fresh install/upgrade, backup/restore dan UAT sebelum ZIP.
6. Buat satu ZIP lengkap hanya setelah seluruh acceptance gate lulus.

## Batasan tetap
- Satu instalasi dan database mandiri per madrasah; tidak ada shared academic multi-tenant.
- Role lokal hanya ADMIN_MADRASAH/GURU/SISWA; Proktor adalah assignment sementara Guru; Kamad/Operator bukan portal aktif.
- Android dan Web Super Admin ditunda sampai Juni 2027.
- Tidak ada production migration, deployment, merge PR atau akses live tanpa persetujuan owner.
- ZIP final belum dibuat; status NOT READY FOR DISTRIBUTION.

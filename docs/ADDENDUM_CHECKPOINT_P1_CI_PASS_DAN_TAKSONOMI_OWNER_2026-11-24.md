# ADDENDUM CHECKPOINT P1 — CI FULL PASS DAN KOREKSI TAKSONOMI OWNER

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Source SHA yang diuji: `235501fcea40092b514ba22946e8d11e20163487`  
PR #23 tetap OPEN / DRAFT / belum merged.

## 1. PENGUJIAN — Koreksi status CI

Checkpoint P1 sebelumnya mencatat full PHPUnit cancelled karena pada saat laporan ditulis run #45342397865 masih berjalan. Hasil final exact SHA kemudian tersedia dan **mengubah status pengujian**:

- Tenant Backend CI [Run #45342902716](https://github.com/zane0184/cbt_november/actions/runs/45342902716): **SUCCESS**
- MySQL: 8.0.46, service container disposable/InnoDB
- PHP: 8.3
- Composer lock/platform validation dan locked install: PASS
- Isolated installer E2E database: PASS
- Focused login/tenant smoke: PASS
- Full PHPUnit: **522 passed, 0 failed, 2,200 assertions**
- Durasi full suite: **113.79 detik**

Test login mencakup Admin Madrasah, Guru username tanpa NIP, Guru Honorer tanpa NIK, Siswa NISN, serta penolakan login legacy Operator/Kamad/permanent Proktor dan akses dashboard Kamad. Ini bukti Backend CI exact commit; belum membuktikan seluruh Web Admin/Guru/Siswa, browser-to-Laravel E2E, VPS runtime, load/stability production, backup/restore UAT, atau kesiapan ZIP distribusi.

Build ZIP workflow [#45342902884](https://github.com/zane0184/cbt_november/actions/runs/45342902884) juga SUCCESS exact SHA yang sama. Itu hanya membuktikan pipeline package build; artifact belum diterima sebagai final karena gate fitur dan staging belum lengkap.

## 2. AUDIT — Owner update taksonomi asesmen terbaru

Owner issue #8 menetapkan koreksi final bahwa label UTS, UAS, UM dan Simulasi TKA **bukan enum/jenis asesmen sistem yang boleh di-hardcode**. Keputusan ini mengalahkan addendum Proktor 23 Nov yang masih mengunci kelayakan pada UTS/UAS/UM, serta checkpoint sebelumnya yang mengulang marker tersebut.

Yang berlaku:
- Jenis/fungsi asesmen, lingkup, nama kegiatan, instrumen, dan mode pelaksanaan adalah dimensi terpisah.
- Nomenklatur Kurmer + KBC harus menjadi satu kerangka; aktivitas bernama UTS/UAS/UM boleh menjadi nama kegiatan yang dikonfigurasi, bukan nilai enum tertutup.
- Simulasi TKA/TKA merupakan kegiatan terstandar eksternal, bukan kategori asesmen CBT internal default.
- Jangan hardcode label ini pada migration, seeder, enum, validation, forms, APIs, reports, policies, Proctor assignment atau tests.
- Inventarisasi seluruh string/dependency fixed-category sebelum patch. Tidak boleh mengubah label saja sambil menyisakan hardcoded behavior.

## 3. DESAIN — Proktor

Proktor tetap fail-closed pada source P1. Service assignment yang saat ini menolak assignment karena kontrak eligibility belum final harus dipertahankan disabled sampai ada owner-approved metadata marker yang:
1. Secara eksplisit menyatakan asesmen pusat/berkewenangan khusus.
2. Tidak diturunkan dari nama kegiatan, enum UTS/UAS/UM, atau mode CBT.
3. Memiliki scope placement Exam+Room terpisah dari daftar peserta admission.
4. Tidak menggunakan ExamSession/SessionRoom/wave/room-session sebagai syarat memulai Attempt.
5. Memberi Guru capability sementara via assignment, dengan masa berlaku, revoke, audit dan server-side policy/API.
6. Membatasi Proktor hanya pada monitoring Exam+Room yang ditugaskan; tidak memberi Admin hak mengakses roster/Attempt monitoring, dan tidak membuka kemampuan Reset/Force Submit/Takeover tanpa otorisasi tersendiri.

Belum ada marker resmi final yang cukup untuk migration atau implementation. Jangan mengaktifkan Proktor assignment hanya untuk membuat UI terlihat lengkap.

## 4. IMPLEMENTASI DAN KONTROL

- Tidak ada perubahan source, migration, data, deployment, atau merge pada addendum ini.
- Tidak ada patch ulang terhadap source yang sudah lulus CI.
- P0 base/PR #22 tetap tidak disentuh.
- PR #23 tetap Draft.

## 5. NEXT EXECUTION ORDER

1. Audit source dan permission Web Admin untuk membuktikan Admin tidak memiliki akses Exam monitoring, roster, Attempt payload, atau data Proktor yang tidak diizinkan; tambahkan negative HTTP/API tests jika temuan membuktikan celah.
2. Audit seluruh hardcoded UTS/UAS/UM/Simulasi TKA dengan pencarian pada migration, seeder, validation, form, API, reports, policy, Proctor assignment, tests dan docs; hasilkan matriks temuan.
3. Audit/melengkapi ADM-06 account lifecycle (username stable, optional identifiers, detail/edit/deactivate/reactivate/reset/session revoke, audit history, masking).
4. Lanjutkan ADM-07/08 data siswa dan penempatan riwayat, ADM-09/10 import-export XLSX/CSV Guru/Siswa, ADM-13/15/16/17 academic master, ADM-02–05 profil madrasah/branding/letterhead, ADM-21–23 settings/reporting/backup UI; tidak membuat ulang fitur yang sudah ada.
5. Guru: DOCX question import E2E, media, immutable question snapshot, publication/token, assessment workflows, analytics and scoring.
6. Siswa: view runner sudah ada di P1 dan sama dengan Agent4 branch pada exact blob; jangan copy ulang. Integrasikan browser E2E harness Agent4 hanya setelah mengaudit file-only dependency, tidak membawa ExamSession/ExamRoomSession backend architecture.
7. Kurmer + KBC provenance end-to-end, no fabricated official content.
8. Staging and ZIP acceptance only after all target tests and UAT.

## 6. STATUS

**Backend P1 exact SHA: FULL CI PASS.**  
**Fitur web keseluruhan: PARTIAL / NOT ACCEPTED.**  
**Proktor Mode B: BLOCKED by missing owner-approved marker.**  
**ZIP: BUILD SUCCESS, NOT FINAL DISTRIBUTION READY.**

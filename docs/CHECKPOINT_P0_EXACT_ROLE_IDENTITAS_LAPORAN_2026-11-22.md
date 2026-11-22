# CHECKPOINT — P0 EXACT-ROLE + IDENTITAS TENANT + LAPORAN
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch Agent 3: `work/web-guru-foundation-20261122`
Branch Agent 2 (read-only): `work/stage4d-account-lifecycle-20261122`
Source HEAD Agent 3 sebelum patch: `702274d5298930f5f1dd13b71b7e402c12868109`
Commit test terakhir tahap ini: `fc91263f4922bb30c4d9933147abdd8f6860f26d`

## Tahap terakhir
P0 exact-role sudah dipatch pada dua komponen Livewire Agent 3 dan test regresi ditambahkan. **Belum selesai/closed** sampai CI PHPUnit menunjukkan PASS.

## AUDIT
- `QuestionBank` dan `ExamManagement` sebelumnya belum memeriksa exactly-one role GURU.
- Helper bersama `TeacherPortalAccess` sudah tersedia dan memeriksa tenant, exact-role, serta profil Guru.
- Modul identitas Tenant sudah ada; jangan membuat modul kedua.
- Tenant model belum menampilkan data Kamad; TeacherProfile memuat NIP tetapi belum status PNS/Non-PNS.
- Login masih statis (huruf C dan CBT MADRASAH), GET login belum menerima tenant branding.
- Kop PDF bersama sudah ada, tetapi Kemenag hardcoded, belum ada signature blocks dan snapshot.
- Laporan saat ini menampilkan 0 jika score tidak ada, export rekap menampilkan satu row per Attempt, dan summary mengagregasi seluruh score history. Perlu kontrak aturan attempt/current score/finalized.

## DESAIN
- Otorisasi Guru menggunakan `TeacherPortalAccess::profile()` + permission komponen + `TeacherAccess::assignedSubjectIds()`.
- Identitas tenant dan Kamad harus ditetapkan Agent 2 dahulu.
- Satu layanan branding tenant dipakai login dan kop PDF.
- Laporan memakai current-score pointer FINALIZED, aturan peserta/Attempt yang eksplisit, serta immutable snapshot.

## IMPLEMENTASI
Branch Agent 3:
- `QuestionBank.php` diubah; commit `901ab9a05ab678c3c8882131d5463d72a0202382`.
- `ExamManagement.php` diubah; commit `8ac0253fcf1cafaf0e5808ee66107a691ba0a7e0`.
- `TeacherPortalFoundationTest.php` ditambah 3 test; commit `fc91263f4922bb30c4d9933147abdd8f6860f26d`.
- Backup tiga file: `docs/backup/web-guru-exact-role-20261122/`; 3/3 blob SHA MATCH.
- Issue #8 diberi update untuk koordinasi Agent 2.
- Branch Agent 3 dibanding checkpoint: 7 ahead, 0 behind.
- Tidak ada source Agent 2, migration, schema, Composer, CBT Engine, atau database diubah.

## PENGUJIAN
- Backup SHA: PASS 3/3.
- PHPUnit: PENDING CI; combined status belum menampilkan check run.
- PHP lint lokal: NOT RUN (tidak ada checkout lokal).
- Browser QA, PDF aktual, print, mobile, dan tenant branding E2E: NOT RUN.
- Tidak ada akses atau perubahan database produksi.

## BLOCKER DAN LANGKAH BERIKUTNYA
1. Periksa hasil GitHub Actions untuk commit `fc91263f4922bb30c4d9933147abdd8f6860f26d`. Jika gagal, baca log dan perbaiki pada branch Agent 3 dengan backup tambahan.
2. Jangan lanjutkan WG-01 import sebelum P0 exact-role CI PASS.
3. Minta Agent 2 meninjau exact-role backend/policy dan menyepakati kontrak identitas tenant, Kamad, status kepegawaian, NIP, layanan branding, serta report snapshot.
4. Audit kontrak Agent 2 terhadap source aktual sebelum integrasi login/PDF.
5. Setelah kontrak tersedia, Agent 3 mengerjakan login branding, preview kop, tiga laporan, signature blocks, dan PDF QA.
6. Uji integrasi tenant, attempt/current score, PDF, snapshot immutable, dan histori sebelum menyatakan siap.

## GUARDRAILS
Tidak ada merge, PR, rebase, cherry-pick, deployment, migration produksi, atau perubahan branch Agent 2. Jangan menyatakan P0 PASS sebelum CI membuktikan hasilnya.

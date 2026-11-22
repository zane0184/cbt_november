# Prompt Lanjutan Agent CBT Madrasah — 22 November 2026

## Agent 1 — Redesign Stage4B agar sesuai MODEL SERENTAK

Lanjutkan dari bukti branch Stage4B/4C saat ini. HEAD branch bersama: `4e663cd84038b7c3337d776f702c499f20d8f537`; source Stage4B/4C yang diuji: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`.

**Temuan wajib ditangani:** CI Stage4B telah PASS, tetapi implementasi membuat `ExamSession`, `session_no`, `starts_at/ends_at` per sesi, `exam_session_rooms`, scheduler lifecycle sesi, dan `exam_proctor_assignments.exam_session_room_id`. Keputusan terbaru proyek adalah ujian MODEL SERENTAK tanpa gelombang atau sesi pelaksanaan. Jangan menganggap desain lama diterima hanya karena CI lulus.

Tugas:
1. Buat branch terpisah `work/stage4b-simultaneous-room-alignment-20261122` dari HEAD `4e663cd...`; jangan bekerja bersama Agent 2 pada branch yang sama.
2. Audit semua model, migration, service, policy, controller, Livewire, route, scheduler, test, dan audit log yang bergantung pada `ExamSession`.
3. Buat desain pengganti tanpa sesi: pertahankan satu jendela ujian serentak berdasarkan `exam.start_at`/`exam.end_at` dan waktu server; Admin tetap dapat mengelola ruang; Proktor tetap penugasan sementara dengan akses terbatas pada ujian+ruang yang sah. Jangan membuat gelombang atau sesi pelaksanaan.
4. Rancang relasi dan migration forward-only yang menjaga histori; tidak boleh blanket backfill, drop table, atau migration produksi. Tampilkan file terdampak, backup plan, preflight, rollback/restore, dan test matrix.
5. Lengkapi negative security tests Admin dengan stale GURU permissions untuk direct API bank soal, assessment/exam, token, participant, attempt, answer, submit, result/score, monitoring, dan export sesuai route yang benar-benar ada. Pastikan HTTP denial dan tidak ada mutasi.
6. Jalankan juga test bootstrap permission yang belum dieksekusi oleh filter CI terakhir. Tambahkan test untuk Livewire action/hydration dan seluruh surface yang relevan.
7. Jangan edit `routes/web.php`; serahkan perubahan route kepada Agent 2 sebagai pemilik file tersebut.
8. Pada tahap ini buat desain dan audit dulu; jangan melakukan patch schema besar sebelum desain diaudit ChatGPT. Laporkan AUDIT, DESAIN, IMPLEMENTASI (jika diizinkan), PENGUJIAN terpisah.

## Agent 2 — Lanjut Stage4D–4G

Lanjutkan dari branch Stage4B/4C HEAD `4e663cd84038b7c3337d776f702c499f20d8f537`. Stage4C Teacher Subject Assignment telah memiliki bukti CI 9 feature tests/41 assertions dan 1 concurrency test/8 assertions PASS. Pertahankan implementasi dan test yang sudah lulus.

1. Buat branch khusus `work/stage4d-student-lifecycle-20261122` dari HEAD tersebut; jangan berbagi branch dengan Agent 1.
2. Lanjut Stage4D lifecycle Guru/Siswa sesuai master prompt: edit profil, aktivasi/nonaktif, reset password/recovery berotorisasi dan audit, perpindahan Rombel, promosi/tinggal kelas/kelulusan/keluar madrasah, histori keanggotaan, dan larangan lebih dari satu membership aktif pada periode akademik yang sama. Jangan hard-delete histori.
3. Stage4E: integrasi Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam dan dimensi lulusan dengan versioning/provenance; CP resmi hanya dinyatakan resmi bila sumbernya terbukti.
4. Stage4F: operasi Admin, laporan non-CBT, dukungan/sinkronisasi, backup/restore terisolasi, dan update center sesuai cakupan master prompt.
5. Stage4G: integrasi, regression, tenant/RBAC, audit, idempotency, concurrency, dan dokumentasi.
6. Jangan membuat gelombang/sesi ujian. Jangan mengubah Stage4B session code; itu domain redesign Agent 1.
7. Agent 2 menjadi pemilik `routes/web.php` untuk route Admin dan integrasi route Guru. Jangan mengubah `AuthWebController.php`, `routes/teacher.php`, atau komponen Teacher milik Agent 3.
8. Backup sebelum setiap perubahan; tidak ada migration produksi, merge, PR, atau deployment. Update checkpoint setiap tahap.

## Agent 3 — Lanjut Web Guru

Lanjutkan branch tersendiri `work/web-guru-foundation-20261122`. HEAD saat ini `25fe5e74a0754bcd110ceffac20141880c549ca8`; source yang sudah diuji `e9c6867d40b134538e791bbbba02f5743afec7ea`; CI run `44984094200` PASS 11 tests/44 assertions.

1. Jangan diganti agent lain dan jangan reset pekerjaan.
2. Perbarui checkpoint/report: HEAD 25fe5, source test e9c686, run 449840 PASS. Koreksi hitungan test lama 7 menjadi 11/44.
3. Audit seluruh legacy Guru route/Livewire components sebelum mengaktifkan menu; periksa exact role, stale permission, tenant scope, direct route, dan aksi mutasi.
4. Tambahkan browser/E2E dan visual test responsive desktop/tablet/mobile serta Light/Dark. Jangan klaim siap UI sebelum bukti.
5. Verifikasi definisi hitungan siswa pada dashboard (profil siswa aktif vs akun aktif) dan pastikan tidak menghitung membership yang tidak sah.
6. Kurikulum: jangan mengklaim CP resmi, Pembelajaran Mendalam, atau dimensi lulusan terintegrasi penuh tanpa sumber resmi/versioning dan bukti data.
7. Inventaris 49 PHPUnit doc-comment deprecation warnings. Ubah hanya file yang berada dalam ownership Agent 3; jangan mass-edit test Admin/Analytics milik agent lain.
8. Agent 3 tetap menjadi pemilik `AuthWebController.php`, `routes/teacher.php`, layout dan komponen Teacher. **Jangan edit `routes/web.php`**; serahkan potongan route yang diperlukan kepada Agent 2.
9. Tidak ada migration produksi, merge, PR, deployment, atau distribusi. Update checkpoint dan handover.

## Aturan bersama

- Jangan klaim lulus tanpa log/test evidence.
- Jangan membuat ulang source dari asumsi.
- Jangan mengubah ZIP baseline.
- Backup setiap file sebelum patch.
- Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN.
- Tidak ada akses lintas tenant.
- Tidak ada ujian bergelombang/sesi; gunakan MODEL SERENTAK.
- Laporkan file berubah, lokasi backup, test command, hasil, kendala, dan next step.
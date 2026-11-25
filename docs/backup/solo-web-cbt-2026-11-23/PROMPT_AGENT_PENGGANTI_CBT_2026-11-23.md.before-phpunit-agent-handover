# PROMPT AGENT PENGGANTI — LANJUTKAN CBT MADRASAH DARI GITHUB

Anda adalah agent koordinator sekaligus pelaksana audit/implementasi Web Admin Madrasah, Web Guru, Web Siswa, dan Backend CBT Madrasah.

## TUGAS PERTAMA — WAJIB
Buka repository `https://github.com/zane0184/cbt_november`, branch kerja `work/solo-web-cbt-completion-20261123`, lalu baca:
1. `docs/AGENT_START_HERE.md`
2. `docs/REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md`
3. `docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md`
4. `docs/AGENT_HANDOVER_MASTER_2026-11-23.md`
5. `docs/CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md`
6. `docs/REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md`
7. `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md`
8. `docs/REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md`
9. `docs/REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md`
10. Issue #20 seluruh komentar owner dan Issue #10 arahan comment `7081649923`.

Jangan coding sebelum membaca semua dokumen dan memverifikasi source/CI.

## KEPUTUSAN OWNER — MUTLAK
- Portal aktif hanya Admin Madrasah, Guru, Siswa.
- Role resmi hanya `ADMIN_MADRASAH`, `GURU`, `SISWA`.
- Tidak ada portal/role Operator, Kamad, Proktor, atau Super Admin lokal. Semua tugas operator dilakukan Admin Madrasah. Histori role lama jangan dihapus sembarangan.
- Proktor hanya penugasan sementara pada Guru. Assignment tidak memberi hak monitoring/room/session/seat/roster/Attempt/operasi CBT kecuali owner mengubah keputusan eksplisit di Issue #20.
- Satu instalasi dan database mandiri per madrasah. Tidak ada multi-tenant akademik antar-madrasah atau pertukaran data akademik.
- Integrasi pusat masa depan hanya lisensi/entitlement/pembayaran. Web Super Admin dan Android native ditunda sampai Juni 2027.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah SATU ekosistem, bukan fitur/modul/kurikulum/database terpisah.
- Guru membuat dan menerbitkan ujian sendiri sesuai assignment tanpa persetujuan Admin.
- Admin tidak mengambil alih soal, ujian, peserta, token, Attempt, jawaban, monitoring, penilaian, nilai.
- DOCX prioritas import soal; jangan hidupkan XLSX question import.
- Kamera bukan syarat CBT standar. Browser deterrent bukan secure kiosk.
- Jangan jalankan Gradle via terminal.

## ATURAN SUMBER
- GitHub adalah satu-satunya sumber proyek.
- Jangan membuka ZIP baseline, Project Files, Library/Pustaka ChatGPT, atau sumber non-GitHub.
- Jangan minta user mengulang keputusan atau mengirim source yang sudah tersedia di GitHub.
- Jangan mengarang source dari asumsi; baca source aktual.
- Semua laporan, checkpoint, prompt, backup manifest wajib disimpan di GitHub, bukan Library.
- Source aktual dan CI exact SHA lebih kuat daripada laporan agent lama.

## VERIFIKASI SEBELUM KERJA
Periksa:
1. HEAD aktual branch kerja dan commit graph.
2. PR #21 state/draft/base/head/diff/reviews.
3. PR #13 Web Siswa, #19 Web Guru, #18 Web Admin: exact HEAD, base, changed files, commits, CI, review, merge status.
4. CI terbaru pada exact SHA masing-masing; ambil log/artifact jika tersedia.
5. Issue #20 dan Issue #10 comments terbaru.
6. Master spec Admin/Guru pada main dan spec Siswa pada branch yang benar.
7. Routes, Livewire, controllers, services, models, policies, migrations, tests, backups, docs.
8. Cari pemanggil endpoint sebelum menutup/menghapus route/controller.

Buat matriks EXISTS & VERIFIED / EXISTS BUT BROKEN / MISSING / BLOCKED / DEFERRED. Setiap baris harus punya requirement, path source, policy, test, CI evidence dan status.

## POSISI AWAL YANG HARUS DIVERIFIKASI ULANG
Pada pemeriksaan 23 Nov 2026:
- PR #21 HEAD `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`, OPEN/DRAFT/UNMERGED.
- Backend CI #45156925960: focused login/tenant PASS; full PHPUnit FAILURE. Log detail belum tersedia. Jangan mengarang jumlah/root cause.
- Agent 4 Web Siswa #45156926014: static audit, Chromium, WebKit compatibility, selected Student Laravel result/exam/login jobs PASS. Belum browser-to-real-Laravel E2E atau UAT Safari fisik.
- Commit `dbebbd5` membatasi API materials read-only, menambah policy read, menghapus opsi filter role legacy dari UI Admin, dan menambah regression test. Empat file dibackup byte-exact. Audit kompatibilitas MaterialController yang disederhanakan sebelum mengubahnya.
- Branch sudah punya patch LoginIdentityResolver SYSTEM_GLOBAL, exact-role/policy, token optional, receipt/client_request_id, fencing, dan patch API/test lain. Verifikasi source/CI; jangan mengulang.
- PR #13/#18/#19 tidak otomatis terintegrasi ke #21 karena base branch berbeda.
- Gap utama: full PHPUnit, real Laravel integration Web Siswa, lease/fencing multi-device/concurrency/recovery, ADM-01–ADM-23, Guru end-to-end, curriculum historical reporting, backup/restore, UAT, deployment dan ZIP.
- Curriculum mapping/snapshot memiliki fondasi, tetapi achievement/report masih memakai model legacy LearningOutcome/LearningObjective/LearningPath.

## URUTAN EKSEKUSI — JANGAN LOMPAT

### Tahap 0 — Rekonsiliasi exact state
Verifikasi HEAD/PR/CI terbaru; catat mismatch laporan vs source; jangan commit source. Pilih satu root cause P0 berdasar log aktual.

### Tahap 1 — P0 backend/full PHPUnit
- Ambil log/artifact #45156925960. Jika tak tersedia, jalankan ulang workflow pada exact source SHA dengan rencana test jelas.
- Triage per root cause: fixture, kontrak API, authorization, migration/seeder, ViewException atau source bug.
- Backup byte-exact sebelum patch dan tambah regression test.
- Jangan melemahkan exact role, assignment, local install scope, policy, fencing atau integritas data.
- Full PHPUnit wajib PASS exact HEAD.
- Two-connection MySQL concurrency harus run dan PASS; SKIPPED bukan PASS.
- Audit MaterialController read-only patch: consumers, policy, scope, pagination/filter, response compatibility.

### Tahap 2 — Web Siswa integrasi nyata
- Reconcile PR #13; jangan membuat ulang IndexedDB runner/mobile-first/deterrent.
- Integrasikan ke Laravel API + MySQL disposable.
- Uji login, start/resume, token/jadwal, heartbeat, lease/fencing, answer save/retry/receipt, media, 409, submit barrier, expiry, response loss, offline/reconnect, recovery, multi-tab/device, stale fence, duplicate request, submit/expiry race.
- Focus/visibility hanya audit signal; jangan auto-submit/punish karena blur/focus.
- Uji Android Chrome dan iOS Safari fisik saat tersedia.

### Tahap 3 — Web Guru
- Refresh exact PR #19 source/CI.
- Audit assignment, CP/TP/ATP, materi/topik, bank soal/versioning, DOCX, media, taxonomy asesmen, snapshot, publish/token/jadwal, monitoring Guru pembuat, grading, hasil/analytics.
- Jangan ulang fitur yang sudah ada.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer privat, immutable snapshot dan scoring terbukti.
- Proktor bukan portal dan bukan grant monitoring.

### Tahap 4 — Web Admin
- Baca master spec Admin.
- Buat matriks ADM-01–ADM-23 dengan route/Livewire/controller/service/model/policy/schema/test/status.
- Cari fitur yang sudah ada sebelum membuat ulang.
- Prioritas: identitas/logo/branding/kop; CRUD Guru/Siswa; akun/akses; import/export Guru/Siswa; tahun ajaran/semester/kelas/rombel/mapel/assignment; registry kurikulum; laporan/cetak administratif; audit log; pengaturan; backup/restore.
- Admin tidak boleh mengakses soal/ujian/peserta/Attempt/jawaban/nilai/monitoring.
- Proktor assignment hanya bila owner menetapkan penanda ujian pusat kanonis. Jangan tebak dari EXTERNAL/PUBLISHED.

### Tahap 5 — Kurikulum satu ekosistem sampai laporan
- Satu read model/contract.
- Hubungkan assignment → CP → TP/ATP → materi/topik → Panca Cinta/PM/8 dimensi → QuestionVersion → Assessment → Exam snapshot → Score → laporan historis.
- Simpan provenance/version pada snapshot.
- Jangan buat CP ganda, hapus histori, atau ubah snapshot lama.
- Test bahwa update master tidak mengubah laporan historis.

### Tahap 6 — Acceptance dan distribusi
Full PHPUnit, browser E2E tiga portal, fresh install, upgrade disposable, repeat seeder, rollback guard, backup/restore, security/IDOR, concurrency, load, recovery, UAT. Rencana VPS hanya setelah acceptance dan persetujuan. ZIP satu instalasi/database per madrasah hanya setelah seluruh gate lulus. Android/Web Super Admin tetap deferred Juni 2027.

## PROTOKOL PATCH DAN CI
1. Satu akar masalah per tahap; satu backlog terurut.
2. Sebelum patch, jelaskan bukti, root cause, file, risiko dan test.
3. Backup tiap file existing dan verifikasi byte-exact.
4. Patch sekecil mungkin; jangan overwrite perbaikan agent sebelumnya.
5. Hindari commit source berturut-turut yang membatalkan CI concurrency.
6. Dokumentasi boleh memakai [skip ci] jika workflow mendukung; cek hasil.
7. CI exact SHA; catat PASS/FAIL/SKIPPED/NOT RUN secara tepat.
8. Jika log 404, tulis log tidak tersedia; jangan menebak.
9. Update report/checkpoint/handover GitHub setiap tahap.
10. Tidak merge, production migration, deploy VPS atau ubah DB produksi tanpa persetujuan eksplisit.
11. Jangan buat ZIP final sebelum acceptance lulus.

## FORMAT LAPORAN WAJIB
Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN, CHECKPOINT. Cantumkan file/route/commit, backup path/blob, exact SHA CI, hasil PASS/FAIL/SKIPPED/NOT RUN, blocker dan satu next action.

## HASIL PERTAMA KEPADA OWNER
Setelah verifikasi awal, laporkan:
1. HEAD aktual dan status PR #21.
2. CI exact SHA terbaru.
3. Pekerjaan yang terbukti selesai dan tidak boleh diulang.
4. Root cause P0 berikutnya dengan bukti log.
5. Satu langkah kerja berikutnya.
Jangan meminta owner mengulang keputusan. Jangan mulai banyak fitur sekaligus.

**PERINTAH:** Lanjutkan sampai seluruh acceptance terpenuhi, tetapi bertahap, terarah, terkendali. Jangan menyatakan selesai tanpa bukti.

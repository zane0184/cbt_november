# Laporan Koordinator — Audit Tugas GitHub CBT Madrasah (24 November 2026)

Repository: `zane0184/cbt_november`  
Branch laporan: `audit/coordinator-github-scope-2026-11-24`  
Branch eksekusi yang ditetapkan handover: `work/solo-web-cbt-completion-20261123-execution`  
PR integrasi: [#21](https://github.com/zane0184/cbt_november/pull/21)  
Owner backlog utama: [Issue #20](https://github.com/zane0184/cbt_november/issues/20)

**Status keseluruhan: WORK IN PROGRESS — NOT READY FOR PRODUCTION — NOT READY FOR OPERATIONAL EXAM — NOT READY FOR DISTRIBUTION.**

Audit ini hanya menggunakan GitHub repository, issues, pull requests, source, dokumen, dan GitHub Actions. Tidak membuka ZIP baseline, Project Files, Library, atau sumber di luar GitHub. Tidak ada source runtime, database, migration produksi, VPS, atau PR yang diubah/di-merge dalam audit ini.

---

## 1. AUDIT — keputusan owner yang berlaku

### 1.1 Instalasi dan batas data
- Produk didistribusikan sebagai satu paket aplikasi/ZIP generik.
- Setiap madrasah memasang aplikasi dan menggunakan database akademik sendiri. Tidak ada shared database akademik, pertukaran data pendidikan, atau akses lintas madrasah.
- Koneksi pusat yang direncanakan hanya untuk identitas instalasi, lisensi, entitlement/paket fitur, masa berlaku, aktivasi, dan pembayaran/langganan. Data siswa, akun, rombel, mapel, soal, media, jawaban, Attempt, nilai, dan laporan pendidikan tidak dikirim ke pusat.
- `tenant_id` yang masih ada pada source adalah konteks instalasi lokal/legacy dan defense-in-depth, bukan SaaS multi-tenant antar-madrasah. Jangan menghapus seluruh mekanisme scope lokal secara membabi buta.
- Backup, restore, upgrade, cache, session, queue, storage, logs, export dan temporary files harus terisolasi per instalasi.

### 1.2 Portal dan role
- Portal/role resmi lokal hanya `ADMIN_MADRASAH`, `GURU`, dan `SISWA`.
- Semua pekerjaan Operator dilebur ke Admin Madrasah. Tidak ada akun, role, portal atau dashboard Operator.
- Kamad bukan akun/role/portal. Kepala Madrasah hanya metadata/jabatan/penandatangan dokumen.
- Tidak ada Proktor permanen. Guru dapat ditunjuk sementara sebagai pengawas melalui assignment administratif.
- Assignment pengawas tidak memberikan hak monitoring, room, session, seat, roster, peserta, token, Attempt, jawaban, nilai atau operasi CBT. Penugasan tidak menjadi grant authorization.
- Role legacy OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN tidak boleh dipakai untuk authorization baru. Histori tidak boleh dihapus atau dipetakan otomatis tanpa rencana upgrade, backup, audit dan persetujuan owner.

### 1.3 Penundaan platform
- Web Super Admin dan Android Super Admin ditunda sampai Juni 2027.
- Android native peserta CBT juga berada di luar fase implementasi sekarang menurut handover terbaru; saat fase dibuka, siswa Android tetap wajib memakai aplikasi native, bukan browser/PWA.
- Fokus aktif: Backend Laravel + Web Admin Madrasah + Web Guru + Web Siswa.

### 1.4 Kurikulum dan asesmen
Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam, dan delapan dimensi profil lulusan adalah **satu ekosistem akademik**, bukan kurikulum, database, atau modul yang berdiri sendiri.

Alur data yang wajib dipertahankan:
sumber resmi/provenance/version → registrasi kurikulum instalasi → tahun ajaran/semester/fase/kelas/mapel → assignment Guru dan pinned curriculum context → CP resmi → TP/ATP Guru → materi/topik → Panca Cinta/PM/8 dimensi → QuestionVersion mapping → Assessment context → immutable Exam/Question snapshot → Attempt/Answer/Score → capaian dan laporan historis.

- CP induk adalah referensi resmi read-only.
- Guru memilih CP sesuai assignment dan menyusun TP/ATP serta materi/topik.
- Panca Cinta lima nilai adalah nilai lintas pembelajaran; PM adalah pendekatan pedagogis; delapan dimensi adalah profil lulusan.
- Provenance, versi, tanggal berlaku dan histori wajib terjaga sampai QuestionVersion, snapshot, hasil dan laporan.
- Payload Siswa wajib di-redact server-side: tidak ada answer key, `is_correct`, explanation privat, rubrik internal atau data siswa lain.

**Koreksi nomenklatur paling baru:** komentar owner pada Issue #8 (#7091931831) dan Issue #20 (#7091952804) menyatakan UTS/UAS/UM/SIMULASI TKA bukan kategori/enum baku. Pisahkan fungsi asesmen, cakupan, nama kegiatan yang dapat dikonfigurasi, instrumen, dan mode pelaksanaan. Acuan istilah desain mencakup Asesmen Formatif, Sumatif Lingkup Materi, Sumatif Akhir Semester (bila perlu), Sumatif Akhir Tahun, dan Sumatif Akhir Jenjang; daftar ini bukan enum tertutup. TKA/simulasi TKA, jika digunakan, dipisahkan sebagai kegiatan asesmen terstandar/eksternal. Ketentuan ini **menggantikan** teks dokumen arsitektur 23 November yang lebih lama yang masih mencantumkan empat kategori tetap.

---

## 2. AUDIT — status repository, PR dan CI

### 2.1 Jalur integrasi aktif
- Handover menetapkan branch eksekusi `work/solo-web-cbt-completion-20261123-execution` dan PR integrasi #21.
- PR #21 masih OPEN/DRAFT/UNMERGED pada data PR yang diperiksa. Jangan merge tanpa persetujuan owner.
- Komentar terbaru yang diperiksa pada PR #21 adalah #7102240851. Isinya mencatat triage full PHPUnit dan patch fixture ExamSnapshot/AnswerKeyLeakage.
- Exact source SHA terbaru yang dapat dibuktikan dari komentar/run tersebut: `612c0a7d2adcccea44fa8064c7e451c64282cd64`.
- CI [#45181799176](https://github.com/zane0184/cbt_november/actions/runs/45181799176) pada SHA tersebut: MySQL 8.0.46 disposable, setup PHP 8.3, Composer, migrasi disposable, focused login/tenant smoke PASS; full PHPUnit **84 failed, 434 passed, 1,757 assertions** — FAILURE. Jadi 4 kegagalan lebih sedikit daripada run sebelumnya yang dilaporkan 88 failed / 430 passed; suite tetap belum lulus.
- P0.1 menyimpan instance TeacherSubjectAssignment pada ExamListContractTest; P0.2 menerapkan helper assignment kurikulum kanonis pada ExamSnapshotTest dan AnswerKeyLeakageTest. Perubahan tersebut tidak boleh diulang. Hasil CI menunjukkan masih ada 84 failure untuk ditriage.

### 2.2 PR yang harus direkonsiliasi — jangan merge otomatis
- [PR #21 — Solo Web CBT integration](https://github.com/zane0184/cbt_november/pull/21): jalur integrasi aktif, Draft. Pusatkan kelanjutan di branch execution; verifikasi exact SHA sebelum patch.
- [PR #22 — PHPUnit 41 failures](https://github.com/zane0184/cbt_november/pull/22): Draft dan belum merge. Ada bukti CI #45220055382 pada SHA `856294037312c186f03422071368bcaa2987516a` dengan 518 passed / 0 failed / 2,189 assertions dan smoke 27/90 pada PHP 8.3 + disposable MySQL 8.0.46/InnoDB. Bukti ini hanya untuk SHA/branch PR #22, bukan bukti branch integrasi PR #21 lulus. Handover PR #22 secara eksplisit melarang pengulangan P0 dan meminta review/approval owner sebelum integrasi.
- [PR #13 — Web Siswa](https://github.com/zane0184/cbt_november/pull/13): Draft/unmerged. Runner IndexedDB v2, durable queue, conflict archive, submit barrier, response-loss recovery dan mobile-first UI sudah dikembangkan. Namun belum ada bukti real browser-to-Laravel E2E lengkap pada source integrasi; token optional, receipt, fencing, media, platform enforcement dan UAT masih menjadi gate.
- [PR #19 — Web Guru](https://github.com/zane0184/cbt_november/pull/19): Draft/unmerged. Penghapusan XLSX question import dan regression suite pernah lulus pada SHA historis (117 passed/514 assertions; kemudian 118/520 untuk curriculum display cap). Jangan anggap CI historis itu membuktikan HEAD PR #19 atau PR #21 saat ini lulus. DOCX tetap menunggu kontrak backend final.
- [PR #18 — Web Admin](https://github.com/zane0184/cbt_november/pull/18): Draft/HOLD. Jangan merge. Stage room/session/seat/roster/monitoring dan migration terkait bertentangan dengan batas kewenangan Admin serta ujian serentak. Pertahankan hanya bagian yang setelah audit terbukti sesuai dan sudah direkonsiliasi ke PR #21.
- PR #17/#5/#7/#6 dan PR lama lain bukan jalur integrasi yang otomatis aktif; cek ownership dan isi sebelum reuse.

### 2.3 Status CI yang tidak boleh dicampur
- PR #21 execution branch: run #45181799176 gagal full PHPUnit 84/434/1,757 pada SHA 612c0a7.
- PR #22: run #45220055382 lulus 518/0/2,189 pada SHA 8562940; terpisah dan belum masuk PR #21.
- PR #19: CI 117/514 dan 118/520 pada SHA historis; bukan HEAD terkini.
- PR #13: runner/static/browser evidence tidak sama dengan integrasi real Laravel/MySQL.
- Tidak ada bukti browser E2E ketiga portal, full concurrency/recovery, UAT, fresh-install/upgrade/restore menyeluruh, atau production deployment.

---

## 3. AUDIT — daftar pekerjaan backend dan CBT

### P0 — tutup full PHPUnit pada branch integrasi
Urutan handover:
1. P0.1 ExamListContractTest — patch sudah ada; jangan diulang.
2. P0.2 ExamSnapshotTest dan AnswerKeyLeakageTest — patch helper assignment kanonis sudah ada; verifikasi hasil dan triage failure tersisa.
3. P0.3 ExamVerificationTest — cleanup `device_leases` harus dilakukan sebelum `exam_attempts` sesuai FK RESTRICT; audit response 403 terpisah.
4. P0.4 Phase7CReopen/Phase7A scoring/ScoringHardening/Analytics Cache — audit role, permission, reopen/recalculate, status dan fixture. Jangan beri Admin akses hasil siswa.
5. P0.5 Livewire stale contract — test lama Admin QuestionBank tidak boleh memaksa Admin memiliki authoring soal; pindahkan test authoring ke komponen Guru yang benar atau jadikan negative access test Admin.
6. P0.6 auth/lease/fencing — RateLimiting, heartbeat, monitoring, submit memakai persisted Sanctum identity, bound auth session, lease dan fence generation nyata. Jangan menerima 401/409 tanpa audit kontrak.
7. P0.7 Proktor — tetap fail-closed sampai owner menetapkan marker kanonis kelayakan ujian pusat. Assignment pengawas tidak membuka monitoring atau akses CBT.
8. Jalankan focused test per akar masalah, lalu full PHPUnit pada exact SHA disposable MySQL 8.0.46/InnoDB. Concurrency harus benar-benar dieksekusi, bukan SKIPPED.

### P0/P1 backend integration yang terkait Web Siswa
- Exact-one role dan scope lokal pada seluruh endpoint; tidak ada KAMAD/OPERATOR authorization.
- Login identity terikat ke LocalInstallationIdentity; SYSTEM_GLOBAL tidak boleh dihitung sebagai madrasah kedua.
- Attempt start/resume, server time, expiry, answer revision, idempotency, receipt dan submit barrier harus konsisten.
- Dual login/session: sesi/token kedua saat Attempt aktif harus ditolak; device fingerprint saja bukan identitas autentikasi yang cukup.
- Lease/fencing generation, heartbeat, stale request rejection dan recovery same Attempt wajib dibuktikan multi-device/two-connection.
- Receipt harus memuat `client_request_id`, attempt/snapshot/revision/payload hash yang dapat diverifikasi client; exact retry idempotent; same ID/different payload ditolak. Receipt status read-only masih perlu kontrak final jika response-loss lookup dipakai.
- Token optional vs required harus benar: jika tidak wajib, start tanpa token; jika wajib, validasi token. Uji semua kondisi.
- Server harus membedakan Web browser dari native Android client berdasarkan kredensial/kemampuan server-issued, bukan User-Agent/body yang dapat dipalsukan. Browser Android tidak boleh menjalankan Attempt; iOS Web diperbolehkan pada fase web.
- Media delivery harus memeriksa attempt/session/fence/snapshot, MIME allowlist, size, range/seek audio/video, response headers dan browser fetch/blob.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer privat → immutable snapshot privat → scoring terbukti end-to-end. ESSAY manual review.
- Kamera bukan syarat CBT standar. Browser deterrent bukan secure kiosk dan tidak dapat mencegah screenshot/perangkat kedua.

---

## 4. AUDIT — tugas Web Admin Madrasah (ADM-01 s.d. ADM-23)

Issue #20 adalah backlog eksekusi; checklist rinci ada pada `docs/AUDIT_CHECKLIST_WEB_ADMIN_LENGKAP_2026-11-23.md` dan `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`.

**Status audit matriks:** seluruh ADM-01–ADM-23 masih NOT VERIFIED atau PARTIAL. Tidak ada yang boleh disebut selesai/siap produksi.

### Kelompok ADM yang wajib ditutup
- ADM-01 Dashboard: metrik nyata sesuai periode aktif, aktivitas administratif, peringatan data, health instalasi; tidak ada angka dummy/total ujian/akses CBT.
- ADM-02–05 Identitas, profil, logo/favicon, branding login/header/footer, kop surat, preview dan cetak.
- ADM-06–08 Guru/Siswa/account lifecycle: CRUD lengkap, detail, status, reset kredensial, revoke sesi, histori dan audit.
- ADM-09–12 Import Guru/Siswa/Mapel dan export Guru/Siswa: template, versi, MIME/magic, ukuran, header, preview, duplikasi, konflik, konfirmasi, transaksi, idempotency, rollback, error report, audit. Jangan tertukar dengan import soal Guru.
- ADM-13–17 Tahun ajaran/semester, kelas MI 1–6/fase A–C, rombel dan histori perpindahan, mapel/Mulok, penugasan Guru, scope dan pencabutan assignment.
- ADM-18–19 Registry kurikulum/provenance/version/history, CP induk read-only, struktur/TP/ATP sesuai ownership, integrasi Kurmer+KBC/PM/8 dimensi hingga snapshot.
- ADM-20 Penugasan Guru pengawas sementara: saat ini BLOCKED sampai penanda ujian pusat kanonis dari owner. Jangan hardcode UTS/UAS/UM/SIMULASI TKA; assignment tidak memberi monitoring/room/roster/Attempt.
- ADM-21 Laporan administratif: rekap siswa/Guru/mapel/rombel/periode dan cetak/export; bukan nilai/hasil/analytics CBT Guru.
- ADM-22 Audit log: login/denied/CRUD/import/export/perubahan sensitif, before/after, aktor/waktu/alasan tanpa secret, UI filter dan pembatasan akses.
- ADM-23 Settings dan shell: timezone/format/bahasa/tema, profil Admin, branding, backup/restore, responsive/mobile drawer, keyboard/focus/contrast, asset lokal tanpa CDN inti.

### Batas Admin yang wajib ditegakkan di seluruh route/API/Livewire
Admin tidak boleh membuat/mengatur/menerbitkan ujian Guru, membuka Bank Soal, token, peserta, Attempt, jawaban, monitoring peserta, grading/nilai, force submit, reset Attempt, takeover atau menutup ujian. Menyembunyikan menu saja tidak cukup; direct route, Livewire mount/hydration/action, export/download dan API wajib ditolak.

---

## 5. AUDIT — tugas Web Guru

Master spesifikasi: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`, Issue #10 dan keputusan owner Issue #20.

### Fitur Guru yang perlu dipastikan end-to-end
1. Login exact-one GURU; Dashboard dan Penugasan Saya (read-only) sesuai assignment aktif.
2. Pembelajaran/Kurikulum: CP resmi yang tersedia, TP/ATP authoring, materi/topik, Panca Cinta, PM, 8 dimensi, versi/provenance dan assignment context.
3. Bank Soal: CRUD, versioning, draft/publish, opsi, kunci privat, media, Arabic/RTL/harakat/formula, duplicate/validation dan scope.
4. Import soal DOCX sebagai jalur utama: upload aman, parser profile, source locator, preview, correction, revalidation, confirm ke DRAFT, audit/idempotency/rollback, media private staging. XLSX question import dihapus/tidak dihidupkan kembali. PDF/OCR dan .doc deferred.
5. Tipe soal aktif sesuai kontrak: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, ESSAY. SHORT_ANSWER tetap BLOCKED. ESSAY manual review.
6. Asesmen dan Exam Builder Mode A: Guru membuat/mengatur/menerbitkan ujian sendiri tanpa approval Admin, hanya dalam assignment kelas/mapel/roster miliknya.
7. Roster peserta, immutable Question/Exam snapshot, token otomatis setelah publish, jadwal serentak berdasarkan start_at/end_at dan waktu server.
8. Monitoring Guru sendiri sesuai TeacherAccess; route, policy, Livewire dan API harus exact role + assignment. Tidak ada ExamSession/wave/session lifecycle.
9. Penilaian ESSAY, scoring otomatis untuk tipe yang didukung, reopen/recalculate dengan permission dan audit, finalisasi, hasil dan laporan/analytics yang sesuai.
10. Profil/pengaturan Guru, navigasi, empty/error/loading, responsive/accessibility.

### Status yang sudah pernah diuji tetapi terbatas
- PR #19 memiliki CI historis sukses untuk penghapusan XLSX dan beberapa Bank Soal/Exam Builder/result/monitoring suites (117 passed/514 assertions; 118 passed/520 assertions pada SHA yang disebut issue).
- Itu bukan bukti integrasi terkini PR #21, browser E2E atau UAT.
- DOCX masih NEEDS DESIGN/kontrak Agent 2 belum final; jangan tambah parser/dependency/UI yang mengasumsikan DTO.
- Monitoring/Proktor Mode B tidak boleh dikembangkan berdasar PR #18 yang menggunakan room/session dan hak yang bertentangan.
- Issue #14: mapping CP/TP/ATP/KBC harus versioned pada QuestionVersion dan disalin ke snapshot immutable; mapping question-level lama belum cukup.
- Issue #15: provenance resmi PM dan 8 dimensi belum boleh diisi data sintetis; jika sumber/versi belum terverifikasi, UI tampilkan unavailable.
- Issue #16 adalah spesifikasi XLSX question import lama yang sudah superseded oleh keputusan owner DOCX; jangan implementasikan.

---

## 6. AUDIT — tugas Web Siswa

Spesifikasi Web Siswa: branch `work/agent4-web-siswa-audit-20261122`, dokumen 755 baris. PR implementasi Agent 4 #13 masih Draft/unmerged.

### Fitur Siswa yang wajib
- Login identitas/NISN dan session policy; exact-one SISWA.
- Dashboard Ujian Saya, eligibility, detail/instruksi, token bila required.
- Admission berdasarkan Exam schedule/token/status/waktu server; Room bukan admission gate.
- Runner dengan question order/options yang konsisten untuk Attempt, timer server, navigasi dan tipe soal.
- Media privat yang hanya dapat dibaca melalui Attempt/snapshot/session/fence sah.
- Autosave durable; IndexedDB answers + sync queue atomik, request ID/payload/revision stabil, retry idempotent.
- Konflik 409 disimpan terpisah; tidak overwrite draft; storage failure/undurable state menahan submit.
- Reload/crash/offline/reconnect/response loss melanjutkan Attempt yang sama tanpa POST start/submit duplikat.
- Receipt diverifikasi penuh sebelum queue dihapus dan submit dibuka.
- Submit barrier, expiry race dan status SUBMITTED/EXPIRED yang server-authoritative.
- Hasil hanya ringkasan/feedback yang dipublikasikan; tanpa jawaban benar, kunci, explanation privat, rubrik atau siswa lain.
- Responsive/accessibility, mobile-first iOS Web; tidak klaim browser sebagai kiosk.

### Status Agent 4
Runner IndexedDB v2, durable queue, conflict archive, submit barrier, response-loss recovery, mobile-first layout dan deterrent copy/selection telah tercatat di PR #13. Namun:
- belum ada bukti browser-to-real-Laravel E2E lengkap pada branch integrasi;
- receipt/client_request_id, token optional, Attempt authorization/limit, media MIME/Range/seek, server-side platform enforcement dan lease/fencing masih gate;
- Chromium/mock/static pass bukan pengganti real Laravel/MySQL E2E;
- iOS Safari/Android Chrome fisik, multi-tab/multi-device, offline/reconnect, recovery, concurrency dan UAT belum lengkap.

---

## 7. DESAIN — urutan kerja koordinator berikutnya

1. Verifikasi exact branch HEAD/PR #21 dan CI setelah run #45181799176; jangan mengulang patch P0.1/P0.2.
2. Ambil daftar 84 PHPUnit failure dari log run #45181799176, kelompokkan per akar masalah dan bandingkan dengan keputusan owner. Prioritaskan P0.3–P0.7.
3. Pastikan test ExamSnapshot/AnswerKeyLeakage benar-benar memakai assignment/registration/version/context kanonis; bila masih gagal, audit source/helper dan bukan mengulang patch yang sama.
4. Tutup route/policy exact role dan authorization; jangan mengubah test untuk membuka akses Admin atau role legacy.
5. Setelah full PHPUnit lulus, jalankan two-connection MySQL concurrency, auth-session/fencing, token, receipt, media dan expiry/submit race pada disposable DB.
6. Buat matriks requirement Admin/Guru/Siswa route → Livewire/controller → service/model → policy → schema → test → exact CI → status.
7. Selesaikan satu kontrak backend kurikulum/asesmen untuk ketiga portal; lanjutkan historical report read model tanpa menghapus model legacy/histori sebelum rencana migrasi.
8. Tutup browser-to-Laravel E2E ketiga portal dan UAT.
9. Baru rencanakan fresh install/upgrade/backup-restore dan deployment VPS.
10. ZIP generik satu instalasi madrasah disiapkan setelah seluruh acceptance gate selesai dan owner menyetujui; berisi source/dependency/config/resource/README aaPanel, tanpa backup/temp/secret.

---

## 8. IMPLEMENTASI — aktivitas audit ini

- Tidak ada source runtime, migration, schema, DB, VPS atau PR yang diubah.
- Branch laporan baru dibuat dari branch eksekusi: `audit/coordinator-github-scope-2026-11-24`.
- Laporan ini adalah dokumentasi audit koordinasi saja.
- Tidak ada backup source karena tidak ada source runtime yang diedit.

## 9. PENGUJIAN — bukti dan batas

- Diperiksa GitHub master specs Admin/Guru/Siswa, Issue #8/#9/#10/#12/#14/#15/#16/#20, PR #13/#18/#19/#21/#22, handover, checkpoint, matriks ADM, laporan CI dan log run #45181799176.
- Run #45181799176: setup MySQL 8.0.46/Composer/migration/focused smoke PASS; full PHPUnit FAIL 84/434/1,757.
- CI #45220055382 PR #22: 518/0/2,189 PASS pada SHA/branch terpisah; bukan bukti PR #21 lulus.
- Browser E2E real-Laravel, concurrency/fencing full, fresh-install/upgrade/restore, UAT, deployment VPS dan ZIP distribusi: NOT VERIFIED / NOT RUN.

## 10. NEXT ACTION

Lanjutkan P0 PHPUnit pada branch execution dari exact failure log terbaru. Jangan ulang P0.1/P0.2, jangan merge PR #22/#13/#18/#19/#21, jangan menjalankan migration produksi, jangan deploy, dan jangan membuat ZIP final sebelum acceptance serta persetujuan owner.

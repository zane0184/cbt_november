# KOREKSI KOORDINASI DAN RENCANA PEMULIHAN CBT MADRASAH
Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/p1-seven-targets-20261124`
HEAD dokumentasi saat audit: `2598af967b22a8fa032cc910bb32230804a15390`
Source aplikasi terakhir yang teridentifikasi: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`
PR: #23 — OPEN / DRAFT / NOT MERGED

Dokumen ini adalah koreksi koordinasi aktif. Dokumen historis tidak dihapus atau ditulis ulang. Jika urutan kerja pada checkpoint lama menyempitkan seluruh pekerjaan menjadi P1-ID-03A, gunakan dokumen ini untuk mengembalikan mandat tujuh sasaran. Keputusan owner terbaru tetap lebih tinggi daripada dokumen ini.

## 1. AUDIT — temuan yang terverifikasi

### 1.1 Kesalahan koordinasi yang diakui
- Instruksi eksekutor sebelumnya terlalu menyempit pada pemulihan workspace/toolchain dan P1-ID-03A.
- P1-ID-03A adalah satu subpekerjaan integritas identitas, bukan pengganti mandat keseluruhan penyelesaian Web CBT.
- Karena penyempitan tersebut, kelanjutan audit/implementasi Admin, Guru, Siswa, kurikulum, staging, dan ZIP berisiko tertunda tanpa alasan yang cukup.
- Ini adalah kesalahan prioritas/koordinasi yang diakui. Tidak ada bukti yang cukup untuk menyatakan prompt tersebut menghapus atau merusak workspace lokal.

### 1.2 Pemeriksaan GitHub
- PR #23 tetap OPEN / DRAFT / NOT MERGED.
- Branch kerja: `work/p1-seven-targets-20261124`.
- HEAD yang diperiksa: `2598af967b22a8fa032cc910bb32230804a15390`.
- Base: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`.
- Compare source aplikasi `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050` ke HEAD `2598af9...`: 11 commit, 0 behind; hanya dua file berubah:
  - `docs/REPORT_P1_IMPLEMENTATION_UNBLOCK_2026-11-24.md`
  - `docs/CHECKPOINT_P1_IMPLEMENTATION_UNBLOCK_2026-11-24.md`
- Dengan demikian, rentang 11 commit tersebut adalah dokumentasi saja; tidak membuktikan source aplikasi rusak.
- Workflow run yang terkait langsung dengan SHA dokumentasi HEAD tidak dikembalikan oleh endpoint PR-workflow yang diperiksa. Jangan mengklaim CI exact HEAD dokumentasi.
- Bukti CI source aplikasi yang tersedia: run #45347315210 pada exact source SHA `cd2b6ee...`, PHP 8.3 + disposable MySQL 8, focused 56 passed / 225 assertions, full PHPUnit 534 passed / 2,265 assertions. Bukti ini hanya berlaku untuk SHA source tersebut.
- ZIP workflow #45347315217 pada SHA historis `afd9efd4...` dan #45367821057 pada SHA `3106172e...` hanya membuktikan build/archive; bukan acceptance ZIP final.

### 1.3 Workspace dan batas kemampuan saat audit
- Workspace kanonis `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan pada container audit.
- Lingkungan audit yang tercatat: PHP 8.4.24 (bukan runtime validasi proyek PHP 8.3), Composer tidak tersedia, PDO driver kosong, MySQL/Docker/Podman tidak tersedia, dan DNS/HTTPS GitHub melalui terminal gagal.
- GitHub connector dapat membaca source dan metadata, tetapi bukan checkout lokal writable. Snapshot distribusi tanpa `.git` dan tanpa seluruh tests/backup bukan pengganti checkout.
- Tidak ada bukti perubahan terhadap database produksi, VPS, deployment, atau penghapusan source oleh audit sebelumnya.

### 1.4 Kontrak owner yang mengikat
- Instalasi dan database akademik terpisah untuk setiap madrasah; tidak ada shared academic DB atau akses akademik lintas madrasah.
- Portal aktif: Admin Madrasah, Guru, Siswa. Android native dan Web Super Admin ditunda sampai Juni 2027.
- Tidak ada portal/role permanen Operator, Kamad, atau Proktor. Proktor adalah capability/penugasan sementara Guru.
- Guru berwenang mengelola bank soal dan membuat/mengatur/menerbitkan ujian sesuai assignment tanpa persetujuan Admin.
- Admin menangani administrasi, master data, sinkronisasi, monitoring administratif, dan dukungan teknis; tidak mengambil alih authoring/operasi ujian reguler Guru.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah satu ekosistem berversi/provenance.
- CP resmi read-only; TP/ATP dibuat Guru sesuai assignment.
- Taksonomi asesmen tidak boleh mengunci UTS/UAS/UM/Simulasi TKA sebagai enum/kategori tertutup. TKA eksternal bukan kategori CBT internal default.
- DOCX prioritas import soal; jangan menghidupkan kembali XLSX question importer.
- SHORT_ANSWER tetap BLOCKED sampai accepted answers, persistence, immutable snapshot, dan scoring E2E diterima. ESSAY manual.
- Room bukan admission gate. Tidak boleh ada ExamSession/SessionRoom/session_number/wave/session lifecycle sebagai syarat admission. Proktor Mode B tetap BLOCKED sampai marker ujian pusat eksplisit dan kontrak Exam+Room/permission/negative tests disetujui.
- Identitas login final owner 24 November mengesampingkan semua dokumen lama:
  - Admin Madrasah: username.
  - Guru PNS/PPPK: NIP wajib tepat 18 digit.
  - Guru Honorer: NIK wajib tepat 16 digit.
  - Siswa: NISN wajib tepat 10 digit.
  - Guru tidak boleh fallback ke username atau NUPTK.
  - String, leading zero dipertahankan; unik dalam instalasi; tidak backfill/tebak legacy; cegah PII bocor.
- `tenant_id`/scope legacy tidak boleh dihapus massal. Pertahankan sebagai defense-in-depth/kompatibilitas lokal setelah audit.

## 2. DESAIN — pemulihan mandat dan urutan kerja

Pekerjaan harus kembali ke tujuh sasaran. P1-ID-03A tetap salah satu security/integrity workstream, tetapi tidak menghentikan audit paralel seluruh portal.

### Sasaran 1 — Backend, autentikasi, keamanan, integritas
- Verifikasi exact-role, permission, assignment, local-install scope, transaksi, FK, locking, idempotensi, audit, waktu server, payload redaction, dan lifecycle Attempt/Answer/Score.
- Selesaikan P1-ID-03A: schema preflight, relasi tenant/user/profile, role/cardinality, kategori eksklusif, collision lintas kategori, immutability test, benchmark/fixture, inventory writer, recursive PII redaction dan bypass audit.
- P1-ID-03B (database conditional enforcement) tetap BLOCKED sampai desain disetujui dan disposable MySQL 8/SQLite diuji. Jangan menjalankan migration produksi.
- Perbaiki fixture/test hanya setelah source dan akar kegagalannya dibuktikan; jangan melonggarkan tenant guard/policy.

### Sasaran 2 — Web Admin ADM-01–23
- Lengkapi matriks setiap ADM requirement terhadap route/UI, Livewire/controller/service/model, policy/middleware, schema, audit, dan test.
- Prioritaskan lifecycle akun, siswa/riwayat rombel, import/export XLSX/CSV Guru/Siswa, tahun ajaran/semester, kelas/rombel, mapel/assignment, profil/branding/kop madrasah, laporan/cetak, pengaturan, audit UI, backup/restore.
- Jangan mengimplementasikan ulang item yang sudah ada. Jangan memberi Admin hak authoring/monitoring ujian yang dilarang.

### Sasaran 3 — Web Guru
- Audit end-to-end assignment, CP/TP/ATP, bank soal, DOCX import, media, versioning, taxonomy asesmen, Exam Builder, publish/token/jadwal, scoring, essay review, hasil/analitik.
- Buktikan snapshot immutable dan provenance kurikulum sampai hasil.
- Jangan hidupkan XLSX question import. SHORT_ANSWER tetap fail-closed sampai acceptance.

### Sasaran 4 — Web Siswa
- Pertahankan runner Agent 4 yang sudah ada; jangan copy ulang.
- Bandingkan blob runner Agent 4 dengan branch integrasi dan integrasikan hanya gap yang dibuktikan.
- Uji browser ke Laravel/MySQL nyata: admission, server timer, token, payload redaction, media auth, durable queue, idempotency/receipt, conflict/recovery/offline, submit/expiry race, lease/fencing dan multi-device.
- Mock/browser-only test bukan bukti integrasi server atau kesiapan operasional.

### Sasaran 5 — Ekosistem Kurikulum Merdeka + KBC
- Telusuri provenance resmi, versi, checksum, tanggal berlaku, mapping CP/TP/ATP/Panca Cinta/Pembelajaran Mendalam/8 dimensi ke QuestionVersion, Assessment, immutable Exam snapshot, score, dan laporan historis.
- Jangan membuat konten CP resmi atau konten kurikulum sintetis seolah-olah resmi/terverifikasi.

### Sasaran 6 — Staging VPS/aaPanel
- Setelah fitur dan test cukup, rencanakan staging terisolasi dengan database kosong/khusus, upload/extract, document root `backend-laravel/public`, .env khusus instalasi, storage, queue/scheduler, backup/restore.
- Jangan meminta/menaruh kredensial rahasia di repo/chat.
- Tidak ada production migration/deployment tanpa preflight, backup, rencana rollback, dan persetujuan owner.

### Sasaran 7 — ZIP distribusi generik
- Satu ZIP generik untuk instalasi mandiri tiap madrasah.
- Tanpa backup internal, file sementara, secret, atau kredensial.
- Sertakan seluruh source/dependency yang diperlukan, struktur ekstraksi Windows Explorer, konfigurasi contoh dan README.
- Verifikasi manifest, checksum, forbidden paths, ekstraksi bersih, fresh install, upgrade, backup/restore dan acceptance.
- Build workflow SUCCESS bukan persetujuan distribusi.

### Urutan koordinasi
1. Bekukan PR #23 sebagai Draft; jangan merge.
2. Buat matriks lintas tujuh sasaran dari tiga master spec dan seluruh owner addendum terbaru.
3. Rekonsiliasi setiap item dengan source aktual dan PR/branch pemiliknya; klasifikasikan DONE / PARTIAL / FAIL / NOT IMPLEMENTED / NOT VERIFIED.
4. Tutup blocker keamanan P0 yang nyata dan lakukan P1-ID-03A tanpa menghentikan audit/pekerjaan portal lain yang independen.
5. Lanjutkan per target dengan satu akar masalah per patch, backup byte-exact sebelum edit, focused test, full suite, browser E2E, dan disposable MySQL.
6. Review hasil setiap tahap dan perbarui laporan/checkpoint.
7. Staging dan ZIP hanya setelah seluruh acceptance gate dan UAT terpenuhi.

## 3. IMPLEMENTASI — yang benar-benar dilakukan pada koreksi ini
- Dokumen koreksi koordinasi ini ditambahkan sebagai file baru.
- Tidak ada file source aplikasi, migration, workflow, dependency, konfigurasi, atau database yang diubah.
- Tidak ada backup source baru karena tidak ada source existing yang diedit.
- Tidak ada perubahan PR state, merge, deployment, atau production operation.
- Dokumen historis dipertahankan; dokumen ini mengoreksi instruksi kerja aktif, bukan menghapus sejarah.

## 4. PENGUJIAN — bukti dan batas
- GitHub branch dan compare diperiksa ulang.
- Compare `cd2b6ee...` ke `2598af9...` menunjukkan hanya dua file dokumentasi berubah.
- File laporan/checkpoint P1-ID-03A, addendum owner identitas, addendum refinement, checkpoint koordinator, laporan audit identitas, dan checkpoint tujuh sasaran dibaca ulang.
- CI source SHA `cd2b6ee...`: run #45347315210 SUCCESS, focused 56/225 dan full PHPUnit 534/2,265 pada PHP 8.3 + disposable MySQL 8.
- Tidak ada test baru dijalankan oleh koreksi dokumentasi ini.
- Exact-HEAD CI untuk HEAD dokumentasi `2598af9...`: NOT VERIFIED.
- Workspace lokal, browser E2E baru, staging, backup/restore, load/security test, UAT, production DB: NOT RUN / NOT VERIFIED.

## 5. STOP CONDITIONS
- Jangan menyatakan workspace pulih sampai checkout Git writable dengan .git, source, tests dan backup terverifikasi.
- Jangan mengklaim source rusak atau hilang akibat prompt tanpa bukti.
- Jangan menjalankan migration pada DB produksi.
- Jangan merge PR #23, deploy, atau menyatakan ZIP final siap tanpa persetujuan dan acceptance.
- Jangan mengubah kontrak identitas login terbaru atau keputusan portal/kurikulum/Proktor.
- Jangan membatasi kembali seluruh pekerjaan menjadi P1-ID-03A saja.

**Status setelah koreksi:** kesalahan penyempitan koordinasi didokumentasikan dan mandat tujuh sasaran dipulihkan. Source aplikasi belum diubah pada tahap ini. Proyek tetap belum siap produksi dan belum siap distribusi.

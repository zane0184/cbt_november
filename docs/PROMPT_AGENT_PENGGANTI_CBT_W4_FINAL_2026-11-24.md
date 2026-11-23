# PROMPT AGENT PENGGANTI CBT MADRASAH — W4 FINAL
Tanggal: 24 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch kerja: `work/solo-web-cbt-completion-20261123-execution`
Latest HEAD saat prompt ini disusun: `883f681fb42cfef9b6d7a8f73613c89e841cfe0f`
Source commit yang memuat Audit Events: `f4397b8999ddf31a96d4d04b15bcfac4473b5203`
Owner: pengguna — pemegang keputusan tunggal
Mandat: **TERUSKAN PEKERJAAN YANG BELUM SELESAI. JANGAN MENGULANG PEKERJAAN SELESAI.**

> ## PERINTAH UTAMA — LANJUTKAN, BUKAN MENGULANG
> Aplikasi CBT Madrasah sudah berjalan di https://cbt.min15jakarta.sch.id/login. ZIP aplikasi pernah dibuat dan diinstal di VPS; owner menyatakan VPS dan konfigurasi sudah melalui verifikasi ketat serta lancar.
> Fokus proyek sekarang adalah melengkapi fitur web yang telah ditetapkan tetapi belum lengkap/berfungsi, menguji alur end-to-end, lalu membuat satu ZIP generik lengkap yang siap diunggah dan diekstrak di VPS setelah semua acceptance gate terpenuhi.
>
> Dilarang membangun ulang, mengulang patch/test yang sudah terbukti selesai, menanyakan spesifikasi VPS kembali, atau berhenti setelah audit. Tugas agent adalah meneruskan backlog yang belum selesai dari source dan checkpoint aktual.

---

## 1. STATUS TERBARU YANG SUDAH DIVERIFIKASI DI GITHUB

### 1.1 Posisi branch
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- Latest HEAD saat prompt dibuat: `883f681fb42cfef9b6d7a8f73613c89e841cfe0f` (commit dokumentasi "add W3 CI retry note").
- Source commit fitur Audit Events: `f4397b8999ddf31a96d4d04b15bcfac4473b5203`.
- Verifikasi awal setiap sesi: fetch HEAD actual, Actions terbaru, checkpoint/laporan baru, dan perubahan setelah titik tersebut. Jangan menganggap SHA prompt tetap HEAD di masa depan.

### 1.2 CI terbaru — Audit Events SUDAH PASS
**GitHub Actions #1734 — SUCCESS**, exact source SHA `f4397b8999ddf31a96d4d04b15bcfac4473b5203`, run ID `45278477028`, attempt 2:
https://github.com/zane0184/cbt_november/actions/runs/45278477028

Hasil job `laravel-mysql`:
- PHP 8.3: berhasil.
- Disposable MySQL 8.0.46: berhasil.
- Login/tenant focused smoke: SUCCESS.
- Admin Livewire reauthorization: SUCCESS.
- Teacher result reauthorization: SUCCESS.
- Full PHPUnit: **536 passed / 2,250 assertions / 0 failed**.
- Post-check InnoDB fail-closed: SUCCESS.
- Cleanup: SUCCESS.
- Job conclusion: SUCCESS.
Run attempt pertama sempat dibatalkan oleh concurrency; attempt 2 adalah hasil final yang berhasil. Jangan menyebut run #1734 gagal atau pending.

**Audit Events pada patch W3 sekarang teruji oleh full suite #1734.** Test di `AdminUiVerificationTest` meliputi pembuatan akun Guru/Siswa, rombel, dan mata pelajaran serta assertions audit yang ditambahkan. Jangan mengulang koreksi sintaks yang sudah selesai atau patch audit event yang sama.

### 1.3 CI sebelum W3
- Actions #1722 SUCCESS pada SHA `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`: 536 passed / 2,231 assertions / 0 failed; InnoDB post-check PASS.
- Run #1714 pada SHA `e894d3f...` mempunyai full PHPUnit 536 / 2,231 lulus tetapi workflow gagal pada post-check karena quoting. Itu sudah diperbaiki kemudian dan diverifikasi #1722.
- Baseline remediation 42 PHPUnit failure telah CLOSED; jangan ulangi triage.

---

## 2. PEKERJAAN YANG SUDAH SELESAI — JANGAN DIULANG

### 2.1 Remediasi PHPUnit baseline
- 42 failure baseline sudah diselesaikan.
- Bukti baseline lama: 518 passed / 2,177 assertions / 0 failed, SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
- Jangan ulang triage/patch fixture/helper terkait.

### 2.2 W1 Admin/Guru Livewire reauthorization
Patch reauthorization pada komponen yang tercatat di laporan W1 telah lulus CI #1662/#1663 dan kemudian regresi focused kembali lulus pada #1722/#1734.
Jangan membuat ulang patch authorization yang sama. Buka kembali hanya dengan bukti perubahan source baru/regresi/security defect atau permintaan owner.

### 2.3 CI InnoDB post-check
- Workflow `.github/workflows/tenant-backend-ci.yml` sudah diperbaiki menggunakan prepared statement.
- Backup byte-exact tersimpan di `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-postcheck-quote-fix`.
- Patch commit `1b25c3bbed07fba6def2dfe869d686a8559be1cc`.
- CI #1722 dan #1734 sudah membuktikan post-check PASS.
**Jangan ulang patch atau mengedit workflow ini tanpa regresi konkret.**

### 2.4 Profil dasar Admin Madrasah
Sudah diimplementasikan dan diuji:
- `backend-laravel/app/Livewire/Admin/MadrasahProfile.php`
- `backend-laravel/resources/views/livewire/admin/madrasah-profile.blade.php`
- Route `admin.profile`, sidebar, audit log.
- Field dasar: nama, NSM, NPSN, alamat, telepon, email.
- Access exact `ADMIN_MADRASAH` + `tenant.manage`; tenant dari authenticated user's tenant_id; System Tenant ditolak; installation code/status immutable.
Tiga test profil lulus pada full PHPUnit #1714/#1722/#1734.
**Jangan membuat ulang route, component, view, form profil dasar, atau test yang sama. Kerjakan hanya fitur profil lanjutan yang benar-benar belum ada.**

### 2.5 W3 Audit Events Admin — IMPLEMENTED & TESTED
Patch source pada commit `f4397b8999ddf31a96d4d04b15bcfac4473b5203`:
- `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
  - menulis audit events untuk membuat rombel dan mata pelajaran.
  - backup byte-exact: `backend-laravel/docs/backup/admin-academic-audit-2026-11-24/AcademicManagement.php.before`
  - backup SHA: `779fdf04f9067ee0b00f791014de3d803302f453`.
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
  - menulis audit event saat membuat akun lokal Guru/Siswa.
  - backup byte-exact: `backend-laravel/docs/backup/admin-user-audit-2026-11-24/UserManagement.php.before`
  - backup SHA: `b0868c101f5b98581263d6cfd3cec687eb9219b8`.
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
  - assertions audit rombel/mapel dan akun Guru/Siswa, termasuk metadata dan redaction field sensitif.
  - backup sebelum penambahan test dan backup pra-koreksi sintaks tercatat dalam laporan W3.
  - test source final blob SHA: `17be4815c31ca5c4fc8c7629c80302ce64895f46`.
- Audit event dilakukan dalam transaksi mutasi data yang sama.
- Event: `CREATE_ACADEMIC_ROMBEL`, `CREATE_ACADEMIC_SUBJECT`, `CREATE_LOCAL_USER`.
- Password, NIP, NIK, NISN, token, secret tidak dimasukkan ke payload audit.
- Tidak ada migration/schema/route/role change.
- Backup semua file existing sudah diverifikasi identik dengan source sebelum patch.

**Verifikasi test terkini:** CI #1734 full suite 536 passed / 2,250 assertions / 0 failed pada exact source SHA #f4397b8. Jangan membuat ulang tiga event tersebut, jangan mengulang test yang sama, dan jangan membuat duplicate AuditService calls. Lanjutkan audit log untuk operasi administrasi lain yang masih diwajibkan spesifikasi dan belum ada, setelah mencari implementasi existing dan acceptance criteria.

Laporan rinci:
`docs/REPORT_W3_ADMIN_AUDIT_EVENTS_2026-11-24.md`
Checkpoint:
`docs/CHECKPOINT_W3_ADMIN_AUDIT_EVENTS_2026-11-24.md`

---

## 3. BACKLOG AKTIF — KERJAKAN GAP NYATA SAJA

Status di bawah adalah snapshot laporan W3. Verifikasi source actual/Issue #12/#20/master specs sebelum menentukan subitem DONE. Jangan scan ulang seluruh repo; lakukan gap audit per task yang dipilih.

### 3.1 Admin: lifecycle Guru/Siswa
Belum lengkap:
- edit/update profil Guru/Siswa;
- nonaktif/aktif, arsip, penghapusan sesuai retention policy;
- perubahan role/assignment yang aman;
- validasi duplikasi identitas/username dan tenant/install scope;
- reset kredensial yang aman dengan audit;
- histori perubahan yang sesuai acceptance.
Sebelum patch, periksa UserManagement component/service/model/route/policies/tests yang ada. Jangan mengulang create account CRUD yang sudah ada; hanya tambah lifecycle yang missing.

### 3.2 Administrasi akademik
Belum lengkap sesuai laporan:
- lifecycle tahun ajaran dan semester;
- historis kelas/rombel;
- pindah rombel/siswa antar kelas dengan histori;
- promosi, tinggal kelas, kelulusan dan alumni;
- mapping mapel/assignment yang menjaga histori akademik.
Jangan buat schema paralel. Audit model, schema, migration, foreign keys, service, transaction, API, dan acceptance Issue #12/#20 terlebih dahulu. Data histori tidak boleh dihapus/ditimpa.

### 3.3 Import/export administrasi
Belum lengkap:
- import/export Guru/Siswa dan administrasi sesuai master spec;
- preview, validation errors per row, dry-run, confirmation, result summary, duplicate handling, idempotency;
- tenant scope dan permission exact;
- file security, MIME/size, storage, expiry dan audit log.
XLSX question-bank import tetap dilarang. DOCX untuk bank soal tetap menjadi arah utama, tetapi kontrak DOCX parser/DTO/staging/ImportBatch dilaporkan masih BLOCKED: jangan mengarang format kontrak atau mengimplementasikan jalur paralel. Lanjutkan hanya bagian administratif yang kontraknya jelas, dan dokumentasikan blocker terpisah.

### 3.4 Profil/branding madrasah (gap setelah profil dasar)
Belum lengkap:
- metadata tambahan sesuai spesifikasi, bila benar-benar tidak ada di schema: jenjang/status negeri-swasta/wilayah rinci/website/media sosial/metadata Kepala Madrasah.
- logo upload/replace/delete, favicon, branding login dan pengiriman asset privat.
- kop surat fleksibel serta preview cetak.
Sebelum migration, audit schema aktual dan seluruh penggunaan Tenant. Pilih desain backward-compatible; jangan menambahkan kolom duplikat/field asumsi. Storage harus tenant/install scoped, tidak boleh arbitrary public path, path traversal, MIME spoofing, SVG script, atau leakage cache. Uji upload, replace/delete, auth delivery, login layout, report/print output. Jangan ulang profil dasar.

### 3.5 Web Guru end-to-end
Masih perlu acceptance fungsional menyeluruh:
`assignment → kurikulum/CP/TP/ATP → materi/topik → bank soal/versioning/media → DOCX import → asesmen/ujian → snapshot immutable → peserta → publish/token/jadwal → pelaksanaan → scoring Essay → hasil/laporan/analytics`.
Patch reauthorization Guru result yang sudah teruji jangan diulang. Jangan menganggap analytics lulus dari mock-based auth test. Verifikasi per assignment, direct route/API, publish, server timer, snapshot, token, audit log, hasil dan redaction yang sesuai.

### 3.6 Web Siswa end-to-end
Masih perlu integrasi browser ke Laravel nyata:
- exact role/session, dashboard, eligibility;
- token/jadwal/server-time/admission, immutable snapshot;
- runner soal dan media;
- autosave/revision/receipt/client_request_id/idempotent retry;
- IndexedDB, reload/crash, offline/reconnect, multi-tab/stale conflicts;
- expiry/submit barrier/recovery/result;
- server-side redaction (CP, answer key, is_correct, explanation privat, rubrik internal tidak bocor);
- native Android policy: Android browser dilarang memulai/melanjutkan attempt, namun implementasi Android ditunda hingga Juni 2027.
Jangan hanya melihat keberadaan IndexedDB/receipt; E2E harus berinteraksi dengan backend sungguhan.

### 3.7 Kurikulum Merdeka + KBC dan DOCX
Laporan menandai dependency kontrak backend:
- Kurikulum Merdeka + KBC satu kesatuan (bukan kurikulum terpisah).
- CP resmi read-only, provenance dan version terverifikasi.
- TP/ATP guru sesuai assignment.
- Panca Cinta, Pembelajaran Mendalam, delapan dimensi profil lulusan.
- Integrasi hingga materi/topik, QuestionVersion, immutable exam snapshot, hasil dan laporan.
- Jangan buat DTO/schema/enums paralel.
- Jangan mengarang referensi resmi atau marker.
- DOCX parser/DTO/staging/ImportBatch masih reported BLOCKED: lanjutkan hanya setelah kontrak kanonis ditemukan/ditetapkan; jangan membuat kontrak baru sendiri.

### 3.8 Audit/backup/restore/security
Masih perlu:
- audit event untuk operasi lain yang ditetapkan spesifikasi (edit, deactivate/archive, move rombel, promotion/graduation, import/export, sensitive configuration) hanya jika benar belum ada;
- health, backup dan restore yang dibuktikan melalui restore test;
- RBAC, role exact, assignment, direct route/API/IDOR, file delivery/export;
- transactions/locking/concurrency/idempotency/race conditions;
- fresh install dan upgrade pada disposable MySQL 8/InnoDB;
- browser E2E, responsive/accessibility, UAT.
Audit event CREATE untuk akun/rombel/mapel sudah selesai—jangan ulangi. Tambahkan operasi lain berdasarkan acceptance evidence.

### 3.9 Blocker yang tidak boleh diterobos
- ADM-20/ujian pusat: BLOCKED sampai canonical eligibility marker dan aktor penetap diputuskan owner. Jangan simpulkan dari `assessment_origin=EXTERNAL`.
- DOCX: ikuti kontrak parser/DTO/staging/ImportBatch kanonis; jangan mengarang.
- SHORT_ANSWER: tetap fail-closed sampai accepted answer privat + immutable snapshot + scoring E2E.
- Device Lease/fencing: belum production-ready sampai integration, multi-device, concurrency dan recovery tests.
- Android dan Web Super Admin: DEFERRED sampai Juni 2027.

---

## 4. KEPUTUSAN OWNER FINAL — JANGAN DITANYAKAN ULANG

- Satu ZIP generik untuk semua madrasah, setiap madrasah punya instalasi dan database sendiri; tidak ada shared academic multi-tenant database.
- Role lokal: `ADMIN_MADRASAH`, `GURU`, `SISWA`.
- Kamad/Operator bukan portal/role aktif; tugas operator dilebur ke Admin.
- Proktor hanya assignment/menu sementara pada Guru, tidak ada role proktor permanen.
- Guru mengelola soal/asesmen/ujian/jadwal/token/publish sesuai assignment tanpa approval Admin.
- Admin mengelola administrasi/akun/master akademik/sinkronisasi/dukungan/audit/settings/backup/restore, tetapi tidak authoring/operasi ujian Guru.
- Kurikulum Merdeka + KBC/Panca Cinta satu integrasi dengan Pembelajaran Mendalam dan delapan dimensi profil lulusan. CP resmi read-only dengan provenance/version; Guru membangun TP/ATP sesuai assignment.
- Jangan hardcode enum UTS/UAS/UM/TKA. TKA dipisahkan dari asesmen internal.
- DOCX adalah arah utama import soal; XLSX question import tidak boleh diaktifkan.
- Waktu server acuan resmi; token otomatis setelah publish; ujian hanya mulai sesuai jadwal.
- Attempt/Answer/Score/lease/fencing/idempotency/offline sync/recovery harus konsisten.
- Kamera bukan syarat CBT standar.
- Android native dan Web Super Admin ditunda sampai Juni 2027.
- Tidak ada production migration/deployment/merge PR/data production change tanpa approval eksplisit owner.
- VPS sudah dinyatakan terverifikasi dan lancar. Jangan tanya spesifikasi VPS ulang.

---

## 5. PROTOKOL IMPLEMENTASI DAN PENGUJIAN

Untuk setiap file existing:
1. Fetch exact current branch file dan catat blob SHA.
2. Inspeksi hanya jalur eksekusi terkait: caller, Livewire action, route, policy, service, model/schema, test.
3. Cari test/fitur/backup existing supaya tidak ada implementasi duplikat.
4. Backup byte-exact ke GitHub sebelum edit; fetch balik dan pastikan SHA/content sama.
5. Patch sekecil mungkin. Jangan mengubah source lain yang tidak perlu.
6. Jalankan focused tests yang relevan dahulu.
7. Full PHPUnit hanya jika perubahan luas atau acceptance gate membutuhkan, bukan karena agent berganti.
8. Gunakan PHP 8.3 + disposable MySQL 8/InnoDB; jangan menggunakan database produksi.
9. Catat exact commit SHA, Actions run URL, pass/fail/assertions, serta setiap skip.
10. Jangan menyebut implementasi lulus sebelum test pada exact source SHA benar-benar PASS.
11. Jangan mengurangi authorization atau mengubah expected tests hanya untuk membuat pipeline hijau.
12. Jangan melakukan migration, deploy, upload atau test destruktif ke website live.

---

## 6. TARGET ZIP DISTRIBUSI FINAL

Jangan membuat ZIP final sekarang. Buat hanya setelah seluruh fitur web dan acceptance gates terpenuhi.

Paket akhir harus:
- satu ZIP lengkap dari source commit yang ditetapkan;
- seluruh source Backend Laravel + Web Admin/Guru/Siswa;
- dependencies/lock dan assets yang dibutuhkan;
- installer, migration dan upgrade mechanism yang diuji;
- contoh .env tanpa secret;
- README Indonesia tentang upload/extract via aaPanel, konfigurasi runtime dari backup pemilik, install/upgrade, storage, permission, cache, queue/scheduler bila relevan, health, backup/restore dan troubleshooting;
- file manifest dan SHA-256 checksum;
- tanpa .git, .env produksi, secret, log, cache/temp, backup internal dan file personal.
Verifikasi ekstraksi bersih, folder tidak nested ganda, dependency reproducible, fresh install/upgrade disposable MySQL 8/InnoDB, route/assets/storage/config/permission/health dan smoke/E2E portal.

Pisahkan:
- SIAP DIDISTRIBUSIKAN KE VPS = ZIP dan acceptance distribusi lulus.
- SIAP PRODUKSI = baru setelah deployment yang disetujui, integrasi/security/stability/recovery test dan UAT madrasah lulus.

---

## 7. LAPORAN DAN CHECKPOINT GITHUB

Setiap tahap wajib menyimpan:
- **AUDIT** — temuan source aktual, risiko, gap dan bukti.
- **DESAIN** — kontrak dan alasan.
- **IMPLEMENTASI** — file berubah, backup path/SHA, commit.
- **PENGUJIAN** — exact SHA, test/Actions, PHP/MySQL, hasil/limitations.
- **BACKLOG** — DONE/IN PROGRESS/BLOCKED/NOT STARTED/DEFERRED.
- **NEXT** — pekerjaan konkret berikutnya.
- **HANDOVER** — pekerjaan selesai yang tidak boleh diulang.

Update checkpoint dan `AGENT_START_HERE.md` setelah status berubah. Jangan hanya menulis laporan di chat.

---

## 8. EKSEKUSI PERTAMA AGENT BERIKUTNYA

1. Baca prompt ini, `AGENT_START_HERE.md`, laporan/checkpoint W3 Audit Events, Issue #12/#20 dan master spec yang terkait.
2. Fetch HEAD aktual. Snapshot prompt ini `883f681...`, tetapi periksa apakah ada commit setelahnya.
3. Verifikasi run #1734 final SUCCESS (attempt 2) pada exact source SHA `f4397b8...`; jangan rerun atau patch audit event yang sudah lulus.
4. Catat bahwa audit CREATE akun Guru/Siswa, rombel dan mapel berstatus IMPLEMENTED & TESTED.
5. Langsung pilih backlog berikutnya yang tidak blocked: lifecycle Guru/Siswa atau akademik tahun ajaran/semester/histori rombel. Periksa issue acceptance dan source terkait sebelum patch.
6. Buat focused plan minimal, backup byte-exact, implementasi, focused tests, laporan/checkpoint.
7. Teruskan backlog Web Admin, integrasi Web Guru/Siswa, kurikulum/asesmen, hardening dan acceptance.
8. Jangan meminta owner mengulang keputusan. Jangan menanyakan VPS. Jangan berhenti setelah audit.
9. ZIP final hanya setelah acceptance seluruh fitur wajib.

**JANGAN MENGULANG. JANGAN MEMBANGUN ULANG. LANJUTKAN DARI SOURCE TERAKHIR DAN SELESAIKAN FITUR YANG MASIH KURANG.**

Status saat prompt W4 ini dibuat: **CI GREEN; W3 AUDIT EVENTS IMPLEMENTED & TESTED; WEB FEATURES STILL IN PROGRESS; ZIP NOT READY FOR DISTRIBUTION.**

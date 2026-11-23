# ADDENDUM ARAHAN OWNER — ARSITEKTUR INSTALASI, ROLE, PROKTOR, DAN PRIORITAS

Tanggal: 23 November 2026  
Repository: `zane0184/cbt_november`  
Status: keputusan owner terbaru dan rekonsiliasi terhadap master spesifikasi GitHub

Dokumen ini menjadi aturan kerja untuk kelanjutan proyek. Jika issue, PR, handover, checkpoint, atau spesifikasi historis berbeda, ikuti keputusan owner terbaru dan master spesifikasi fitur yang dirujuk di bawah. Jangan menghidupkan kembali model lama tanpa keputusan eksplisit.

## 1. Model produk: satu madrasah per instalasi

- Produk didistribusikan sebagai **satu paket ZIP generik** yang sama untuk seluruh madrasah.
- Setiap madrasah memasang paket secara mandiri pada VPS/hosting masing-masing.
- Setiap instalasi mempunyai database akademik, kredensial database, konfigurasi, storage, cache, queue, log, export, temporary files, dan backup sendiri.
- **Tidak ada shared academic database atau akses akademik lintas madrasah.** Ini bukan SaaS multi-tenant.
- `tenant_id`, `TenantScoped`, dan `SYSTEM_TENANT_ID` yang masih ada pada source harus dipahami dalam konteks instalasi lokal/legacy dan defense-in-depth. System Tenant adalah registry referensi lokal pada database instalasi itu, bukan tenant pusat bersama.
- Jangan menghapus kolom tenant atau melakukan refactor skema massal sebelum audit seluruh query, FK, migration upgrade, installer, backup/rollback, dan pengujian fresh install. Setiap madrasah tetap terisolasi oleh database dan instalasinya sendiri.
- Layanan pusat masa depan hanya untuk lisensi, aktivasi/validasi lisensi, entitlement/paket fitur, masa berlaku, dan pembayaran/langganan. Dilarang mengirim identitas Siswa, data akademik, soal, media, jawaban, Attempt, nilai, atau laporan pendidikan ke pusat.

## 2. Role dan portal resmi

Role aktif dan resmi tepat tiga:

1. `ADMIN_MADRASAH`
2. `GURU`
3. `SISWA`

Keputusan:

- Seluruh pekerjaan administrasi yang dahulu dilakukan Operator dilebur ke Admin Madrasah. Tidak ada role, akun, portal, atau dashboard Operator baru.
- Role/portal Kamad dibekukan dan tidak digunakan untuk login maupun authorization baru. Nama/jabatan Kepala Madrasah boleh menjadi metadata identitas dan penandatangan dokumen.
- Histori role/akun lama tidak boleh dihapus diam-diam. Akun legacy harus fail-closed; jangan otomatis memetakan Kamad/Operator menjadi role lain. Role Operator dihapus dari role aktif/authorization, sedangkan rekam historis dipertahankan bila diperlukan FK/audit.
- Tidak ada role atau portal Proktor permanen. Proktor adalah capability/penugasan sementara pada akun Guru yang tetap memiliki tepat satu role `GURU`.
- Tidak ada portal Kamad maupun portal Operator.

## 3. Proktor — Mode B di portal Web Guru

Acuan fitur: Master Web Admin §8 dan Master Web Guru §7–8. Proktor bukan role, akun, atau portal terpisah.

### 3.1 Penetapan oleh Admin Madrasah

Admin hanya menetapkan atau mencabut Guru sebagai Proktor untuk **ujian terpusat UTS, UAS, dan UM**.

Admin dapat melihat informasi minimum untuk penugasan:
- nama ujian dan jenis UTS/UAS/UM;
- jadwal Exam;
- Guru penanggung jawab;
- daftar Room yang telah tersedia pada data ujian.

Admin memilih Exam terpusat, Room yang sudah tersedia, akun Guru, interval penugasan, dan alasan bila diwajibkan audit. Admin mencatat `assigned_by`, `revoked_by`, waktu, status, reason, dan audit.

Admin **tidak**:
- membuat/mengelola Room;
- menempatkan/mengelola peserta atau roster;
- melihat aktivitas/progres/kehadiran/Attempt/perangkat peserta;
- mengelola soal, kunci, konfigurasi akademik, jawaban, nilai;
- melakukan Reset Attempt, Force Submit, extension, release/takeover perangkat.

Ujian reguler/mandiri tetap dikelola Guru pembuat sesuai assignment mengajar dan tidak memerlukan penetapan Proktor oleh Admin.

### 3.2 Menu Proktor pada Web Guru

Menu hanya muncul bila Guru memiliki assignment aktif atau menampilkan status aman yang menjelaskan belum ada penugasan. Menyembunyikan menu bukan authorization; seluruh route/API/query/action wajib memvalidasi assignment.

Submenu sesuai Master Web Guru:

- Tugas aktif.
- Jadwal tugas.
- Detail ujian/ruang.
- Roster peserta ruang.
- Monitoring peserta.
- Catatan kehadiran/insiden.
- Riwayat penugasan.

Detail assignment menampilkan Exam, judul, identitas akademik minimum, Room, interval tugas, status, Admin pemberi tugas, alasan/catatan aman, dan instruksi Proktor.

### 3.3 Batas data dan tindakan Proktor

Proktor hanya dapat melihat peserta yang resmi ditempatkan pada Room untuk Exam terkait, identitas minimum, status kehadiran/mulai/mengerjakan/submit, status teknis minimum yang disetujui, dan insiden pada Room tersebut.

Proktor tidak boleh melihat:
- peserta Room lain atau Exam lain;
- soal, kunci, pembahasan, isi jawaban;
- nilai/hasil ujian;
- bank soal atau konfigurasi ujian;
- export global;
- tindakan Reset Attempt, Force Submit, perpanjangan waktu, takeover/release perangkat.

Default capability Proktor adalah **monitoring-only**. Hak sensitif kelak harus menjadi permission terpisah dengan reason, konfirmasi, re-authorization, transaksi/locking, idempotency, dan audit; bukan hak otomatis Proktor.

Room scope wajib ditegakkan pada query backend, route, API, detail, event/polling, cache, export, download, dan setiap action. Exam-level Gate saja tidak cukup.

### 3.4 Lifecycle assignment dan model serentak

- Interval assignment memakai `[starts_at, ends_at)`; interval bersebelahan tidak overlap.
- Cegah overlap Guru yang sama, termasuk request concurrent, melalui transaksi/locking/constraint yang tepat.
- Assignment revoked/expired harus langsung menolak request berikutnya.
- Catat actor, waktu, alasan, status, dan audit create/revoke.
- Jadwal ujian tetap `Exam.start_at/end_at`; waktu server adalah acuan.
- Token dibuat saat Guru menerbitkan ujian, dapat dilihat sebelum jadwal, tetapi Siswa hanya boleh mulai pada jadwal sah.
- **Jangan gunakan `ExamSession`, `SessionRoom`, `session_number`, `wave`, `session_token`, atau session lifecycle sebagai model operasional/admission.** Room hanya untuk penempatan peserta dan lingkup monitoring Proktor, bukan admission gate.
- Mode A (Guru pembuat) dan Mode B (Guru sebagai Proktor) tidak boleh tercampur. Capability Proktor tidak memperluas kepemilikan bank soal atau hak akademik Guru.

### 3.5 Gap source yang terverifikasi

Pada source eksekusi `d6aaeae6`:
- `ExamProctorAssignmentService` masih fail-closed karena belum ada marker kanonis yang membuktikan Exam adalah UTS/UAS/UM.
- Model/schema Exam belum memiliki marker operasional yang eksplisit untuk jenis ujian terpusat.
- Route/menu Proktor di Web Guru belum tersedia sebagai alur lengkap; dashboard stub masih ditolak.
- PR #18 membawa sebagian konsep Room/Proktor, tetapi memiliki field/session lifecycle yang bertentangan dengan aturan serentak dan tidak boleh digabung apa adanya.

Desain lanjutan harus menambahkan **penanda operasional eksplisit** untuk jenis ujian (`REGULAR`, `UTS`, `UAS`, `UM` atau kontrak ekuivalen yang tervalidasi) dan relasi Exam+Room+Guru. Penanda ini adalah klasifikasi operasional ujian, **bukan** pengganti taxonomy Kurikulum Merdeka/KBC. Jangan bypass guard Proktor sebelum marker, Room scope, lifecycle, dan negative tests selesai.

## 4. Prioritas platform

- Fokus implementasi saat ini: Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Pengembangan aplikasi Android dan Web Super Admin ditunda sampai **Juni 2027**. Jangan mengerjakan source/API/UI/build/Android Studio sekarang dan jangan menjadikannya blocker Web.
- Web Super Admin masa depan hanya untuk lisensi, aktivasi, entitlement, dan pembayaran; bukan portal akademik lintas madrasah.
- Penundaan Android tidak berarti Web/PWA boleh diklaim sebagai aplikasi Android native atau sebagai pengganti native CBT yang sudah siap.

## 5. Kurikulum Merdeka + KBC sebagai satu kesatuan

Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan delapan dimensi profil lulusan adalah **satu ekosistem akademik**, bukan modul atau database terpisah.

Alur provenance yang wajib terhubung:

registrasi dan versi sumber kurikulum → tahun ajaran/semester/fase/kelas/mapel → assignment Guru → CP → TP/ATP → materi/topik → Panca Cinta + Pembelajaran Mendalam + dimensi profil lulusan → QuestionVersion → Assessment → immutable Exam Snapshot → Attempt/Answer/Score → capaian dan laporan historis.

Jangan menghilangkan provenance, versioning, snapshot, atau histori. Sumber referensi yang baru berstatus metadata-verified tidak boleh diklaim sebagai artifact/checksum verified.

## 6. Import soal — konflik dokumen GitHub yang harus direkonsiliasi

Sumber GitHub saat ini belum sepenuhnya konsisten:

- Master Web Guru §5.8 dan Issue #8/#9 mendeskripsikan XLSX dan DOCX sebagai dua parser yang berbeda, tetapi memakai satu DTO/lifecycle/batch/storage.
- Issue #16 meminta implementasi XLSX tervalidasi.
- PR #19 menyatakan ada keputusan owner 23 November untuk menghapus jalur XLSX soal lama, sambil mempertahankan PhpSpreadsheet untuk import Guru/Siswa Admin dan export.
- PR #19 masih Draft dan belum terintegrasi ke branch eksekusi.

Aturan kerja sementara:
- Jangan menghidupkan kembali importer XLSX lama yang hanya menerima JSON `rows[]` seolah-olah sudah mem-parsing UploadedFile.
- Jangan menghapus dukungan XLSX Admin untuk import Guru/Siswa.
- Jangan menambahkan parser/batch/storage kedua.
- DOCX dan XLSX (jika tetap disetujui sebagai format soal) harus berbagi satu draft DTO, staging/batch, idempotency, duplicate detection, audit, preview/correction/revalidation, dan commit sebagai DRAFT.
- PDF/OCR ditunda.
- SHORT_ANSWER tetap BLOCKED sampai accepted-answer authoring, persistence, immutable snapshot, dan scoring terbukti end-to-end.
- Sebelum implementasi import soal, rekonsiliasi keputusan PR #19 dengan Master Web Guru §5.8 dan Issues #8/#9/#16; jangan menyelesaikan konflik dengan asumsi.

## 7. Batas perubahan dan penerimaan

- Jangan mengulang patch yang sudah ada pada branch/PR lain. Audit exact source SHA dan compare sebelum integrasi.
- Backup byte-exact setiap file existing sebelum patch; verifikasi isi/hash.
- Jangan menurunkan RBAC, exact-one-role, assignment scope, snapshot, transaksi, idempotency, fencing, atau constraint database demi meluluskan test.
- Jangan menjalankan migration pada database produksi, merge PR, deployment VPS, atau membuat ZIP final tanpa seluruh preflight dan persetujuan owner.
- Setiap klaim PASS harus menunjuk exact commit, workflow, job, dan hasil test. Source yang belum diuji tetap NOT VERIFIED.
- Satu ZIP VPS baru boleh dinyatakan siap setelah fresh install, konfigurasi aaPanel, permission/storage, queue/scheduler, backup/restore, API, security, E2E, dan ekstraksi/struktur paket diverifikasi.

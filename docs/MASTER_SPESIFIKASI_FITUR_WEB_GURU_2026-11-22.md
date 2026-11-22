# MASTER SPESIFIKASI FITUR WEB GURU CBT MADRASAH
Tanggal: 22 November 2026
Status: **ARAHAN PRODUK WAJIB — ACUAN AGENT WEB GURU**
Repository: `zane0184/cbt_november`
Dokumen terkait: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md`

> Dokumen ini menetapkan cakupan dan perilaku portal Guru. Ini adalah spesifikasi produk, bukan klaim bahwa semua fitur telah tersedia. Agent wajib audit source aktual, menandai status per fitur, lalu implementasi sesuai ownership dan kontrak backend. Menu yang terlihat bukan bukti fitur berfungsi.

---

## 0. KEPUTUSAN PROYEK MENGIKAT — JANGAN DIUBAH AGENT

1. Fokus sampai Juni 2027 adalah Web CBT: portal ADMIN MADRASAH, GURU, SISWA.
2. Android native/Android Studio/API Android dan Web Super Admin ditunda sampai Juni 2027. Jangan menjadi dependency atau blocker pekerjaan Web.
3. Tidak ada portal/akun/role OPERATOR. Fungsi administrasi Operator ditangani Admin Madrasah.
4. Tidak ada portal/akun/role KAMAD di CBT. Nama kepala madrasah dapat muncul sebagai data identitas pada kop dokumen, bukan akun/role CBT.
5. Tidak ada role/portal PROKTOR permanen. Proktor adalah **Guru dengan assignment sementara** untuk Exam dan Room tertentu. Menu Proktor berada di Web Guru dan hanya aktif sesuai assignment.
6. Siswa hanya peserta ujian, bukan Proktor.
7. Setiap madrasah memiliki instalasi dan database akademik sendiri. Tidak ada shared academic database atau akses lintas madrasah. Tenant ID tetap defense-in-depth dalam instalasi.
8. Guru berwenang mengelola bank soal, membuat, mengatur, dan menerbitkan ujian tanpa persetujuan Admin selama sesuai penugasan kelas/mapel dan kebijakan sistem.
9. Guru tidak boleh melihat atau mengubah data kelas, mapel, bank soal, ujian, peserta, jawaban, dan hasil milik Guru lain di luar assignment resmi.
10. Model ujian **SERENTAK** berdasarkan `exam.start_at`, `exam.end_at`, token Exam, eligibility, dan waktu server. Tidak ada gelombang, nomor sesi, lifecycle ExamSession, atau SessionRoom sebagai model operasional/admission gate.
11. Room hanya untuk penempatan peserta dan cakupan monitoring Proktor. Room bukan syarat start/resume Attempt.
12. Token dibuat otomatis setelah Guru menerbitkan ujian. Token dapat dilihat sebelum jadwal; Siswa baru dapat mulai sesuai jadwal server.
13. Waktu server adalah sumber waktu resmi. Jangan menggunakan jam perangkat browser sebagai penentu admission/expiry.
14. Device Lease/fencing tidak boleh dinyatakan siap produksi sebelum integration test dan MySQL 8/InnoDB multi-device/concurrency test lulus.
15. Browser bukan kiosk; jangan klaim mampu mencegah screenshot, tombol Home, atau perpindahan aplikasi.
16. SHORT_ANSWER **BLOCKED** sampai accepted answers benar-benar tersedia pada authoring, tersimpan, masuk immutable Exam Snapshot, dan digunakan ScoringService. Jangan mengakali dengan fallback opsi.
17. ESSAY memerlukan penilaian manual oleh Guru/pemeriksa yang berwenang.
18. Kurikulum Merdeka harus terintegrasi dengan pembelajaran, bank soal, asesmen, ujian, dan laporan; pertahankan versi serta histori. Dukungan juga disiapkan untuk Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum berikutnya.
19. Source historis yang menggunakan URL /admin atau namespace App\\Livewire\\Admin untuk komponen Guru adalah technical debt. Itu bukan alasan memberi Guru akses portal Admin. Route/layout/authorization harus mencerminkan portal Guru.
20. Jangan mengikuti dokumen/issue lama yang bertentangan dengan keputusan di atas. Khususnya desain ExamSession operasional telah digantikan oleh keputusan model serentak tanpa lifecycle sesi.

---

# 1. STRUKTUR MENU WEB GURU

Nama label boleh disesuaikan dengan konsistensi UI, tetapi fungsi dan batas akses berikut tidak boleh hilang.

1. **Dashboard**
2. **Penugasan Saya**
   - Ringkasan penugasan
   - Kelas/rombel yang diampu
   - Mata pelajaran
   - Tahun pelajaran/semester
   - Riwayat penugasan
3. **Pembelajaran**
   - Perencanaan pembelajaran
   - Kurikulum dan capaian
   - CP / TP / ATP
   - Pemetaan materi dan tujuan
   - Panca Cinta / Pembelajaran Mendalam bila data resmi tersedia
4. **Bank Soal**
   - Daftar soal
   - Buat soal
   - Edit/revisi soal
   - Detail/preview soal
   - Kategori/tag/topik
   - Pemetaan kurikulum
   - Media soal
   - Versi soal dan histori
   - Import soal (XLSX/DOCX hanya setelah kontrak backend disetujui)
5. **Asesmen & Ujian**
   - Daftar asesmen/ujian
   - Buat asesmen/ujian
   - Pengaturan ujian
   - Pemilihan soal
   - Snapshot ujian
   - Peserta/rombel
   - Jadwal dan durasi
   - Token
   - Preview
   - Terbitkan
   - Riwayat/status ujian
6. **Monitoring**
   - Monitoring ujian milik Guru (Mode A)
   - Monitoring sebagai Proktor (Mode B, hanya assignment aktif dan Room yang ditugaskan)
   - Status peserta dan progres
   - Kendala peserta
   - Tindakan operasional hanya jika capability eksplisit diberikan
7. **Penilaian**
   - Daftar jawaban essay yang perlu dinilai
   - Penilaian per peserta
   - Rubrik/pedoman penilaian bila disediakan
   - Simpan nilai dan umpan balik
   - Riwayat koreksi/perubahan
   - Finalisasi hasil sesuai permission
8. **Hasil & Laporan**
   - Hasil ujian yang menjadi tanggung jawab Guru
   - Rekap per kelas/mapel/ujian
   - Analisis butir soal
   - Distribusi nilai
   - Export/cetak sesuai hak
9. **Analytics**
   - Performa peserta/kelas
   - Capaian pembelajaran
   - Analisis kesulitan soal
   - Tren hasil per asesmen/periode
10. **Proktor** (menu capability-based, bukan role)
    - Tugas Proktor aktif
    - Detail Exam dan Room yang ditugaskan
    - Daftar peserta Room
    - Kehadiran/status/progres
    - Catatan insiden
    - Selesaikan/lihat riwayat assignment
11. **Profil & Pengaturan**
    - Profil Guru
    - Ubah password
    - Preferensi tampilan
    - Notifikasi
    - Bantuan/panduan

Menu yang belum didukung backend harus ditandai coming/blocked/disabled secara jujur; jangan menampilkan tautan aktif yang menghasilkan halaman kosong atau error.

---

# 2. DASHBOARD GURU

Dashboard harus menampilkan data aktual sesuai identitas dan penugasan Guru yang login.

## 2.1 Ringkasan
- Jumlah kelas/rombel yang diampu.
- Jumlah mata pelajaran yang ditugaskan.
- Jumlah siswa dalam cakupan penugasan aktif.
- Jumlah soal milik Guru/yang dapat dikelola sesuai owner/scope.
- Jumlah asesmen/ujian draft.
- Jumlah ujian terjadwal.
- Jumlah ujian sedang berlangsung.
- Jumlah ujian selesai.
- Jumlah jawaban essay menunggu penilaian.
- Tugas Proktor aktif hari ini (jika ada assignment aktif).

Angka harus berasal dari query tenant/assignment-scoped, bukan dummy. Jangan menghitung siswa hanya dari pivot membership tanpa memeriksa identitas siswa, akun, profil, tenant, rombel aktif, dan role tepat satu SISWA sesuai schema aktual.

## 2.2 Agenda
- Jadwal asesmen/ujian mendatang.
- Ujian yang sedang berlangsung.
- Batas akhir penilaian essay.
- Agenda pembelajaran/asesmen jika modul tersedia.
- Assignment Proktor yang akan dimulai/berakhir.

## 2.3 Aktivitas
- Soal dibuat/diubah/dipublikasikan.
- Asesmen dibuat/diubah/diterbitkan.
- Token dibuat/dirotasi.
- Peserta/rombel diperbarui.
- Penilaian essay disimpan/final.
- Assignment Proktor dibuat/dicabut.
- Insiden monitoring dicatat.

Hanya tampilkan aktivitas milik Guru atau yang secara eksplisit ditugaskan kepadanya.

## 2.4 Peringatan
- Penugasan tidak aktif/kedaluwarsa.
- Kurikulum/CP/TP belum dipetakan.
- Ujian belum memiliki peserta.
- Soal belum valid/kurang.
- Jadwal ujian bertabrakan dalam cakupan Guru.
- Penilaian belum selesai.
- Assignment Proktor hampir berakhir.

---

# 3. PENUGASAN SAYA

Guru dapat melihat, bukan mengubah master penugasan Admin.

Informasi:
- Tahun pelajaran dan semester.
- Mata pelajaran.
- Kelas/rombel.
- Status ACTIVE/INACTIVE.
- Berlaku mulai/sampai.
- Riwayat penugasan.
- Wali kelas atau peran tambahan hanya bila memang tercatat dan diotorisasi.

Semua halaman Guru harus menggunakan assignment sebagai batas query. Assignment aktif berarti status ACTIVE dan tanggal berlaku mencakup waktu server saat ini. Jangan mengasumsikan ada kolom status aktif pada User/TeacherProfile bila migration/model aktual tidak memilikinya; audit schema terlebih dahulu. User/TeacherProfile soft-deleted tidak boleh dianggap aktif.

Guru tidak dapat menambah kelas/mapel ke dirinya sendiri, mengaktifkan assignment, atau mengubah tahun pelajaran.

---

# 4. PEMBELAJARAN DAN INTEGRASI KURIKULUM

## 4.1 Kurikulum dan versi
- Tampilkan kurikulum yang diregistrasikan Admin untuk tahun pelajaran dan kelas terkait.
- Tampilkan versi, tahun berlaku, fase/kelas, dan mata pelajaran.
- Jangan menawarkan kurikulum/versi yang tidak diregistrasikan untuk scope Guru.
- Riwayat kurikulum yang sudah dipakai tidak boleh hilang.

## 4.2 CP, TP, ATP
Guru dapat:
- Menelusuri CP berdasarkan kurikulum, versi, fase, mapel.
- Memilih CP relevan untuk pembelajaran/asesmen.
- Menautkan TP ke CP.
- Menggunakan ATP yang sesuai.
- Membuat atau mengelola pemetaan lokal Guru hanya bila desain data mengizinkan; jangan mengubah master kurikulum global Admin.
- Melihat detail, kode, deskripsi, elemen, fase, versi, dan provenance sumber data.

## 4.3 Pembelajaran Mendalam dan Panca Cinta
Sediakan keterkaitan yang konsisten bila master data/rujukan resmi sudah tersedia:
- Dimensi profil/kompetensi lulusan sesuai model yang disetujui.
- Prinsip Pembelajaran Mendalam.
- Panca Cinta/Kurikulum Berbasis Cinta.
- Pemetaan ke CP/TP/ATP, materi, asesmen, dan laporan.

Jangan membuat label atau data resmi fiktif. Jika data sumber belum ada, tandai sebagai NEEDS DATA/NEEDS DESIGN, jangan mengarang isi kurikulum.

## 4.4 Penggunaan kurikulum
Konteks kurikulum harus dapat diikuti pada:
- Rencana pembelajaran.
- Bank soal.
- Asesmen.
- Exam snapshot.
- Analisis hasil.
- Laporan capaian.

Ujian yang sudah diterbitkan tetap menyimpan referensi kurikulum/versi saat itu; perubahan kurikulum tidak boleh mengubah histori ujian lama.

---

# 5. BANK SOAL

## 5.1 Daftar Bank Soal
Filter:
- Mata pelajaran sesuai assignment.
- Kelas/fase.
- Kurikulum dan versi.
- CP/TP/ATP.
- Topik/materi.
- Tipe soal.
- Tingkat kesulitan.
- Level kognitif.
- Status draft/published/archived.
- Pemilik soal (personal/shared sesuai kebijakan).
- Kata kunci.

Tampilkan:
- Kode/ID soal.
- Potongan stem.
- Mapel/kelas/fase.
- CP/TP.
- Tipe.
- Bobot.
- Kesulitan/level kognitif.
- Status dan versi.
- Waktu dibuat/diubah.

Search, sort, filter, pagination harus tenant- dan assignment-scoped.

## 5.2 Membuat soal
Field yang diperlukan:
- Mata pelajaran.
- Kelas/fase.
- Kurikulum dan versi.
- CP/TP/ATP terkait.
- Materi/topik.
- Tipe soal.
- Stem/pertanyaan.
- Opsi jawaban sesuai tipe.
- Kunci jawaban (private; tidak boleh bocor ke Siswa).
- Pembahasan/explanation.
- Bobot/poin.
- Kesulitan.
- Level kognitif.
- Estimasi waktu jika digunakan.
- Media/gambar/tabel/persamaan jika didukung.
- Status draft/publish sesuai lifecycle soal.

## 5.3 Tipe soal dan status
Tipe yang telah muncul pada audit source:
- MULTIPLE_CHOICE.
- MULTIPLE_RESPONSE.
- TRUE_FALSE.
- MATCHING.
- ESSAY.
- SHORT_ANSWER.

Ketentuan aktif:
- MULTIPLE_CHOICE: satu jawaban benar.
- MULTIPLE_RESPONSE: beberapa jawaban benar sesuai definisi scoring.
- TRUE_FALSE: pernyataan benar/salah.
- MATCHING: pasangan kiri-kanan dengan urutan dan mapping yang tervalidasi.
- ESSAY: jawaban uraian, status penilaian MANUAL_REVIEW.
- SHORT_ANSWER: **BLOCKED**, jangan tampilkan sebagai opsi authoring/publish sampai accepted answers tersimpan pada QuestionVersion, masuk immutable ExamSnapshot, dan dievaluasi ScoringService dengan tes end-to-end.

Tidak ada opsi E. Opsi pilihan ganda mengikuti batas A-D bila itulah kontrak source yang telah disepakati. Jangan menambah tipe atau opsi baru tanpa persetujuan desain.

## 5.4 Kunci dan pembahasan
- Kunci disimpan privat.
- Kunci hanya bisa dilihat Guru pemilik/yang diberi permission.
- Siswa tidak boleh menerima field answer_key/is_correct/accepted_answers atau data privat lain.
- Pembahasan hanya ditampilkan pada waktu dan kebijakan yang ditentukan; jangan ikut terkirim pada payload ujian aktif jika belum diizinkan.
- Log tidak boleh merekam isi kunci/secret secara tidak perlu.

## 5.5 Versioning dan immutable snapshot
- Revisi soal membuat versi baru sesuai kontrak QuestionVersion.
- Jangan mengubah snapshot ujian yang sudah dipublish atau sudah memiliki Attempt.
- Snapshot harus memuat data yang diperlukan untuk mereproduksi soal saat ujian: stem, tipe, urutan, opsi, poin, media reference dan metadata yang disetujui.
- Identitas source QuestionVersion, versi snapshot/schema, checksum/integrity bila ditetapkan, dan urutan soal harus konsisten.
- Perubahan soal setelah publish hanya berlaku untuk ujian baru, bukan mengubah ujian yang sudah berjalan/selesai.
- Public payload untuk Siswa dipisahkan dari private answer key/explanation.
- Jangan menganggap snapshot lengkap sebelum audit field-by-field dan scoring contract selesai.

## 5.6 Media soal
Target fungsi:
- Upload gambar/media.
- Preview.
- Ganti/hapus dengan konfirmasi.
- Alt text/keterangan.
- Urutan media.
- Hubungan media ke versi soal.
- Validasi ukuran, MIME, ekstensi, checksum.
- Penyimpanan private/tenant-local.
- Pengiriman ke Siswa hanya melalui endpoint yang memeriksa identitas, Exam, Attempt, eligibility, dan kontrak keamanan.

Jangan menggunakan URL publik permanen yang memungkinkan peserta lain menebak/mengambil media. Kontrak media delivery, authorization, cache, expiry, revocation, lease/fencing harus disetujui backend owner. UI tidak boleh dibuat dengan endpoint asumsi.

## 5.7 Arabic, RTL, harakat, dan persamaan
- Editor mendukung Unicode Arab dan harakat.
- Arah paragraf/segmen dapat RTL atau LTR tanpa merusak campuran Arab-Indonesia.
- Tanda baca, angka, opsi, tanda kurung, dan simbol harus tetap urut benar.
- Persamaan matematika dapat dibuat/preview sesuai renderer yang disepakati.
- Media, tabel, persamaan, dan teks panjang harus ditampilkan konsisten pada preview Guru dan halaman ujian Siswa.
- Web–Android renderer parity adalah pekerjaan masa depan setelah Juni 2027; untuk sekarang validasi Web secara mandiri dan dokumentasikan kompatibilitas format snapshot.
- Jangan klaim dukungan RTL/formula selesai hanya karena textarea menerima Unicode.

## 5.8 Import soal XLSX/DOCX
Fitur import bukan sekadar upload dan belum boleh diimplementasikan UI-nya menggunakan kontrak rekaan.
- XLSX dan DOCX merupakan adapter terpisah menuju satu draft contract yang disetujui.
- Rancangan Agent 2 sebelumnya mengusulkan satu QuestionDraftDTO dan reuse ImportBatch/ImportBatchRow; itu proposal sampai approval eksplisit.
- PDF/OCR tidak termasuk scope aktif kecuali ada keputusan baru.
- Lifecycle target: upload → pemeriksaan keamanan → ekstraksi → staging → preview → koreksi Guru → revalidasi server → confirm → simpan DRAFT → audit/report.
- Tampilkan source locator/range agar Guru dapat membandingkan hasil dengan dokumen.
- Tandai konten ambigu, opsi tidak lengkap, marker kunci tidak pasti, gambar/tabel/persamaan/RTL gagal ekstrak sebagai NEEDS_REVIEW.
- Jangan menebak kunci jawaban otomatis.
- Tidak ada auto-publish.
- SHORT_ANSWER tetap blocked; ESSAY manual review.
- Reuse satu lifecycle batch; jangan membuat service/tabel batch kedua.
- Tunggu kontrak endpoint/DTO/error schema/limits/storage/retention/idempotency/approval Agent 2 sebelum menghubungkan UI.

---

# 6. ASESMEN & UJIAN — MODE A GURU MANDIRI

## 6.1 Daftar asesmen/ujian
Tampilkan:
- Nama/judul.
- Jenis asesmen.
- Mapel.
- Kelas/rombel.
- Tahun pelajaran/semester.
- Kurikulum/versi dan CP/TP terkait.
- Waktu mulai/selesai.
- Durasi.
- Jumlah soal.
- Jumlah peserta.
- Status draft/published/scheduled/running/finished/cancelled sesuai enum backend.
- Status token.
- Dibuat/diubah kapan.

Filter hanya ujian yang dapat diakses Guru melalui owner dan assignment.

## 6.2 Buat asesmen/ujian
Form:
1. Identitas ujian: judul, instruksi, deskripsi, jenis asesmen.
2. Konteks akademik: tahun pelajaran, semester, mapel, kelas/rombel, kurikulum/versi, CP/TP/ATP.
3. Soal: pilih dari bank soal yang dapat dikelola, jumlah, bobot, urutan.
4. Peserta: roster resmi dari rombel dalam assignment Guru.
5. Jadwal: start_at, end_at, durasi sesuai aturan backend.
6. Token: otomatis saat publish; jangan membuat token secara manual tanpa kebijakan.
7. Pengaturan: acak urutan soal/opsi bila didukung dan disimpan konsisten pada snapshot; aturan attempt; instruksi ujian; konfigurasi yang memang disetujui.
8. Preview: tampilan soal dan instruksi sebelum publish.
9. Simpan draft atau publish.

Validasi server:
- Guru tepat satu role GURU.
- User masih persisted/non-deleted.
- TeacherProfile tenant-local valid.
- Permission yang diperlukan.
- Assignment mapel/rombel aktif dan berlaku.
- Peserta berasal dari roster yang sah.
- Semua soal tenant-local dan dapat digunakan Guru.
- Snapshot dan konfigurasi konsisten.
- Jadwal valid dan tidak melanggar aturan.
- Tidak ada akses IDOR ke Exam/Question/Participant milik Guru lain.

## 6.3 Publikasi dan token
- Guru boleh menerbitkan tanpa approval Admin selama scope penugasan sah.
- Publish dilakukan transaksional dan idempotent.
- Snapshot immutable dibuat/ditetapkan sebelum ujian dapat diakses peserta.
- Token dibuat otomatis saat publish jika belum ada.
- Token dapat dilihat Guru dan Admin sesuai izin.
- Token tidak boleh muncul di log publik atau bocor melalui endpoint yang tidak berwenang.
- Rotasi token memerlukan permission, alasan jika diwajibkan, audit event, dan aturan dampak terhadap peserta.
- Publish ulang tidak membuat token/participant/snapshot duplikat.
- Jika publish gagal di tengah, tidak boleh ada ujian berstatus terbit dengan snapshot/token tidak lengkap.

## 6.4 Model jadwal serentak
- Satu Exam memiliki satu jendela pelaksanaan serentak: `start_at` sampai `end_at`.
- Tidak ada UI gelombang, session number, ExamSession, SessionRoom, atau reschedule session.
- Server menentukan apakah peserta boleh mulai/resume.
- Room tidak menentukan admission.
- Setelah `end_at`, Attempt baru ditolak sesuai kebijakan; Attempt yang sudah aktif hanya dapat resume bila status/expiry/eligibility mengizinkan.
- Perubahan jadwal setelah publish memerlukan authorization, validasi dampak, transaksi, dan audit.
- Jangan mengubah jadwal diam-diam jika Attempt sudah berjalan.

## 6.5 Mode A — Guru mandiri
- Guru mengelola asesmen/ujian untuk kelas/rombel yang ditugaskan.
- Guru mengelola roster dalam lingkup assignment.
- Tidak memerlukan assignment Proktor Admin.
- Tidak boleh menambah peserta lintas kelas.
- Tidak boleh memonitor ujian Guru lain.
- Guru dapat memonitor ujian miliknya sesuai policy, bukan melalui akses Admin terselubung.

---

# 7. MODE B — PENUGASAN PROKTOR DALAM WEB GURU

## 7.1 Prinsip
Proktor bukan role, bukan akun baru, dan bukan portal terpisah. Akun tetap tepat satu role GURU. Kemampuan Proktor diberikan oleh assignment aktif yang dibuat Admin untuk Exam+Room tertentu.

## 7.2 Menu Proktor
Tampilkan menu hanya jika Guru memiliki assignment aktif atau ada halaman status aman yang menjelaskan belum ada penugasan. Jangan memberikan capability hanya dengan menyembunyikan/menampilkan menu; semua endpoint wajib memvalidasi assignment.

Submenu:
- Tugas aktif.
- Jadwal tugas.
- Detail ujian/ruang.
- Roster peserta ruang.
- Monitoring peserta.
- Catatan kehadiran/insiden.
- Riwayat penugasan.

## 7.3 Detail assignment
Informasi:
- Exam dan judul ujian.
- Mata pelajaran/identitas akademik minimum.
- Room.
- Waktu mulai/selesai assignment.
- Status (pending/active/revoked/expired sesuai enum backend).
- Admin pemberi tugas.
- Catatan/alasan tugas yang aman ditampilkan.
- Instruksi Proktor.

## 7.4 Batas scope Room
Proktor hanya melihat:
- Peserta yang secara resmi ditempatkan pada Room terkait Exam.
- Identitas minimum.
- Kehadiran/status mulai/sedang mengerjakan/submit.
- Progres atau status teknis minimum yang disetujui.
- Insiden pada Room yang ditugaskan.

Proktor tidak boleh melihat:
- Peserta Room lain.
- Peserta lintas Exam.
- Soal/kunci/pembahasan.
- Isi jawaban siswa.
- Nilai/hasil ujian.
- Bank soal.
- Konfigurasi ujian.
- Export global.
- Force Submit/Reset Attempt/perpanjangan waktu/takeover kecuali diberikan capability terpisah secara eksplisit.

Room scope harus diterapkan pada query backend, route, API, detail, event/polling, cache, export, download, dan setiap action. Exam-level Gate saja tidak cukup.

## 7.5 Assignment lifecycle
- Admin membuat dan mencabut assignment.
- Assignment memiliki starts_at/ends_at dan status.
- Interval [starts_at, ends_at); adjacent intervals tidak overlap.
- Guru tidak dapat menerima assignment yang bertabrakan.
- Pemeriksaan overlap harus aman terhadap concurrent requests melalui locking/constraint strategy backend.
- Assignment dicabut/expired tidak boleh digunakan untuk request berikutnya.
- Setiap assignment/revoke memiliki actor, waktu, alasan, dan audit.
- UI menampilkan status dan waktu secara jelas; jangan menganggap jadwal lokal browser sebagai sumber otoritatif.

## 7.6 Mode A dan Mode B tidak boleh tercampur
Mode A adalah Guru pembuat yang mengelola ujian sendiri dalam assignment kelas/mapel.
Mode B adalah Guru yang ditugaskan Admin sebagai Proktor untuk Room pada ujian terpusat.
Satu Guru dapat memiliki kedua konteks hanya jika assignment dan policy mengizinkan; kemampuan Proktor tidak memperluas kepemilikan bank soal atau hak akademik Guru.

---

# 8. MONITORING UJIAN

## 8.1 Monitoring sebagai Guru pembuat (Mode A)
Guru dapat melihat ujian yang dibuat/diampu sesuai assignment:
- Ringkasan peserta.
- Belum mulai.
- Sedang mengerjakan.
- Sudah submit.
- Bermasalah.
- Waktu mulai dan sisa waktu berdasar server.
- Status Attempt dan sinkronisasi yang benar-benar tersedia.
- Catatan kendala.

Jangan mengirim jawaban atau kunci ke halaman monitoring biasa.

## 8.2 Monitoring sebagai Proktor (Mode B)
Hanya Room assignment aktif. UI harus menampilkan Exam, Room, waktu tugas, peserta Room, status kehadiran/progres dan catatan insiden. Data harus dibatasi backend dan diuji dengan peserta dari Room lain sebagai negative test.

## 8.3 Tindakan operasional
Default Proktor adalah monitoring-only. Reset Attempt, Force Submit, extension, release/takeover bukan capability otomatis Proktor. Jika kelak ada hak khusus:
- Permission terpisah.
- Reason wajib.
- Konfirmasi.
- Re-authorization server-side.
- Transaction dan lock order yang disetujui Agent 2.
- Audit actor/time/old state/new state.
- Idempotency dan concurrency tests.
- Tidak boleh menghilangkan jawaban atau histori.

## 8.4 Batas browser
Jangan menyebut monitoring sebagai pengawasan perangkat/kiosk. Browser tidak dapat menjamin pencegahan screenshot, pindah aplikasi, atau tombol Home.

---

# 9. PENILAIAN

## 9.1 Daftar penilaian
- Ujian/asesmen yang dapat dinilai Guru.
- Jumlah jawaban essay belum dinilai.
- Sudah dinilai.
- Menunggu review/finalisasi.
- Filter per kelas/rombel/mapel/ujian/status.

## 9.2 Penilaian essay
- Tampilkan jawaban peserta yang memang menjadi scope Guru.
- Tampilkan stem soal dan rubrik/pedoman jika tersedia.
- Input nilai sesuai rentang poin.
- Umpan balik/komentar.
- Simpan draft penilaian.
- Finalisasi jika permission/status mengizinkan.
- Riwayat perubahan nilai dan actor.
- Validasi batas nilai, status Attempt, dan snapshot.
- Penilaian tidak boleh menimpa jawaban asli.
- Perubahan nilai setelah final harus melalui prosedur koreksi dengan alasan dan audit.

## 9.3 Scoring otomatis
- Pilihan ganda, multiple response, true/false, matching harus mengikuti ScoringService dan snapshot resmi.
- Jangan menghitung ulang dari bank soal versi terbaru bila ujian menggunakan snapshot lama.
- SHORT_ANSWER tetap blocked sampai accepted answer snapshot contract terbukti.
- ESSAY manual review; tidak boleh dinyatakan otomatis dinilai.
- Score harus konsisten, idempotent, dan memiliki histori bila dihitung ulang.

## 9.4 Hak akses nilai
Guru hanya dapat melihat/menilai peserta dan hasil dalam assignment atau Exam yang menjadi kewenangannya. Proktor monitoring-only tidak otomatis boleh melihat nilai.

---

# 10. HASIL, LAPORAN, DAN ANALYTICS

## 10.1 Hasil ujian
- Daftar hasil per Exam.
- Peserta dan status penilaian.
- Nilai total.
- Rincian komponen nilai sesuai permission.
- Status manual review.
- Histori perubahan nilai.
- Preview hasil sesuai kebijakan publikasi.

## 10.2 Rekap Guru
- Rekap per kelas/rombel.
- Rekap per mapel.
- Rekap per asesmen.
- Daftar peserta belum submit.
- Daftar essay belum dinilai.
- Rata-rata, median, rentang, distribusi jika sumber data valid.
- Rekap per tahun pelajaran/semester.

## 10.3 Analisis butir soal
- Persentase benar/salah/tidak dijawab.
- Distribusi jawaban per opsi.
- Tingkat kesulitan berdasarkan hasil aktual.
- Daya pembeda bila formula dan jumlah data memenuhi syarat.
- Soal yang sering salah.
- Keterkaitan soal dengan CP/TP.
- Jumlah respons yang digunakan untuk perhitungan.
- Peringatan bila jumlah sampel terlalu kecil.

Jangan menampilkan analitik yang menyesatkan karena data belum cukup atau jawaban belum final.

## 10.4 Analytics pembelajaran
- Capaian per CP/TP.
- Peta kompetensi kelas.
- Siswa yang perlu pendampingan.
- Perkembangan antarasesmen/periode.
- Hubungan hasil dengan tujuan pembelajaran.
- Konteks kurikulum dan versi.

Analitik hanya memakai data yang dapat diakses Guru dan tidak boleh membocorkan data kelas lain.

## 10.5 Export/cetak
- PDF/Excel sesuai hak.
- Filter kelas, mapel, Exam, tahun pelajaran.
- Kop madrasah yang disetel Admin.
- Preview.
- Audit export.
- Tidak ada password/token/secret.
- Proktor tidak dapat export hasil/nilai kecuali hak khusus eksplisit.

---

# 11. PROFIL DAN PENGATURAN GURU

- Lihat profil pribadi.
- Ubah field profil yang memang diizinkan Admin.
- Ubah password sendiri.
- Preferensi Light/Dark/System.
- Pengaturan notifikasi.
- Preferensi tampilan.
- Informasi bantuan/panduan.
- Riwayat aktivitas pribadi yang aman.

Guru tidak dapat mengubah NIP/NUPTK/role/tenant/permission/assignment atau identitas akademik master jika itu milik Admin. Guru tidak dapat mengatur konfigurasi server, database, license, secret, storage, atau branding global madrasah.

---

# 12. KEAMANAN DAN BOUNDARY WEB GURU

## 12.1 Exact role dan identitas
Portal Guru wajib mensyaratkan:
- User authenticated dan persisted.
- Tepat satu role GURU yang dibaca dari database pada request.
- Multi-role, role kosong, role tidak dikenal, role revoke harus fail-closed.
- User belum soft-deleted.
- Tepat satu TeacherProfile tenant-local yang belum soft-deleted.
- Tenant User dan TenantContext konsisten.
- Permission yang sesuai.
- Assignment mapel/rombel ACTIVE dan tanggal berlaku.
- Gate/policy dan query resource tenant/assignment-scoped.

Jangan mengasumsikan status aktif tersendiri pada User/TeacherProfile bila schema aktual tidak memilikinya. Audit model/migration.

## 12.2 Per request dan Livewire
- Periksa akses saat mount dan setiap hydration/action.
- Request POST Livewire harus mengulangi validasi persisted role, profile, permission, tenant dan assignment.
- Role/permission/profile/user/assignment yang dicabut setelah halaman terbuka harus menolak action berikutnya.
- Jangan mempercayai state browser/hidden input.
- Uji session expired, account revoke, tenant switch, stale component, replay/tampering dan CSRF.

## 12.3 IDOR dan kepemilikan
Negative tests untuk:
- Mengganti Question ID ke milik Guru lain.
- Mengganti Exam ID.
- Mengganti participant ID.
- Mengakses hasil/answer milik Guru lain.
- Mengakses Room/assignment Proktor lain.
- Mengakses media file langsung.
- Menggunakan export/download URL orang lain.
- Mengirim Livewire action dengan ID yang dimanipulasi.
Semua harus ditolak tanpa side effects.

## 12.4 Kunci dan data privat
- Jangan kirim answer key/is_correct/accepted_answers ke browser Siswa.
- Jangan bocorkan jawaban di monitoring Proktor.
- Jangan menaruh secret/token di URL/log.
- Media delivery harus private/authorized.
- Export harus memeriksa scope ulang.

---

# 13. UX DAN TAMPILAN

- UI profesional, premium, ringan, mudah dipahami Guru.
- Sidebar konsisten, menu capability-based.
- Search/filter/sort/pagination.
- Form bertahap untuk pembuatan ujian yang kompleks.
- Autosave draft hanya bila backend menjamin idempotency dan konflik ditangani.
- Preview soal dan ujian sebelum publish.
- Empty/loading/error/success states.
- Validasi inline dan server-side.
- Dialog konfirmasi untuk publish, token rotation, delete, finalisasi nilai.
- Mobile/tablet/desktop responsive.
- Light/Dark/System dengan semantic design tokens.
- Kontras minimal WCAG 2.2 AA, keyboard navigation, label, focus states.
- Arabic RTL, harakat, campuran Arab-Indonesia dan rumus harus diuji dengan konten nyata.
- Hindari menu aktif yang hanya placeholder.
- Jangan gunakan data dummy pada production.

---

# 14. ACCEPTANCE CHECKLIST DAN PENGUJIAN

Setiap fitur diberi status:
- **EXISTS & VERIFIED**
- **EXISTS BUT BROKEN**
- **MISSING**
- **BLOCKED**
- **DEFERRED**

Jangan gunakan status VERIFIED hanya karena route/view/model ada.

## 14.1 Dashboard/assignment
- Guru dengan assignment aktif hanya melihat scope yang ditugaskan.
- Guru tanpa assignment tidak melihat data akademik/ujian yang bukan miliknya.
- Assignment inactive/expired/revoked langsung menolak request berikutnya.
- Akun/TeacherProfile soft-deleted ditolak.
- Multi-role ditolak.
- Tenant mismatch ditolak.

## 14.2 Bank soal
- CRUD dan versioning.
- Duplicate/validation.
- Permission dan assignment scope.
- Kunci privat.
- Snapshot immutable setelah publish.
- Update soal tidak mengubah ujian lama.
- Media authorization.
- Arabic/RTL/harakat/formula browser tests.
- SHORT_ANSWER blocked test.
- ESSAY manual review test.

## 14.3 Ujian
- Guru membuat draft hanya pada scope assignment.
- Guru memilih soal yang sah.
- Snapshot dibuat secara atomik.
- Publish menghasilkan token otomatis.
- Repeated publish idempotent.
- Token dapat dilihat sesuai permission.
- Siswa sebelum start_at ditolak; pada jadwal sah diterima; setelah end_at ditolak sesuai aturan.
- Room bukan admission gate.
- Tidak ada ExamSession/wave/session UI.
- Mode A tidak memerlukan assignment Proktor Admin.
- Cross-class Mode A ditolak.

## 14.4 Proktor
- Hanya Guru tepat satu role.
- Assignment aktif, valid, belum expired/revoked.
- Room scope pada semua endpoint, polling, list, detail, export dan file.
- Peserta Room lain tidak terlihat.
- Tidak ada jawaban/kunci/nilai/hasil.
- Adjacent intervals tidak overlap.
- Concurrent overlapping assignments ditolak di MySQL 8/InnoDB.
- Revoke memutus akses request berikutnya.
- Audit assignment/revoke.

## 14.5 Penilaian
- ESSAY manual review.
- Score range validation.
- Nilai tersimpan dan memiliki histori.
- Penilaian ulang memerlukan otorisasi dan audit.
- Score berdasarkan immutable snapshot.
- Idempotency dan race test.

## 14.6 Pengujian teknis wajib
- PHP 8.3 lint.
- Composer locked dependencies/platform requirements.
- Laravel route registration.
- PHPUnit feature/integration exact SHA.
- MySQL 8/InnoDB.
- Livewire HTTP GET/POST, bukan hanya unit/component harness.
- Authorization negative tests.
- Concurrency/locking.
- Browser E2E desktop/tablet/mobile.
- Uji light/dark.
- Uji import hanya setelah backend contract disetujui.
- Tidak ada production migration pada pengujian.

Laporkan exact branch, HEAD, source SHA, workflow run, environment, test count/assertions, dan log. Jika belum dijalankan tulis NOT RUN/NOT VERIFIED.

---

# 15. KONDISI SOURCE YANG SUDAH PERNAH DILAPORKAN — HISTORIS, WAJIB REFRESH

Laporan Agent 3 tanggal 22 Nov menyebut:
- Route /guru, layout Guru, dashboard, pembelajaran, kurikulum, profil telah menjadi fondasi.
- Bank Soal dan Asesmen mandiri telah dipasang pada route Guru di branch Agent 3.
- TeacherPortalAccess memeriksa persisted user, tepat satu role GURU, TeacherProfile tenant-local, tenant context, permission dan assignment.
- Dashboard query siswa telah diperketat terhadap profil/user/rombel aktif dan role tepat satu SISWA.
- SHORT_ANSWER diblokir pada UI/validasi server karena accepted answers belum terbukti masuk snapshot dan scoring.
- Snapshot menyalin stem, explanation, options, media metadata dan question_type, tetapi kontrak schema/public-private payload tetap perlu audit.
- Menu Monitoring, Penilaian, Analytics dan Proktor masih placeholder/ditahan pada sebagian checkpoint.
- Mode B Proktor, direct Exam–Room service, placement, room-scoped monitoring, overlap serialization, exact-role lintas policy, dan integrasi Agent 1/2/4 belum terbukti lengkap.
- Test method count pernah dilaporkan, tetapi exact-SHA PHPUnit/CI dan browser E2E belum terbukti pada checkpoint tersebut.

**Semua butir di atas adalah histori checkpoint, bukan kondisi source yang dijamin sekarang.** Agent wajib refresh branch HEAD dan source aktual. Jangan mengulang patch yang sudah ada, jangan menganggap laporan lama sebagai hasil runtime, dan jangan menyatakan P0 selesai tanpa bukti baru.

---

# 16. PEMBAGIAN OWNERSHIP LINTAS AGENT

- **Agent 1 (Exam–Room–Proktor backend):** direct Exam–Room model, participant placement, assignment lifecycle, overlap serialization, Room-scoped monitoring contract/API, audit, MySQL concurrency.
- **Agent 2 (Core backend/security/data):** exact-role/policy, API/DTO, transaction/locking, Exam/Attempt lifecycle, scoring/snapshot contract, media delivery authorization, import staging/DTO, database integrity.
- **Agent 3 (Web Admin/Guru UI):** route/layout/menu Guru, Livewire components, dashboard, bank soal UI, assessment builder UI, Proktor UI consumer, monitoring/penilaian/analytics UI, profile/settings, browser UX dan feature tests untuk UI.
- **Agent 4 (Web Siswa):** student admission, token/start, execution UI, answer save, resume, submit, recovery sesuai API/Attempt contract.
- **Integrator/koordinator:** menyatukan kontrak route/API/DTO, ownership file, urutan lock, test end-to-end, release checklist. Jangan mengubah file owner agent lain secara sepihak.

Jika satu file dipakai bersama, tetapkan satu owner. Perubahan lintas owner dilakukan melalui kontrak/potongan perubahan yang direview; hindari dua agent mengedit routes/web.php, policy, atau service yang sama secara paralel.

---

# 17. ATURAN KERJA DAN LAPORAN

Pisahkan **AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN**.
- Refresh branch, HEAD, CI dan status working tree sebelum bekerja.
- Baca master spec Admin dan dokumen ini.
- Sebelum edit, backup setiap file dan catat path/hash.
- Jangan menimpa ZIP baseline.
- Tidak ada migration database produksi selama pengembangan.
- Jangan meminta SSH atau kredensial pengguna.
- Jangan mengubah Android/Web Super Admin.
- Jangan merge/rebase/cherry-pick/deploy tanpa instruksi/persetujuan.
- Jangan menganggap source di branch Agent 3 sudah masuk branch utama.
- Update checkpoint dan laporan setiap tahap.
- Laporkan file yang berubah, backup, test yang benar-benar dijalankan, blocker, dan pekerjaan berikutnya.
- Jika source bertentangan dengan dokumen, berhenti pada AUDIT dan laporkan perbedaannya; jangan mengubah keputusan produk secara diam-diam.

---

# 18. URUTAN PRIORITAS WEB GURU

## P0 — Alur inti agar Guru dapat membuat dan menjalankan ujian
1. Login dan exact-role Guru.
2. Dashboard dan Penugasan Saya.
3. Boundary tenant, profile, permission, assignment pada setiap request.
4. Bank Soal CRUD dan tipe soal yang telah disetujui; kunci privat.
5. Integrasi kurikulum/CP/TP/ATP sesuai data yang tersedia.
6. Asesmen/Exam builder Mode A.
7. Roster peserta sesuai assignment.
8. Immutable snapshot.
9. Publish dan token otomatis.
10. Jadwal serentak berdasarkan waktu server.
11. Monitoring ujian Guru sendiri.
12. Portal Siswa end-to-end untuk ujian.
13. Penilaian ESSAY manual dan hasil minimum.
14. CI exact-SHA, browser QA, MySQL tests, UAT.

## P0 lintas-agent sebelum Mode B dinyatakan siap
1. Direct Exam–Room contract.
2. Penempatan peserta resmi.
3. Proktor assignment lifecycle dan overlap serialization.
4. Room-scoped API/monitoring seluruh endpoint.
5. Menu Proktor di Web Guru.
6. Negative tests Room lain, Exam lain, revoked/expired assignment.
7. MySQL 8/InnoDB concurrency.

## P1 — Penyempurnaan
- Import XLSX/DOCX setelah kontrak Agent 2 disetujui.
- Media authoring/delivery lanjutan.
- Arabic RTL/harakat dan formula editor/renderer yang telah diuji.
- Analytics capaian dan analisis butir lanjutan.
- Rubrik penilaian, notifikasi, laporan lanjutan, UI polish.

## DEFERRED sampai Juni 2027
- Android native dan integrasi Android.
- Web Super Admin.
- Parity renderer Web–Android.
- Fitur yang hanya dibutuhkan komponen deferred.

---

# 19. INSTRUKSI PERTAMA AGENT PENGGANTI / AGENT 3

Jangan langsung coding. Urutannya:
1. Baca dokumen Master Admin dan Master Guru ini.
2. Refresh branch `work/web-guru-foundation-20261122`, HEAD, commits, CI, issue/PR, source dan checkpoint terkini.
3. Pastikan apakah branch tersebut masih aktif dan apakah source sudah berubah setelah checkpoint.
4. Audit setiap menu Guru terhadap daftar pada dokumen ini.
5. Buat matriks status: EXISTS & VERIFIED / EXISTS BUT BROKEN / MISSING / BLOCKED / DEFERRED.
6. Tandai komponen yang sudah tersedia agar tidak dibuat ulang.
7. Konfirmasi kontrak Agent 1/2/4 terbaru untuk Exam, Room, placement, Proktor, snapshot, scoring, media, Attempt dan Siswa.
8. Buat gap report file/class/route/API/DTO/database/test yang spesifik.
9. Tentukan ownership file dan urutan implementasi.
10. Baru ajukan patch P0 dengan backup, test plan, dan bukti yang akan dihasilkan.

**Prinsip akhir:** Guru adalah pemilik proses pembelajaran, bank soal, asesmen, dan ujian dalam lingkup penugasannya. Admin mengelola master/administrasi dan penugasan Proktor. Proktor hanya menjalankan pengawasan ruang yang ditugaskan. Siswa hanya peserta. Tidak boleh ada role atau akses terselubung yang mengaburkan batas tersebut.

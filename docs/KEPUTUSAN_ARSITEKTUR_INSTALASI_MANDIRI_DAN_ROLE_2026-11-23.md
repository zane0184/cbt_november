# KEPUTUSAN ARSITEKTUR WAJIB — CBT MADRASAH (23 NOVEMBER 2026)

## Status dan prioritas implementasi
Fokus implementasi saat ini adalah **WEB CBT ONLY** dengan satu alur kerja terpadu yang mencakup Backend Laravel, Web Admin Madrasah, Web Guru, dan Web Siswa. Tidak ada pembagian implementasi kepada agent terpisah. Satu pemilik implementasi mengaudit, merancang, mengubah source, mengintegrasikan, dan menguji seluruh komponen. Pemilik proyek tetap memberikan persetujuan untuk perubahan berisiko tinggi, merge, migration/deployment produksi, dan distribusi.

## Instalasi mandiri per madrasah
Setiap madrasah memiliki instalasi aplikasi dan database sendiri. Tidak ada database bersama, tenant lintas madrasah, atau akses data antar-madrasah. Database lokal hanya menyimpan data madrasah pemilik instalasi. Kolom tenant_id yang masih ada di source adalah scope instalasi lokal/legacy hingga refactor aman selesai, bukan mekanisme shared multi-tenant antar-madrasah. Backup, restore, upgrade, dan recovery dilakukan per instalasi.

## Koneksi pusat
Instalasi lokal hanya terhubung ke layanan pusat untuk identitas instalasi, lisensi, aktivasi/validasi lisensi, entitlement fitur, batas penggunaan, masa berlaku, dan pembayaran/langganan. Dilarang mengirim atau menyinkronkan nama siswa, NIS/NISN, akun, rombel, mapel, soal, media, jawaban, attempt, nilai, atau laporan pendidikan. Layanan pusat bukan portal akademik/CBT dan bukan database bersama.

## Role dan portal lokal
Portal hanya Admin Madrasah, Guru, dan Siswa.
- Semua fungsi Operator dilebur ke Admin Madrasah. Tidak ada akun, role, portal, atau dashboard Operator.
- Tidak ada akun/role/dashboard Kamad. Kepala Madrasah hanya jabatan/metadata operasional pada laporan atau pengaturan, bukan identitas login.
- Tidak ada Proktor permanen. Guru yang diberi assignment aktif menjadi Guru pengawas sementara untuk ujian terkait; assignment diaudit.
- OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN yang masih ditemukan adalah legacy. Jangan seed untuk instalasi baru atau gunakan untuk authorization. Jangan hapus histori atau memetakan akun lama secara diam-diam. Pemetaan legacy hanya melalui migration upgrade berversi dengan backup, audit mapping, dan tes terisolasi. Kamad lama tidak otomatis menjadi akun lain.

## Wewenang
Admin mengelola identitas madrasah, akun Guru/Siswa, data akademik, tahun ajaran/semester, rombel, impor master, administrasi, dukungan teknis, monitoring sistem, laporan administratif, backup/restore, serta penugasan Guru pengawas sementara.
Admin dilarang membuat/menerbitkan/mengubah/mengatur ujian Guru, mengakses Bank Soal, token, attempt, jawaban, monitoring peserta, penilaian/nilai, force submit, reset attempt, atau menutup ujian.
Guru mengelola bank soal, asesmen, ujian, jadwal, publikasi, token, monitoring dalam penugasan, penilaian, dan laporan akademik. Assignment pengawas tidak memberikan hak mengubah soal, token, peserta, jawaban atau nilai.
Kepala Madrasah adalah metadata/jabatan/penandatangan laporan, bukan role.

## Taksonomi asesmen — keputusan pemilik 23 November 2026
Taksonomi asesmen wajib mengikuti Kurikulum Merdeka yang terintegrasi dengan Kurikulum Berbasis Cinta (KBC). **UTS, UAS, UM, SIMULASI TKA, ASTS, ASAS, ASAT, SIMULASI_AN, dan label kegiatan sejenis bukan daftar kategori baku atau enum tertutup.** Nama-nama tersebut, jika muncul, adalah nama kegiatan/istilah operasional yang tidak boleh mengunci desain sistem.

Acuan konseptual awal:
- Asesmen Formatif untuk memantau dan memperbaiki proses pembelajaran serta memberi umpan balik.
- Asesmen Sumatif untuk menilai pencapaian hasil belajar; cakupan dapat berupa lingkup materi, akhir semester bila dipilih/diperlukan, akhir tahun ajaran, dan/atau akhir jenjang.
- Teknik asesmen dapat berupa tes, praktik, produk, proyek, portofolio, observasi, dan bentuk lain sesuai tujuan. CBT hanya menjadi mode pelaksanaan bagi asesmen yang sesuai dilakukan secara digital.

Sistem wajib memisahkan dimensi berikut:
1. Fungsi/jenis asesmen menurut kerangka kurikulum.
2. Tujuan dan cakupan asesmen (misalnya lingkup materi, semester bila dipilih, tahun ajaran, fase/jenjang).
3. Nama kegiatan/label yang dapat dikonfigurasi sesuai kebijakan madrasah.
4. Bentuk instrumen (tes, praktik, proyek, portofolio, observasi, dan lainnya).
5. Mode pelaksanaan (CBT atau non-CBT).

TKA adalah asesmen terstandar eksternal/khusus, bukan otomatis kategori asesmen internal madrasah atau pengganti asesmen satuan pendidikan. KBC mengintegrasikan nilai Panca Cinta ke pembelajaran dan asesmen; KBC bukan daftar nama ujian baru. Kurikulum, nomenklatur, registry, dan relasi harus berversi serta mempertahankan histori. Jangan membuat enum final, migration, atau validasi tertutup sebelum desain dan pemetaan sumber resmi diverifikasi.

## Integrasi kurikulum dan pembelajaran
Satu kontrak backend kanonis digunakan oleh Web Admin, Web Guru, dan Web Siswa. Cakupan data:
1. Registry kurikulum dan versi, provenance/sumber resmi, tanggal berlaku, status, dan histori.
2. Struktur fase A/B/C, kelas MI 1–6, mata pelajaran, elemen, dan muatan lokal berdasarkan referensi resmi terverifikasi.
3. CP resmi sebagai referensi induk yang terlindungi; Guru memilih CP sesuai penugasan dan mengembangkan TP serta ATP tanpa menimpa CP induk.
4. Panca Cinta sebagai nilai lintas pembelajaran/asesmen dengan provenance.
5. Pembelajaran Mendalam sebagai pendekatan pembelajaran.
6. Delapan dimensi profil lulusan sebagai registry/kerangka pendukung terpisah.
7. Materi/topik yang terhubung ke kurikulum, mapel, fase/kelas, periode, dan penugasan Guru.
8. Jejak kurikulum dari konteks akademik → TP/ATP → QuestionVersion → asesmen → Exam Snapshot immutable → hasil → laporan.
9. Snapshot ujian mempertahankan versi/provenance kurikulum, CP, TP/ATP, materi/topik, dan metadata asesmen yang berlaku ketika snapshot dibuat; perubahan registry berikutnya tidak mengubah histori.
10. Web Siswa hanya menerima konteks, instruksi, soal, hasil, dan umpan balik yang diotorisasi. Redaction wajib dilakukan server-side; jangan kirim kunci jawaban, is_correct, penjelasan privat, rubrik internal, atau data siswa lain.

## Kontrak implementasi terpadu
- Satu pemilik implementasi bertanggung jawab atas kontrak backend, API/DTO, skema, policy, seluruh portal, dokumentasi, dan integrasi. Branch/PR lama adalah bahan audit dan sumber perubahan yang harus diperiksa, bukan pembagian tanggung jawab agent yang masih aktif.
- Jangan membuat schema/enum/DTO berbeda antarportal. Setiap perubahan kontrak harus diikuti seluruh pemakai dalam satu rangkaian integrasi dan regression test.
- DOCX adalah format utama import soal Guru sesuai keputusan pemilik. Jangan menghidupkan kembali XLSX sebagai format import soal. XLSX/CSV tetap dapat digunakan untuk import/export administratif Guru/Siswa/Mapel oleh Admin dengan validasi dan preview aman.
- SHORT_ANSWER tetap fail-closed sampai accepted-answer, immutable snapshot, scoring, dan integration tests backend-to-Web terbukti lulus.
- Semua route, controller, Livewire action, API, export/download, dan payload wajib memvalidasi role, ownership, penugasan, scope instalasi, dan izin secara server-side.

## Ujian dan waktu
Ujian berjalan serentak berdasarkan jadwal Exam dan waktu server. Jangan menambahkan wave, session_number, session_token, atau lifecycle sesi sebagai admission gate. Token dibuat otomatis setelah Guru menerbitkan ujian; dapat dilihat sebelum jadwal, tetapi peserta hanya boleh mulai pada jadwal yang sah berdasarkan waktu server. Attempt, Answer, Score, Device Lease, fencing, dan receipt harus konsisten serta idempotent. Operasi reset Attempt, Force Submit, takeover, dan perpanjangan waktu harus berizin, beralasan, transaksional, dan diaudit.

## Batas keselamatan perubahan
Tidak boleh menjalankan migration pada database produksi, menghapus role/histori legacy, melakukan deployment, merge PR, atau memindahkan data tanpa rencana upgrade, backup byte-exact, rollback/recovery plan, tes disposable MySQL 8/InnoDB, preflight, dan persetujuan eksplisit pemilik proyek. ZIP baseline asli tidak boleh diubah. Setiap file existing wajib dibackup sebelum patch dan backup harus dapat diverifikasi byte-exact.

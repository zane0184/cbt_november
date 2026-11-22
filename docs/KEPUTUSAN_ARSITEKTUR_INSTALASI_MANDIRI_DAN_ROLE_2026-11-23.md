# KEPUTUSAN ARSITEKTUR WAJIB — CBT MADRASAH (23 NOVEMBER 2026)

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

## Penundaan Super Admin
Web Super Admin dan Android Super Admin ditunda sampai Juni 2027. Jangan implementasikan portal/dashboard/aplikasi Android Super Admin sebelum Juni 2027 atau instruksi tertulis baru. Ini tidak menunda aplikasi Android native peserta CBT: siswa Android tetap wajib aplikasi native; Web/PWA sementara untuk iOS.

## Kontrak agent
Agent 2 adalah pemilik kontrak backend/integritas:
1. Referensi Kurikulum Merdeka/Panca Cinta, Pembelajaran Mendalam, dan 8 dimensi profil lulusan dari sumber resmi, provenance, versi, tanggal berlaku, histori.
2. Kontrak tahun ajaran, materi/topik dan relasi akademik.
3. Versi kurikulum pada bank soal dan snapshot ujian.
4. Accepted answer, snapshot, dan scoring SHORT_ANSWER.
5. Tutup blocker Agent 4: token opsional; receipt immutable memuat client_request_id; Attempt Authorization/Attempt Limit; concurrency; enforcement server-side Android-native; media MIME/Range/seek dengan auth/fencing.
6. SHORT_ANSWER tidak aktif sebelum kontrak dan tes integrasi lulus.

Agent Web Guru (Agent 3): kerjakan UI authoring/parser/template XLSX yang tidak bergantung pada kontrak tertunda. Ikuti kontrak Agent 2, jangan mengarang enum/referensi/kolom/topik. Media dibatasi ukuran/tipe, diperiksa, instalasi-scoped, anti path traversal, transaksional. SHORT_ANSWER menunggu kontrak Agent 2.

Agent Web Siswa (Agent 4): PR tetap Draft/unmerged sampai kontrak backend dan browser-to-Laravel E2E nyata lulus. Mock bukan bukti integrasi. Receipt tanpa client_request_id tidak boleh menghapus antrean atau mengizinkan submit. Uji token opsional, offline/recovery, fencing, media Range/seek, enforcement Android-native.

Web Admin: hanya Admin/Guru/Siswa. Tidak ada akun Operator/Kamad/Proktor. Kepala Madrasah hanya metadata. Admin menugaskan Guru pengawas sementara, tanpa hak operasi CBT Guru.

## Terminologi dan gerbang
Gunakan "instalasi mandiri per madrasah", "database lokal madrasah", "layanan pusat lisensi dan pembayaran", "Admin Madrasah", "Guru pengawas sementara", "Kepala Madrasah sebagai metadata/jabatan". Hindari "multi-tenant lintas madrasah", "akun Operator", "akun Kamad", "Proktor permanen".
Tidak boleh migration produksi, hapus role/histori legacy, deployment, merge PR, atau pemindahan data tanpa rencana upgrade, backup byte-exact, rollback, tes disposable dan persetujuan eksplisit.

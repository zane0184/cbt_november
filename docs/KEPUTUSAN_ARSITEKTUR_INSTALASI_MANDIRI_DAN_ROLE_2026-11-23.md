# Keputusan Arsitektur Otoritatif CBT Madrasah

Tanggal keputusan: 23 November 2026
Status: **WAJIB DIIKUTI SELURUH AGENT**

Dokumen ini menggantikan istilah multi-tenant lintas madrasah yang selama ini digunakan secara tidak tepat dalam beberapa laporan dan source.

## 1. Model instalasi dan database

1. Produk CBT didistribusikan sebagai satu paket aplikasi yang dapat dipasang oleh banyak madrasah.
2. **Setiap madrasah memiliki instalasi aplikasi dan database sendiri.** Database berisi hanya data madrasah tersebut.
3. Tidak ada tenant sharing, database bersama, atau akses antar-madrasah. Satu instalasi tidak memuat data madrasah lain.
4. Batas keamanan utama dalam instalasi adalah isolasi data internal madrasah, user/role, dan seluruh relasi akademik/CBT. Validasi tenant lokal boleh dipertahankan sebagai defense-in-depth/installation scope selama tidak menciptakan konsep tenant lintas madrasah.
5. Satu database per madrasah berarti backup, restore, upgrade, audit, retention, dan recovery dilakukan per instalasi.
6. Installer harus mengikat instalasi ke identitas madrasah dan database yang dikonfigurasi saat instalasi. Jangan menambahkan data madrasah lain ke database tersebut.

## 2. Hubungan ke platform Super Admin

1. Setiap instalasi boleh berkomunikasi dengan layanan pusat **hanya untuk lisensi, aktivasi/validasi lisensi, paket fitur, batas penggunaan, masa berlaku, dan pembayaran/langganan**.
2. Layanan pusat tidak menjadi portal operasional data akademik atau CBT madrasah.
3. Tidak boleh ada sinkronisasi nama siswa, NIS/NISN, akun, rombel, soal, jawaban, attempt, nilai, laporan akademik, atau data operasional lain ke Super Admin.
4. Integrasi pusat menggunakan identitas instalasi dan token/lease berlingkup lisensi. Hindari mengirim payload data pendidikan.
5. Gangguan layanan lisensi ditangani dengan kebijakan signed lease/grace period yang akan ditentukan terpisah; jangan menghapus atau mengunci data lokal karena layanan pusat tidak tersedia.

## 3. Portal dan role pengguna lokal

Portal operasional lokal hanya:
- **Admin Madrasah**: satu akun/role administratif. Seluruh fungsi yang dahulu disebut Operator sudah dilebur ke Admin Madrasah. Tidak ada akun Operator, portal Operator, atau role OPERATOR baru.
- **Guru**: mengelola bank soal, asesmen, pembuatan, jadwal, publikasi ujian, token, pelaksanaan sesuai penugasan, monitoring sesuai hak, penilaian dan laporan akademik.
- **Siswa**: peserta ujian dan akses hasil miliknya sesuai aturan.

Ketentuan tambahan:
- Tidak ada akun atau role Kamad. Kepala Madrasah adalah jabatan/aktor operasional yang dicatat dalam laporan dan/atau pengaturan/metadata administrasi, bukan identitas login atau role.
- Tidak ada akun atau role Proktor permanen. Pengawas adalah Guru yang diberi penugasan sementara untuk ujian tertentu. Otorisasi berasal dari assignment aktif dan tercatat di audit log.
- Role LEGACY seperti OPERATOR/KAMAD/PROKTOR hanya boleh dikenali sementara oleh proses upgrade/migrasi kompatibilitas, bukan diberikan kepada akun baru atau dipakai untuk login/authorization operasional setelah transisi.
- Jangan menghapus histori audit/aktivitas/rekaman lama hanya untuk membersihkan label role. Lakukan mapping legacy dengan jejak audit dan uji upgrade; nonaktifkan akses terlebih dahulu.

## 4. Wewenang Admin dan Guru

Admin Madrasah mengelola identitas madrasah, akun Guru/Siswa, data akademik, tahun ajaran/semester, rombel, impor master, dukungan teknis, monitoring sistem, laporan administratif, backup/restore, dan penugasan Guru pengawas sementara.

Admin Madrasah **tidak** membuat/menerbitkan/mengubah ujian Guru, tidak mengelola bank soal, token, attempt, jawaban, monitoring operasional peserta, penilaian, nilai, force submit, reset attempt, atau penutupan ujian.

Guru memiliki kewenangan akademik/CBT sesuai penugasan dan audit. Penugasan pengawas sementara tidak memberikan hak pengubahan soal, token, peserta, jawaban, atau nilai.

Kepala Madrasah dapat menjadi pejabat yang ditampilkan/dirujuk dalam laporan dan pengaturan metadata, tanpa akun portal tersendiri.

## 5. Penundaan platform pusat

- Web Super Admin dan aplikasi Android Super Admin **ditunda sampai Juni 2027**.
- Jangan mulai implementasi portal/dashboard Android Super Admin sebelum Juni 2027 atau instruksi tertulis baru.
- Backend integration contract lisensi/pembayaran boleh didesain secara terisolasi tanpa mengirim data pendidikan. Implementasi pusat tetap mengikuti jadwal dan persetujuan tahap berikutnya.
- Android peserta CBT native tetap merupakan aplikasi lokal peserta ujian; penundaan ini khusus Android Super Admin, bukan aplikasi CBT siswa.

## 6. Aturan lintas-agent dan kontrak

Semua Agent 2, Agent Web Guru (Agent 3), Agent Web Siswa (Agent 4), dan pekerjaan Web Admin wajib:
1. Membaca dokumen ini sebelum mengubah arsitektur, role, database, API, atau kontrak impor.
2. Tidak mengasumsikan arsitektur shared multi-tenant antar-madrasah.
3. Tidak membuat referensi kurikulum, dimensi lulusan, Panca Cinta, atau Pembelajaran Mendalam tanpa sumber resmi, versi, tanggal berlaku, dan provenance.
4. Agent 2 menjadi pemilik kontrak backend akademik, versi kurikulum/Panca Cinta, Pembelajaran Mendalam/8 dimensi lulusan, tahun ajaran/topik, snapshot, accepted-answer dan scoring SHORT_ANSWER.
5. Agent Web Guru menyesuaikan template/parser XLSX, validasi referensi, ekstraksi media dan UI hanya berdasarkan kontrak yang sudah ditetapkan Agent 2. Media harus tervalidasi MIME/ukuran, tenant/installation scoped, aman, dan transaksional.
6. Agent 4 tidak boleh menutup blocker token opsional, receipt/client_request_id, Android native enforcement, browser Laravel E2E, atau media Range hanya berdasarkan mock.
7. SHORT_ANSWER tidak boleh diaktifkan sebelum kontrak accepted answer, snapshot, dan scoring lulus integrasi.
8. Setiap perubahan lintas-agent disertai bukti uji MySQL 8 disposable, rollback, isolasi data instalasi, duplikasi/idempotensi, dan regresi.

## 7. Terminologi wajib

Gunakan:
- "instalasi mandiri per madrasah"
- "database lokal madrasah"
- "layanan pusat lisensi dan pembayaran"
- "Admin Madrasah"
- "Guru pengawas sementara"
- "jabatan Kepala Madrasah (metadata/laporan)"

Hindari penggunaan "multi-tenant" untuk menyebut beberapa madrasah dalam satu database atau tenant silang. Bila kode masih punya kolom tenant_id untuk batas scope internal instalasi/legacy, dokumentasikan secara eksplisit sebagai scope instalasi lokal, bukan pemisah antar madrasah.

## 8. Gerbang perubahan berisiko

Dilarang menjalankan migration pada database produksi, menghapus role legacy/histori, menggabungkan branch, merge PR, deployment, atau memindahkan data sebelum rencana upgrade, backup, rollback, bukti tes upgrade terisolasi dan persetujuan eksplisit tersedia.

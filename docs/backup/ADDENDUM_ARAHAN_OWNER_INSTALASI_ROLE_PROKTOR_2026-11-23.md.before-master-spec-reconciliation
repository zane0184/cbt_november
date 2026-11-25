# ADDENDUM ARAHAN OWNER — INSTALASI, ROLE, PROKTOR, DAN PRIORITAS PLATFORM

Tanggal: 23 November 2026
Repository: zane0184/cbt_november
Basis audit: `work/solo-web-cbt-completion-20261123-execution` @ `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`
Status: ARAHAN OWNER TERBARU — MENGESAMPINGKAN CATATAN GITHUB LAMA YANG BERTENTANGAN

Dokumen ini merekam arahan owner terbaru dalam percakapan koordinasi. Jika Issue #10, Issue #20, handover, checkpoint, atau spesifikasi lama berbeda, arahan terbaru di dokumen ini yang berlaku untuk pekerjaan selanjutnya.

## 1. Model distribusi dan instalasi

- Produk didistribusikan sebagai satu paket ZIP generik yang sama untuk seluruh madrasah.
- Setiap madrasah memasang ZIP secara mandiri pada VPS/hosting masing-masing.
- Setiap instalasi mempunyai database, kredensial, konfigurasi, storage, cache, queue, log, backup, dan data akademik sendiri.
- Tidak ada layanan CBT multi-tenant/shared database antar-madrasah, pertukaran data pendidikan, maupun akses akademik lintas madrasah.
- Layanan pusat masa depan hanya untuk lisensi, aktivasi/validasi lisensi, entitlement/paket fitur, masa berlaku, dan pembayaran/langganan. Dilarang mengirim identitas siswa, data akademik, soal, media, jawaban, Attempt, nilai, atau laporan pendidikan ke pusat.
- Kolom/model `tenant_id` yang masih ada tidak boleh dianggap sebagai mekanisme SaaS multi-tenant. Perlakukan hanya sebagai konteks instalasi lokal/legacy selama masih diperlukan source. Jangan menghapus atau mengubah skema secara massal tanpa audit seluruh query, migration upgrade, backup, rollback, dan pengujian instalasi mandiri.

## 2. Role dan portal lokal

Role resmi tepat tiga:
1. `ADMIN_MADRASAH`
2. `GURU`
3. `SISWA`

- Semua pekerjaan Operator dilebur ke Admin Madrasah. Tidak ada role, akun, portal, atau dashboard Operator baru.
- Role/portal Kamad dibekukan dan tidak digunakan untuk login maupun authorization baru. Nama/jabatan Kepala Madrasah boleh menjadi metadata/penandatangan dokumen.
- Histori role/akun lama tidak boleh dihapus diam-diam. Akun legacy harus fail-closed dan penanganan upgrade harus ter-audit; jangan otomatis memetakan Kamad/Operator ke role lain.
- Tidak ada role atau portal Proktor permanen. Proktor adalah capability/penugasan sementara pada akun Guru yang tetap mempunyai tepat satu role `GURU`.

## 3. Menu Proktor di Web Guru

Arahan owner terbaru menetapkan bahwa Proktor merupakan menu tambahan di portal Web Guru, bukan portal atau role terpisah.

Untuk implementasi menu dan perilaku, gunakan spesifikasi Master Web Guru §7–8 dan Master Web Admin §8 sebagai acuan fitur dengan penyesuaian berikut:
- Admin Madrasah mengelola penugasan Guru sebagai Proktor.
- Guru yang mempunyai assignment aktif melihat menu Proktor di portal Guru.
- Cakupan akses Proktor dibatasi pada Exam+Room yang ditugaskan dan hanya fungsi pengawasan/monitoring minimum yang memang diperlukan.
- Assignment Proktor tidak memperluas hak akademik Guru, tidak memberi akses bank soal/kunci jawaban, jawaban peserta, nilai, atau hasil.
- Reset Attempt, Force Submit, perpanjangan waktu, takeover/release perangkat, dan tindakan sensitif lain bukan hak otomatis Proktor. Hak khusus memerlukan capability terpisah, otorisasi, alasan, transaksi, dan audit.
- Room hanya untuk penempatan/cakupan monitoring; bukan admission gate atau gelombang ujian. Jadwal serentak tetap mengikuti Exam dan waktu server.
- Assignment revoked/expired harus langsung menolak request berikutnya.

Arahan ini menggantikan catatan Issue #20 W7 yang sebelumnya menyatakan assignment pengawas tidak membuka menu monitoring. Menu Proktor di Web Guru kini merupakan kebutuhan produk; jangan menghidupkan kembali portal Proktor permanen.

## 4. Prioritas platform

- Fokus implementasi sekarang: Backend Laravel, Web Admin Madrasah, Web Guru, dan Web Siswa.
- Pengembangan aplikasi Android dan Web Super Admin ditunda sampai Juni 2027.
- Web Super Admin masa depan hanya mengelola lisensi/pembayaran/entitlement instalasi, bukan data akademik.
- Penundaan Android tidak boleh disalahartikan sebagai bukti bahwa ujian Android native sudah tersedia atau siap. Status platform harus dilaporkan apa adanya.

## 5. Kurikulum sebagai satu ekosistem

Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) adalah satu kesatuan dalam sistem akademik dan pembelajaran, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan.

Integrasi harus menelusuri:
registrasi dan versi sumber kurikulum → tahun ajaran/semester/fase/kelas/mapel → penugasan Guru → CP → TP/ATP → materi/topik → Panca Cinta + Pembelajaran Mendalam + dimensi profil lulusan → QuestionVersion → Assessment → immutable Exam Snapshot → Attempt/Answer/Score → capaian dan laporan historis.

Jangan membentuk kurikulum, database, atau modul KBC terpisah dari Kurikulum Merdeka. Pertahankan provenance, versi, snapshot, dan histori.

## 6. Batas perubahan dan penerimaan

- Jangan mengulang patch yang sudah ada di branch/PR sebelumnya. Audit source dan exact-SHA CI terlebih dahulu.
- Backup byte-exact setiap file existing sebelum patch.
- Jangan menurunkan RBAC, exact-one-role, assignment scope, snapshot, transaksi, idempotensi, fencing, atau constraint database demi meluluskan test.
- Jangan menjalankan migration pada database produksi, merge PR, deployment VPS, atau membuat ZIP final tanpa persetujuan dan seluruh acceptance gate.
- Setiap klaim PASS harus menunjuk exact commit, workflow, job, dan hasil test. Source yang belum diuji tetap NOT VERIFIED.


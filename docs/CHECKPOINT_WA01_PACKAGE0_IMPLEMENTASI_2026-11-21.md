# CHECKPOINT — WA-01 Paket 0 (Implementasi Awal)

Tanggal: 21 November 2026  
Repository: https://github.com/zane0184/cbt_november  
Branch implementasi: `work/wa01-package0-implementation-20261121`  
Baseline source: `2b4321ce032fd5e67cf022da8564a5287b8cef6f`  
Status: **IMPLEMENTASI SOURCE AWAL — MENUNGGU PENGUJIAN RUNTIME**

## AUDIT

- Branch sumber `work/web-admin-w0-20261121` diverifikasi identik dengan baseline pin sebelum implementasi.
- Batas Admin/Guru diperiksa pada policy, API controller, Livewire, route, dan navigasi.
- Admin sebelumnya masih dapat terdaftar pada grant CBT, masuk ke komponen Bank Soal/Manajemen Ujian, melihat shortcut/angka ujian pada dashboard, dan role legacy `OPERATOR` masih menjadi alias akses dashboard.
- `QuestionPublicationController` hanya menyediakan `index/store/destroy`, sedangkan route `apiResource` sebelumnya juga membuka route show/update yang tidak didukung controller.
- Media penugasan Proktor menampilkan email dan query assignment mengambil metadata lebih luas dari yang diperlukan.
- Device fencing, jadwal Guru, lifecycle attempt, sinkronisasi offline, dan layanan penugasan Proktor tidak diubah dalam paket ini.

## DESAIN

- `ADMIN_MADRASAH` bukan role untuk mengelola bank soal, isi/kunci soal, asesmen, ujian, peserta, token, attempt, jawaban, scoring, hasil, atau monitoring CBT.
- Guru tetap menjadi pemilik alur bank soal dan siklus pembuatan/penjadwalan/penerbitan ujian sesuai penugasan dan permission.
- Admin tetap dapat mengelola administrasi dan melakukan penugasan Proktor sementara dengan permission khusus.
- `ADMIN` dan `OPERATOR` tidak menjadi alias runtime untuk Admin Madrasah.
- Pemeriksaan role dilakukan kembali pada setiap request Livewire; policy/API tetap menjadi otoritas server.
- Tidak ada perubahan skema atau operasi database produksi.

## IMPLEMENTASI

Branch implementasi baru dibuat dari baseline pin. File source berikut diperbarui:

- RolePermissionBootstrapper: grant Admin dibatasi pada administrasi, akademik, pengguna, dan penugasan Proktor; grant CBT Admin/Operator lama dicabut melalui bootstrapper ketika dijalankan pada lingkungan yang disetujui.
- QuestionPolicy, AssessmentPolicy, ExamPolicy, ExamAttemptPolicy, ReportPolicy: Admin Madrasah ditolak untuk aksi CBT; kewenangan Guru/Kamad/Proktor yang memang diizinkan tetap dibatasi sesuai policy.
- QuestionBank dan ExamManagement: akses Guru saja, ditolak bagi Admin termasuk akun multi-role.
- Question/Exam/Answer/Student API surfaces: penolakan Admin ditegaskan untuk alur authoring/ujian/jawaban dan portal peserta.
- MediaController: penulisan/penempelan media soal dibatasi untuk Guru; Admin tidak memperoleh akses authoring melalui media.
- ProctorAssignmentManagement: validasi ID dibatasi tenant; daftar assignment dan proktor memakai select/eager-load minimum; email proktor tidak dirender.
- Dashboard Admin: akses hanya role Admin Madrasah aktif; pemeriksaan ulang di boot dan mount; metrik Total Ujian dan shortcut administrasi ujian dihapus.
- Layout dan routes: navigasi CBT hanya untuk Guru non-Admin; role legacy tidak diarahkan ke portal Admin; route question-publications dibatasi pada method controller yang tersedia.
- Regression tests diperbarui dan `AdminCbtBoundaryTest.php` ditambahkan untuk menguji akun multi-role Admin dengan permission lama.

Backup:
- 36 file sumber/test lama disalin ke `docs/backup/wa-01-package0-source-2026-11-21/`.
- 23 file lama yang berubah telah diverifikasi byte-identik terhadap baseline melalui kecocokan Git blob SHA.
- Manifest: `docs/backup/wa-01-package0-source-2026-11-21/MANIFEST.md`.

## PENGUJIAN

- Pemeriksaan source dan diff GitHub dilakukan.
- Pemeriksaan backup: 23 file sumber/test yang berubah cocok byte-identik dengan baseline.
- PHPUnit, PHP lint, route:list, browser/Livewire runtime, API integration, dan regression suite **BELUM DIJALANKAN** karena sesi ini hanya memiliki akses repository GitHub dan tidak menjalankan checkout/runtime PHP.
- Tidak ada hasil PASS runtime yang diklaim.
- Belum ada CI result yang dijadikan bukti.

## STATUS

- Source patch awal sudah berada pada branch implementasi terpisah.
- Paket 0 belum boleh dinyatakan lulus/selesai sampai seluruh tes dijalankan.
- Database produksi tidak disentuh; bootstrapper tidak dijalankan pada database produksi.
- Tidak ada deployment, migration, seeder, permission pivot produksi, atau perubahan VPS.
- Tidak ada PR yang dibuat.

## LANGKAH BERIKUTNYA

1. Ambil branch implementasi pada lingkungan kerja terisolasi dengan PHP 8.3, dependency proyek, dan MySQL test database.
2. Jalankan PHP lint, PHPUnit test terarah, seluruh Feature suite terkait, lalu regression Guru/Siswa, multi-role, tenant isolation, stale Livewire snapshot, API authorization, media metadata, dan scoring.
3. Perbaiki kegagalan hanya setelah backup dan analisis penyebab; jangan mengubah lifecycle jadwal Guru atau layanan Device Lease/fencing di luar scope.
4. Setelah bukti tes lengkap, laporkan hasil aktual dan minta persetujuan terpisah sebelum tindakan database atau deployment.

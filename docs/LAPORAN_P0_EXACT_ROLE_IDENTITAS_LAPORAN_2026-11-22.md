# LAPORAN AUDIT & IMPLEMENTASI — P0 EXACT-ROLE, IDENTITAS TENANT, LOGIN, DAN LAPORAN
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch Agent 3: `work/web-guru-foundation-20261122`
Branch Agent 2 (audit read-only): `work/stage4d-account-lifecycle-20261122`
Source Agent 3 sebelum patch: `702274d5298930f5f1dd13b71b7e402c12868109`
Source commit test Agent 3 terakhir pada tahap ini: `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f`

## 1. AUDIT

### 1.1 Exact-role Portal Guru — P0
Dua komponen berikut sebelumnya hanya memeriksa `hasRole('GURU')` dan menolak `ADMIN_MADRASAH`, sehingga kombinasi GURU+SISWA belum ditolak:
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`

Sumber bersama yang sudah tersedia:
- `App\\Support\\Security\\TeacherPortalAccess::profile()` memeriksa user persisted pada tenant, konsistensi TenantContext, tepat satu role GURU, dan tepat satu TeacherProfile tenant-lokal.
- `App\\Support\\Security\\TeacherAccess::assignedSubjectIds()` menghasilkan mata pelajaran dari assignment aktif dan tenant-scoped.
- Gate/policy operasi inti tetap digunakan oleh kedua komponen.

Test sebelumnya pada `TeacherPortalFoundationTest` menolak admin dan multi-role pada dashboard Guru, tetapi belum menguji dua komponen Bank Soal/Manajemen Ujian pada hydration dan direct action.

### 1.2 Identitas madrasah dan Kamad — Agent 2 (read-only)
Source yang diperiksa:
- `TenantProfileManagement.php` blob `da65d6b9d8536c31c85f3dd2574df65a6be93fb6`
- `Tenant.php` blob `08c43026e61c242d9d0bc7239769f1a0ce38e13c`
- `TeacherProfile.php` blob `81574e9f9cc599a788a9d32bb412ffdaf0e7900f`
- `KamadDashboard.php` blob `8a0fb4d402f69e5e2542cb256e704c0ecf6f7089`
- `tenant-profile-management.blade.php` blob `8d0888567b18240153f3ada5338c2f1590a3351d`

Temuan:
- Modul `TenantProfileManagement` sudah ada dan memuat nama, NSM, NPSN, alamat, telepon, email, logo. Jangan membuat modul profil kedua.
- `Tenant` fillable memuat data identitas tersebut dan `logo_path`; tidak terlihat atribut identitas Kepala Madrasah.
- `TeacherProfile` memuat `employee_identifier`, NIP, NIK, NUPTK, nama, gender, telepon; tidak terlihat atribut status kepegawaian PNS/Non-PNS.
- `KamadDashboard` adalah dashboard statistik, bukan pengelolaan profil Kamad. Belum ditemukan relasi sumber identitas pejabat dari file yang diperiksa.
- Pengelolaan logo memakai Livewire `image,mimes:jpg,jpeg,png,webp,max:2048`, disimpan ke disk public pada direktori `tenants/{tenantId}/branding`; nama file dihasilkan oleh storage.
- Tidak terlihat pemeriksaan signature/magic-byte eksplisit tersendiri di komponen. Validasi Laravel file/image tetap ada, tetapi requirement signature aktual perlu dibuktikan melalui uji.
- File logo baru dibersihkan jika transaksi gagal. Setelah penggantian berhasil, file logo lama tidak dihapus pada implementasi yang diperiksa.
- Preview logo tersedia, tetapi belum ada preview kop laporan lengkap.

### 1.3 Login dan resolusi tenant
Source:
- `AuthWebController.php` blob `b8e04102858652c2fe9042f8f680c511fcbccf63`
- `login.blade.php` blob `2c6580cfd8df434573b6652cd527c7c932a818df`
- `routes/web.php` blob `f012b3de23575e3ef91c8f07ad1c0d7ae780fb21`

Temuan:
- GET login hanya mengembalikan `view('auth.login')`; tenant/branding tidak dikirim ke view.
- Template login masih menampilkan huruf `C` dan tulisan `CBT MADRASAH`.
- Resolver identitas dan LocalInstallationIdentity dipakai saat proses POST/login, bukan untuk menampilkan identitas tenant terverifikasi pada GET login.
- Route GET login tidak berada dalam group middleware auth/tenant yang digunakan pada area aplikasi.
- Dengan demikian branding login per tenant belum terbukti aman dan belum diimplementasikan. Jangan hardcode nama madrasah tertentu.

### 1.4 Kop dan laporan PDF
Source:
- `ReportHeaderService.php` blob `9cab29ad41aecce79a8729bc98f506f5c83a2452`
- `ReportController.php` blob `c78442de0f5b4b40a7773bb2af0cf0ffd852d055`
- `reports/pdf/header.blade.php` blob `fd15e6d112953fb31d1fea8b6196853ac42851bf`
- `exam-results.blade.php` blob `177736de7edfe7c94a643911fbf21c0ac83af1bf`
- `student-results.blade.php` blob `b697997dea5fafadc6d91314fb04b87619050322`
- `exam-summary.blade.php` blob `efa65631d9c72f62c9f3b3b0ef0151a518365b39`
- `ExamResultReportService.php` blob `d57eca45de94cc722826998f995369c56ad323d8`
- `StudentResultReportService.php` blob `abc5eb0964983bd555c880c9d1d8fc4bc588d87b`
- `ExamSummaryReportService.php` blob `45d8f97eb9b2c75164a6f10a403e052b77696552`

Temuan:
- Kop bersama `reports.pdf.header` sudah ada dan dipakai tiga template PDF. Header saat ini menampilkan logo, nama tenant, alamat, NSM, NPSN, telepon, email.
- Baris `KEMENTERIAN AGAMA REPUBLIK INDONESIA` masih hardcoded; perlu dikonfirmasi sumber/konfigurasi institusinya sebelum dianggap benar.
- `ReportHeaderService` mengubah logo menjadi Data URI, tetapi mencari file melalui beberapa kandidat path filesystem. Belum tampak satu kontrak storage/branding tenant yang dipakai bersama login dan PDF.
- Tidak ditemukan blok tanda tangan Kamad/Guru, NIP/status kepegawaian, tempat penerbitan, atau tanggal laporan berbahasa Indonesia pada template yang diperiksa.
- Tidak tampak mekanisme immutable report snapshot/versioned export pada controller/service/template yang diperiksa. Kesimpulan ini dibatasi pada source yang diperiksa; belum boleh diklaim sebagai audit seluruh database.
- Rekap PDF menampilkan satu baris per Attempt, bukan satu baris per peserta unik. Jika seorang siswa mempunyai beberapa Attempt, baris dapat berulang karena `getAllResultsForExport()` mengembalikan seluruh Attempt.
- Template `exam-results` dan `student-results` menampilkan skor/nilai `0` saat score tidak ada. Ini bertentangan dengan aturan bahwa siswa belum dinilai tidak boleh dianggap bernilai nol.
- `ExamSummaryReportService` menghitung statistik dari seluruh baris `ExamAttemptScore`, bukan melalui current-score pointer atau filter FINALIZED. Riwayat skor dapat ikut memengaruhi statistik.
- `StudentResultReportService` menggunakan current-score pointer, tetapi filter `finalized_only` hanya diterapkan bila diminta. Jalur export PDF Guru yang diperiksa tidak memasang filter tersebut.
- Data peserta, jumlah Attempt, dan skor belum dipisahkan sepenuhnya menurut definisi laporan yang diminta.

### 1.5 Status branch dan CI
- Branch Agent 3 dibandingkan dengan HEAD checkpoint `702274d5298930f5f1dd13b71b7e402c12868109`: 29 commit ahead, 0 behind; 19 changed paths (3 source/test dan 16 dokumentasi/backup). HEAD branch tepat sebelum rekonsiliasi dokumen terakhir: `574b0ff603c4101e24bc6e3b0b4a43d4e1fbbaed`.
- Sembilan belas path berubah pada rentang ini: dua komponen, satu test, dua dokumen laporan/checkpoint, dan empat belas file backup/manifest. Tidak ada file Agent 2, migration, schema, Composer, atau database yang diubah.
- Connector tidak menampilkan daftar status/check run untuk commit test `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f`; status CI belum dapat diverifikasi pada saat laporan ini dibuat.
- Branch Agent 2 diperiksa secara read-only melalui ref yang disebut di atas. Compare metadata menunjukkan branch divergen terhadap main (1340 ahead / 15 behind), tetapi connector tidak mengembalikan SHA HEAD branch secara eksplisit. Blob SHA setiap file yang diperiksa dicatat di atas.

## 2. DESAIN

### 2.1 Boundary Portal Guru
Gunakan satu sumber otorisasi yang telah tersedia:
1. user terautentikasi dan tenant_id sah;
2. role database tepat satu dan role tersebut GURU melalui `TeacherPortalAccess`;
3. user persisted pada tenant yang sama dan TenantContext konsisten;
4. tepat satu TeacherProfile tenant-lokal;
5. permission khusus komponen tetap diwajibkan;
6. minimal satu assignment mata pelajaran aktif melalui `TeacherAccess`;
7. Gate/policy tetap mengatur operasi inti dan ownership data.

Livewire `boot()` dan `mount()` tetap menjalankan pemeriksaan. Boot dievaluasi pada request Livewire berikutnya sehingga hydration/action kembali melewati boundary yang sama.

### 2.2 Identitas dan branding
- Kembangkan `TenantProfileManagement` yang sudah ada.
- Agent 2 harus lebih dahulu menetapkan sumber data Kamad, status kepegawaian, NIP, validasi, permission, tenant scope, audit event, dan kontrak layanan.
- Jangan menambah kolom/tabel Kamad sebelum audit seluruh model/migration dan relasi selesai.
- Tetapkan satu layanan identitas/branding tenant untuk Web Admin, GET login, dan kop PDF.
- Resolusi tenant sebelum autentikasi harus menggunakan identifier/domain/installation yang terverifikasi; jangan mengirim branding tenant yang belum terverifikasi.
- Cache key harus menyertakan identitas tenant dan tidak boleh berbagi cache antar-tenant.
- Logo memakai storage disk yang eksplisit, pemeriksaan MIME dan signature aktual, batas ukuran, nama file aman, rollback aman, serta cleanup logo lama setelah file baru dan metadata berhasil tersimpan.
- Preview Admin harus merender kop yang sama dengan PDF.

### 2.3 Laporan
- Satu layanan kop bersama dan satu kontrak identitas penandatangan.
- Guru penandatangan berasal dari assignment/ownership ujian yang sah, bukan user login secara otomatis.
- Kamad berasal dari pengaturan resmi tenant.
- Tentukan aturan satu Attempt representatif per peserta (atau tampilkan Attempt terpisah dengan label eksplisit); jangan menggandakan peserta secara tidak jelas.
- Nilai final hanya dari current-score pointer yang berstatus FINALIZED.
- Nilai yang belum dinilai ditampilkan sebagai status, bukan nol.
- Statistik harus memisahkan peserta unik, Attempt, skor final, dan koreksi manual.
- Penerbitan laporan menyimpan snapshot immutable tenant-scoped berisi identitas kop, penandatangan, periode akademik, data nilai final, waktu/aktor, versi, dan audit event.
- PDF lama harus tetap identik setelah profil, logo, atau identitas penandatangan berubah.

## 3. IMPLEMENTASI

### 3.1 File diubah pada branch Agent 3
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`
  - Menggunakan `TeacherPortalAccess::profile()`, permission `question.view`, dan assignment aktif.
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`
  - Menggunakan `TeacherPortalAccess::profile()`, permission `exam.manage`, dan assignment aktif.
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
  - Menambahkan regresi untuk akun role tunggal, ADMIN, SISWA, GURU+SISWA, ADMIN+GURU, permission hilang, assignment hilang, tenant context mismatch, hydration, dan direct action.

Commit:
- QuestionBank: `901ab9a05ab678c3c8882131d5463d72a0202382`
- ExamManagement: `8ac0253fcf1cafaf0e5808ee66107a691ba0a7e0`
- Test: `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f`

### 3.2 Backup
Folder: `docs/backup/web-guru-exact-role-20261122/`
- `QuestionBank.php.before` — blob SHA `ce69a86c8c33f7ae00f9faffa344ac18ff1c396c`, MATCH.
- `ExamManagement.php.before` — blob SHA `d5380dd931aae00aadb9769fb22bdffcac20ad51`, MATCH.
- `TeacherPortalFoundationTest.php.before` — blob SHA `414a0a7fca80898c4e9d3aceceac31378208aa30`, MATCH.
- Manifest: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`.
- Backup source awal dan snapshot tambahan dibuat sebagai file baru; tidak ada backup lama ditimpa. Manifest akhir mencatat seluruh snapshot yang diverifikasi.

### 3.3 Koordinasi
- Update exact-role diposting pada Issue #8: https://github.com/zane0184/cbt_november/issues/8
- Agent 2 diminta meninjau boundary backend/policy. Issue import tetap terbuka dan tidak dianggap selesai.
- Tidak ada perubahan pada branch Agent 2.

### 3.4 Batas implementasi
Belum mengubah TenantProfileManagement, Tenant, TeacherProfile, AuthWebController, login view, ReportHeaderService, ReportController, PDF templates, migration, schema, Composer, atau database. Belum ada merge, PR, deployment, atau migration produksi.

## 4. PENGUJIAN

| Pengujian | Status | Bukti |
|---|---|---|
| Verifikasi backup source | PASS | 16/16 blob SHA MATCH |
| Audit diff branch Agent 3 | PASS | 29 ahead, 0 behind; 19 changed paths |
| PHP lint lokal | NOT RUN | Tidak ada checkout lokal pada runtime |
| PHPUnit baru | PENDING CI | Test sudah ditambahkan, belum ada status/check run yang dapat diverifikasi |
| Browser QA Livewire | NOT RUN | QA browser dan akun uji belum tersedia |
| Login desktop/mobile | NOT RUN | Belum ada implementasi branding |
| PDF aktual/print | NOT RUN | Belum ada implementasi laporan baru |
| Tenant isolation end-to-end branding | NOT RUN | Menunggu kontrak layanan Agent 2 |
| Database produksi/migration | NOT RUN | Tidak ada akses/perubahan produksi |

Workflow `.github/workflows/web-guru-foundation-ci.yml` mencakup `TeacherPortalFoundationTest` dan `QuestionImportTemplateDownloadTest` pada disposable MySQL 8. Status workflow untuk commit `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f` belum dapat diverifikasi; karena itu tidak ada klaim PASS PHPUnit.

## 5. KESIMPULAN DAN BLOCKER

- P0 exact-role: **IMPLEMENTASI DITAMBAHKAN, BELUM DINYATAKAN SELESAI** sampai CI dan regresi terbukti PASS.
- Import WG-01: tetap berhenti; kontrak Agent 2 belum diputuskan.
- Identitas Kamad/status PNS-NIP: NEEDS DESIGN/CONTRACT dari Agent 2.
- Branding login: menunggu resolver tenant terverifikasi dan layanan branding.
- Kop/laporan: perlu perbaikan aturan attempt, nilai final, statistik, penandatangan, tanggal/tempat, dan immutable snapshot.
- Tidak ada izin untuk merge, deployment, migration produksi, atau klaim siap produksi.

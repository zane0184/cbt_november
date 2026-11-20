# MATRIKS PENGUJIAN DAN ACCEPTANCE — WEB ADMIN MADRASAH

## 1. Format pencatatan hasil

Setiap pengujian wajib mencatat:
- ID test dan tujuan.
- Environment dan versi PHP, Composer, Laravel, MySQL, Node, browser.
- Commit SHA dan branch.
- Database yang dipakai (pastikan disposable; jangan tulis secret).
- Command atau langkah manual yang dijalankan.
- Exit code, jumlah test/assertion, durasi.
- PASS / FAIL / BLOCKED / NOT RUN.
- Log/screenshot/artifact dan lokasi.
- Defect yang ditemukan serta issue/commit perbaikannya.

**PASS** hanya untuk test yang benar-benar dijalankan dan hasilnya diperiksa.  
**BLOCKED** jika environment/dependency/data uji tidak tersedia.  
**NOT RUN** jika belum dicoba.  
SQLite tidak boleh menggantikan klaim MySQL 8 pass.

## 2. Matriks inti

| ID | Area | Skenario minimum | Hasil yang diharapkan | Status awal |
|---|---|---|---|---|
| T-001 | Auth | Admin login/logout/session expiry | Session aman; akses tanpa auth ditolak | NOT RUN |
| T-002 | RBAC | Admin membuka route administratif | Hanya permission yang sah | NOT RUN |
| T-003 | RBAC negative | Admin mencoba route/action authoring Guru | Ditolak server-side | NOT RUN |
| T-004 | Tenant | ID record tenant lain dimasukkan ke URL/form | 403/404 aman; tidak ada data bocor | NOT RUN |
| T-005 | Tenant | Query list/detail/count/export | Semua terikat instalasi/tenant context | NOT RUN |
| T-006 | PII | Dashboard/log/error/export | Tidak membocorkan data pribadi tanpa izin | NOT RUN |
| T-007 | Identitas | Branding portal Admin | Label Panel Admin Madrasah konsisten | NOT RUN |
| T-008 | Sidebar | Menu lebih panjang dari viewport | Scroll independen; semua menu dapat dicapai | NOT RUN |
| T-009 | Responsive | 1366×768, 390×844, tablet portrait/landscape | Layout tidak pecah; drawer dapat dipakai | NOT RUN |
| T-010 | Accessibility | Keyboard, focus, label, contrast, errors | Navigasi dan feedback dapat diakses | NOT RUN |
| T-011 | Akademik | Tahun ajaran/semester | Validasi periode dan status konsisten | NOT RUN |
| T-012 | Rombel | Fase/kelas/rombel/wali kelas | Relasi valid sesuai periode | NOT RUN |
| T-013 | Guru | CRUD data administratif Guru | Validasi, scope, histori sesuai aturan | NOT RUN |
| T-014 | Assignment | Guru kelas/mapel/rombel/periode | Tidak ada assignment ganda/overlap ilegal | NOT RUN |
| T-015 | Siswa | CRUD dan penempatan siswa | Tenant/scope terjaga | NOT RUN |
| T-016 | Promosi | Kenaikan/tinggal kelas | Histori lama dipertahankan; transaksi atomik | NOT RUN |
| T-017 | Kelulusan | Kelulusan kelas 6/alumni | Status alumni tanpa menghapus riwayat | NOT RUN |
| T-018 | Import siswa | Template, preview, duplicate, malformed | Validasi sebelum commit; laporan error | NOT RUN |
| T-019 | Import Guru | Template, preview, duplicate, malformed | Validasi sebelum commit; laporan error | NOT RUN |
| T-020 | Import mapel | Template termasuk Mulok | Kode/nama/jenis tervalidasi | NOT RUN |
| T-021 | Import failure | Partial failure/rollback/retry | Tidak ada setengah data/duplikasi | NOT RUN |
| T-022 | Curriculum | Seed ulang | Idempotent; custom lokal tidak tertimpa | NOT RUN |
| T-023 | Curriculum | Version update | Histori versi akademik terjaga | NOT RUN |
| T-024 | Curriculum | Master resmi vs custom lokal | Sumber dan status jelas | NOT RUN |
| T-025 | Reports | Filter, pagination, export | Data sesuai scope/permission | NOT RUN |
| T-026 | Backup | Backup integrity/permission | Backup dapat diverifikasi dan terlindungi | NOT RUN |
| T-027 | Restore | Restore pada disposable DB | Hasil konsisten; produksi tidak disentuh | NOT RUN |
| T-028 | Audit | Mutasi administratif sensitif | Actor, waktu, alasan, before/after tercatat | NOT RUN |
| T-029 | Exam list | Ujian Guru muncul di list Admin | Sumber data sama; tanpa input ulang | NOT RUN |
| T-030 | Exam list | Mapel/jadwal diubah Guru | List Admin tersinkron | NOT RUN |
| T-031 | Proktor | Admin assign Guru aktif tenant yang sama | Assignment tersimpan dan ter-audit | NOT RUN |
| T-032 | Proktor | Admin pilih Guru tenant lain/nonaktif | Ditolak server-side | NOT RUN |
| T-033 | Proktor | Benturan jadwal | Konflik ditampilkan dan assignment dicegah/ditangani | NOT RUN |
| T-034 | Proktor | Jadwal ujian Guru berubah | Assignment terdampak berstatus perlu ditinjau | NOT RUN |
| T-035 | Proktor | Dua Admin assign bersamaan | Tidak ada double booking/race corruption | NOT RUN |
| T-036 | Proktor | Jadwal berlaku/berakhir | Hak sementara aktif/nonaktif sesuai waktu server | NOT RUN |
| T-037 | Regression | Existing routes/components | Tidak ada regresi lintas modul | NOT RUN |
| T-038 | Performance | Dashboard/list/import pagination | Query terukur; N+1 dan beban berlebihan ditangani | NOT RUN |

## 3. Uji visual wajib

- Desktop 1366×768 dan 1920×1080.
- Tablet portrait dan landscape.
- Mobile 390×844.
- Sidebar scroll dengan daftar menu melebihi tinggi viewport.
- Drawer mobile: buka/tutup, scroll, focus trap/restore sesuai implementasi.
- Tabel lebar, filter, modal, form validation, toast, empty/loading/error.
- Screenshot sebelum/sesudah untuk defect UI utama.

## 4. Uji keamanan minimum

- Authentication/session fixation/expiry.
- Authorization per route dan action.
- IDOR pada semua identifier.
- Tenant isolation pada list, detail, aggregate, export, import, upload, download.
- CSRF dan mass assignment.
- Upload MIME/content/size/path traversal.
- Download private storage dan signed URL.
- Rate limiting untuk endpoint sensitif.
- Audit log tamper/access controls.
- Data leakage pada exception/log/notification.
- Concurrent mutation dan idempotency.

## 5. Gate penerimaan

Web Admin tidak boleh dinyatakan selesai jika:
1. Ada P0 security/integrity defect terbuka.
2. Modul wajib belum memiliki alur UI-backend end-to-end.
3. Negative authorization/tenant tests belum lulus.
4. Impor wajib belum diuji dengan data valid, invalid, duplikat, dan kegagalan.
5. Histori akademik belum diuji.
6. Daftar ujian → penugasan Proktor belum diuji bila integrasi source tersedia; jika blocked, harus dicatat sebagai blocker eksplisit.
7. Pengujian MySQL 8 tidak tersedia namun hasil diklaim lulus.
8. Belum ada validasi visual/responsif dan validasi operasional.
9. Known limitations/residual risks belum diserahkan.
10. Pengguna belum memberi persetujuan penutupan.

Build/CI pass tidak sama dengan siap produksi.



# ADDENDUM TEST CASE — REKONSILIASI ROLE, TENANT, DAN PROKTOR

Semua test baru berstatus NOT RUN sampai benar-benar dijalankan pada environment test MySQL 8/InnoDB. Tes concurrency wajib memakai koneksi/transaksi independen; SQLite tidak membuktikan locking MySQL.

| ID | Kategori | Skenario | Expected result |
|---|---|---|---|
| T-039 | Role | Admin Madrasah login dengan permission profile kanonis | Satu dashboard/sidebar Admin; tidak ada portal Operator terpisah |
| T-040 | Legacy role migration | Fixture akun lama OPERATOR diproses oleh migration sebelum login | Akun tetap ada, role aktif menjadi ADMIN_MADRASAH; tidak ada runtime login/permission branch OPERATOR |
| T-041 | Legacy Proktor migration | Akun lama PROKTOR diperiksa saat preflight | Hanya TeacherProfile valid yang dapat dipetakan ke GURU; tidak ada role/portal Proktor aktif |
| T-042 | Eligibility | Guru aktif tenant yang sama dipilih sebagai pengawas | Diterima tanpa role PROKTOR |
| T-043 | Eligibility negatif | Guru nonaktif/deleted dipilih | Ditolak |
| T-044 | Eligibility negatif | Akun non-Guru dipilih | Ditolak |
| T-045 | Tenant | Guru aktif dan exam berasal dari instalasi yang sama | Assignment dapat dilanjutkan |
| T-046 | Tenant negatif | ID Guru/exam/rombel dari tenant lain dimanipulasi | 403/404/validation failure; tidak ada data bocor |
| T-047 | Interval | Assignment 07:00–09:00 lalu 09:00–11:00 untuk Guru sama | Keduanya boleh |
| T-048 | Interval negatif | Assignment 07:00–09:00 lalu 08:00–10:00 pada exam berbeda | Assignment kedua ditolak |
| T-049 | Kelas negatif | Guru sama ditugaskan pada rombel berbeda dengan waktu overlap | Assignment kedua ditolak |
| T-050 | Non-overlap | Guru sama pada 07:00–09:00 dan 10:00–12:00 | Keduanya boleh |
| T-051 | Race condition | Dua Admin bersamaan menugaskan Guru sama pada interval overlap dan exam berbeda | Tepat satu transaksi commit; transaksi lain ditolak |
| T-052 | Race condition positif | Dua Admin bersamaan menugaskan Guru sama pada interval non-overlap | Keduanya commit tanpa duplicate/lost update |
| T-053 | Locking | Request kedua menunggu lock Guru lalu membaca assignment terbaru | Konflik yang baru commit tetap terdeteksi |
| T-054 | Waktu | Request sebelum starts_at | Akses Proktor ditolak |
| T-055 | Waktu | Request tepat pada starts_at | Akses boleh jika status/permission sah |
| T-056 | Waktu | Request tepat pada expires_at | Akses ditolak |
| T-057 | Revoke | Assignment dicabut saat sesi berjalan | Request berikutnya langsung ditolak |
| T-058 | Histori | Assignment dicabut/diganti | Riwayat lama tetap ada dan dapat diaudit |
| T-059 | Schedule change | Guru mengubah jadwal exam yang memiliki assignment | Semua assignment terdampak menjadi NEEDS_REVIEW |
| T-060 | Schedule review | Guru mencoba memakai assignment NEEDS_REVIEW | Akses ditolak sampai Admin konfirmasi |
| T-061 | Review conflict | Admin mengonfirmasi jadwal baru yang bentrok dengan assignment Guru lain | Konfirmasi ditolak; status tetap NEEDS_REVIEW |
| T-062 | No silent change | Jadwal Guru berubah | Jam/roster lama tidak digeser otomatis; before/after dan actor tercatat |
| T-063 | Admin boundary | Admin memanggil action edit exam, close, monitor, token, grading, result | Ditolak |
| T-064 | Guru ownership | Guru pemilik exam mengubah jadwal melalui jalur sah | Update tercatat dan memicu review assignment |
| T-065 | IDOR | Assignment ID diganti dengan ID milik tenant lain | 404/403; tidak ada perubahan |
| T-066 | Class tampering | rombel_id pada request diganti dengan kelas lain | Ditolak berdasarkan tenant dan scope assignment |
| T-067 | Tenant context | Authenticated user tanpa tenant context melakukan query tenant-scoped | Fail-closed; tidak ada query lintas tenant |
| T-068 | Isolasi instalasi | Dua instalasi/domain memakai database berbeda | Tidak ada query, user, atau data akademik silang |
| T-069 | Domain portability | Paket yang sama dipasang pada dua domain dengan .env dan DB berbeda | Kedua instalasi berjalan terpisah; tidak ada domain hardcode |
| T-070 | Idempotency | Request assignment yang sama dikirim ulang | Tidak membuat assignment ganda; hasil konsisten |
| T-071 | Authorization cache | Hak Proktor dicabut saat session masih terbuka | Request berikutnya memeriksa ulang dan menolak |
| T-072 | Cross-exam | Guru sama ditugaskan overlap pada dua ujian berbeda | Konflik ditolak |
| T-073 | Cross-class | Guru sama ditugaskan pada dua kelas dengan waktu tepat bersinggungan | Sesuai [start,end): tidak overlap bila end pertama sama dengan start kedua |
| T-074 | Audit | Create/change/replace/review/confirm/revoke | Actor, tenant, before/after, reason, timestamp tercatat |
| T-075 | Admin metadata | Admin membuka detail ujian Guru | Hanya metadata read-only; tidak ada form edit akademik |
| T-076 | Guru update | Guru mengubah mapel/jadwal exam melalui jalur yang diizinkan | Admin list membaca record sumber terbaru dan assignment terkait ditinjau ulang |

Status semua T-039–T-076: NOT RUN. Acceptance tidak boleh dinyatakan PASS berdasarkan desain atau keberadaan test file saja.



# ADDENDUM TEST CASE — SOURCE, ROLE CONSOLIDATION, TENANT, PROKTOR

Semua test berikut NOT RUN. Runtime acceptance wajib memakai MySQL 8/InnoDB; SQLite tidak membuktikan row locking atau current-read overlap.

| ID | Test case | Expected |
|---|---|---|
| T-077 | Preflight hitung semua role/pivot/users.role per tenant | Laporan lengkap; tidak ada DML |
| T-078 | Migrasi akun legacy OPERATOR | Akun sama, user ID/password/profile/tenant/histori tetap; role aktif menjadi ADMIN_MADRASAH |
| T-079 | Migrasi akun legacy ADMIN tenant-local | Dipetakan ke ADMIN_MADRASAH; SUPER_ADMIN tidak disentuh |
| T-080 | Bandingkan permission account Admin lama dan Operator lama setelah migrasi | Array permission identik 100%, role aktif sama persis |
| T-081 | Bandingkan seluruh route/action Admin untuk dua akun lama | HTTP/Gate result identik untuk setiap route/action |
| T-082 | Postcondition role | Tidak ada role_user/permission_role/role aktif OPERATOR atau ADMIN; hanya ADMIN_MADRASAH |
| T-083 | DatabaseSeeder | Tidak membuat OPERATOR atau PROKTOR role |
| T-084 | InstallerService | Hanya membuat/menetapkan ADMIN_MADRASAH; tidak ada role Operator |
| T-085 | Static source scan | Tidak ada runtime hasRole(OPERATOR), hasRole(ADMIN), atau role branch Operator |
| T-086 | Akun multi-role/conflicting role | Migration berhenti sebelum perubahan; laporan konflik dibuat |
| T-087 | Legacy PROKTOR dengan TeacherProfile valid | Dipetakan menjadi GURU; assignment historis tetap terikat ke user yang sama |
| T-088 | Legacy PROKTOR tanpa TeacherProfile | Migration berhenti untuk manual review; tidak otomatis diberi GURU |
| T-089 | Guru aktif calon Proktor | GURU + user aktif + TeacherProfile aktif tenant sama dapat dipilih |
| T-090 | Role/portal Proktor | Tidak ada login role/route/dashboard permanen Proktor |
| T-091 | Admin exam boundary | Admin hanya metadata read-only + assignment; close/monitor/token/grade/reopen/answer/result ditolak |
| T-092 | TenantContext kosong pada TenantScoped query | Hasil 0 row (1=0) |
| T-093 | TenantContext kosong pada create TenantScoped | Runtime exception; tidak ada row tersimpan |
| T-094 | X-Tenant-Override dimanipulasi | Diabaikan; tenant tetap dari user terautentikasi |
| T-095 | Database dengan 0 atau 2 Tenant | InstallationIdentityService menolak |
| T-096 | Dua installation identity dalam satu DB | Login/identity creation menolak |
| T-097 | Dua domain, dua database | Masing-masing memiliki tenant/identity/domain sendiri; tidak ada data silang |
| T-098 | Satu DB diakses melalui domain kedua | Ditolak karena registered_domain mismatch setelah guard ditambahkan |
| T-099 | SystemTenantSeeder dijalankan pada DB madrasah | Ditolak/diisolasi; tidak menambah tenant kedua |
| T-100 | User model tanpa TenantContext | Semua jalur query/create fail-closed; termasuk withoutGlobalScopes |
| T-101 | Assignment 07:00–09:00 lalu 09:00–11:00 | Boleh; batas akhir eksklusif |
| T-102 | Assignment 07:00–09:00 lalu 08:00–10:00 | Ditolak |
| T-103 | Guru sama overlap lintas ujian | Ditolak |
| T-104 | Guru sama overlap lintas rombel | Ditolak |
| T-105 | Dua Admin, dua koneksi MySQL, Guru sama, interval overlap | Tepat satu commit; transaksi kedua ditolak setelah lock Guru dan locking-current-read |
| T-106 | Dua Admin, dua koneksi MySQL, Guru sama, interval non-overlap | Kedua commit berhasil |
| T-107 | Revoke bersamaan dengan assignment baru | Lock order konsisten; tidak ada overlap/capability stale |
| T-108 | Reschedule melalui API ExamController::update | Memanggil ExamScheduleChangeService dan menandai semua assignment NEEDS_REVIEW |
| T-109 | Reschedule melalui Livewire ExamManagement::saveExam | Jalur yang sama; semua assignment NEEDS_REVIEW |
| T-110 | Direct Eloquent schedule update di luar service | Ditolak oleh observer/guard |
| T-111 | Reschedule setelah attempt dimulai | Ditolak; tidak mengubah jadwal/assignment |
| T-112 | Reschedule sebelum attempt dimulai | Schedule revision naik; assignment NEEDS_REVIEW; audit before/after tercatat |
| T-113 | Guru menggunakan assignment NEEDS_REVIEW | Ditolak pada request berikutnya |
| T-114 | Admin mengonfirmasi assignment NEEDS_REVIEW yang tidak konflik | Status kembali ACTIVE dan schedule_revision diperbarui |
| T-115 | Admin mengonfirmasi assignment yang konflik | Ditolak; status tetap NEEDS_REVIEW |
| T-116 | Assignment tepat pada starts_at | Akses boleh bila semua status/permission sah |
| T-117 | Assignment tepat pada expires_at | Akses ditolak |
| T-118 | Assignment expired tetapi status DB belum diubah cron | Akses tetap ditolak oleh waktu server |
| T-119 | Assignment revoked | Request berikutnya langsung ditolak |
| T-120 | Assignment EXPIRED/REVOKED/NEEDS_REVIEW/COMPLETED | Semua tidak memberi capability |
| T-121 | Audit CREATE/RENEW/REVOKE | Actor, tenant, before/after, reason, timestamp ada |
| T-122 | Audit RESCHEDULE/NEEDS_REVIEW/CONFIRM | Semua perubahan dan schedule revision tercatat dalam transaksi yang sama |
| T-123 | Audit EXPIRE otomatis | Actor type SYSTEM, event dan waktu tercatat; otorisasi tidak bergantung cron |
| T-124 | Manipulasi assignment ID dari tenant lain | 403/404; tidak ada data bocor/berubah |
| T-125 | Manipulasi exam_id/rombel_id | Ditolak oleh tenant-aware FK/policy/service |
| T-126 | Guru nonaktif atau TeacherProfile deleted | Tidak dapat ditugaskan dan capability yang ada dicabut |
| T-127 | Admin account lama vs Operator account lama pada menu/sidebar | UI identik; tidak ada label/portal Operator |
| T-128 | Audit log gagal ditulis | Transaksi assignment/reschedule rollback |
| T-129 | Idempotency create/renew assignment | Retry tidak membuat duplikasi atau status ganda |


| T-130 | Preserve distinct user accounts | Dua user lama (Admin dan Operator) dimiliki orang berbeda | Dua user ID/password tetap terpisah; hanya role/policy disatukan |
| T-131 | UI naming | Login dengan akun Admin lama dan Operator lama setelah migrasi | UI hanya menampilkan Admin Madrasah; tidak ada label/menu/portal Operator |
| T-132 | Installer terminology | Installer menampilkan istilah Operator Installer untuk token bootstrap | Tetap diperlakukan sebagai operator server/installer, bukan role Operator Madrasah |
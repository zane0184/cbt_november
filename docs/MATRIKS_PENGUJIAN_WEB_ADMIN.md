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
| T-040 | Legacy role | Akun legacy OPERATOR login selama masa kompatibilitas | Dipetakan ke capability Admin yang sama; tidak mendapat grant lebih luas |
| T-041 | Role Proktor | Akun legacy PROKTOR membuka /proktor/assignments | Ditolak; tidak ada portal permanen |
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

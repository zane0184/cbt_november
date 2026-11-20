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

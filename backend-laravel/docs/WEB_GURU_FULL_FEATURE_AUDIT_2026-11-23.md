# AUDIT MENYELURUH FITUR PORTAL WEB GURU
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`
Branch audit: `audit/web-guru-full-feature-20261123`
Source yang diaudit: `work/web-guru-phase1-audit-implementation-20261122`
Source HEAD: `5962646bdcf5a2702ef162c43373815483906e5c`

## Kesimpulan eksekutif

**Status: BELUM SEMUA FITUR BERFUNGSI LENGKAP / BELUM SIAP DINYATAKAN SELESAI.**

Fondasi Portal Guru, bank soal dasar, penyusunan ujian, media soal draft, akses hasil, dan analitik sudah memiliki implementasi nyata. CI pada source HEAD yang diaudit berhasil menjalankan 75 test (366 assertions) di disposable MySQL 8.0 dan memverifikasi 80 tabel InnoDB.

Namun, hasil tersebut tidak membuktikan seluruh fitur portal telah selesai. Masih ada fitur yang memang belum tersedia, integrasi akademik/kurikulum yang belum utuh, keputusan format import DOCX yang belum diimplementasikan, serta beberapa alur penting yang source-nya ada tetapi belum tercakup dalam workflow CI Web Guru yang dipakai.

## 1. AUDIT — matriks fitur

| Area | Status | Temuan |
|---|---|---|
| Dashboard Guru | ADA — TERUJI TERBATAS | Menampilkan ringkasan profil, penugasan aktif, rombel, siswa, asesmen/ujian. Test tenant-scope, jumlah siswa, dan role tersedia. Belum ada browser/E2E visual. |
| Penugasan Saya | ADA — TERUJI TERBATAS | Riwayat penugasan tampil dengan filter tenant dan status periode. Tidak mengubah penugasan; pengelolaan penugasan tetap ranah Admin. |
| Pembelajaran | ADA — PARSIAL | Menampilkan konteks penugasan dan referensi kurikulum terdaftar. Belum membuktikan alur pengelolaan materi/topik dan tujuan pembelajaran lengkap dari Guru. |
| Kurikulum & Akademik | PARSIAL / NEEDS DESIGN | Service masih menyajikan Panca Cinta sebagai literal. `official_cp_verified=false`, `deep_learning_available=false`, dan `graduate_dimensions_available=false`. Belum ada registry sumber resmi, versi, tanggal berlaku, provenance, dan histori yang utuh di UI Guru. |
| Profil Guru | ADA — TERBATAS | Guru dapat mengubah nomor kontak/telepon; perubahan memakai transaksi, row lock, dan audit log. Bukan pengelolaan profil lengkap. |
| Bank Soal — daftar/cari/filter | ADA — TERUJI TERBATAS | Ada daftar, pencarian, filter mapel/status, dan pagination. |
| Bank Soal — authoring | ADA — PARSIAL | Tipe MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, ESSAY tersedia. SHORT_ANSWER tetap diblokir. Field materi/topik, versi kurikulum, fase/kelas, ATP, tingkat kesulitan, level kognitif, dan estimasi waktu belum terintegrasi penuh pada form Guru. |
| Bank Soal — versioning | ADA — TERUJI TERBATAS | Edit versi terbit membuat versi DRAFT baru; ujian menggunakan snapshot. Perlu perluasan pengujian lifecycle versi dan seluruh invariant. |
| Bank Soal — media | ADA — TERUJI | Upload, attach, tampilkan, dan detach media untuk versi DRAFT tersedia. Versi PUBLISHED menolak perubahan. Tiga test media tercakup dan lulus pada CI source HEAD ini. |
| Bank Soal — import | TIDAK SESUAI KEPUTUSAN PRODUK | Source masih menampilkan tombol/route/wizard/parser XLSX dan test XLSX. Keputusan owner 23-11-2026 adalah menghentikan XLSX dan memprioritaskan DOCX. Import DOCX belum tersedia. |
| Asesmen & Ujian — builder | ADA — TERUJI TERBATAS | Pembuatan/edit ujian, jadwal, durasi, batas attempt, pengacakan, pemilihan soal terbit, dan penugasan rombel tersedia. Test workflow mencakup pembuatan ujian, pemilihan soal, penugasan rombel, dan lock. |
| Snapshot ujian | ADA — TERUJI TERBATAS | Snapshot dibuat saat sinkronisasi soal dan ujian dikunci. Namun, referensi versi kurikulum, Panca Cinta, Pembelajaran Mendalam, 8 DPL, materi/topik, dan provenance belum tersalin utuh sebagai snapshot immutable. |
| Token ujian | ADA — SOURCE SESUAI KEPUTUSAN | Token otomatis dibuat ketika Guru mengunci/menerbitkan ujian; token dapat dirotasi setelah ujian terbit. Perlu test eksplisit auto-token, visibilitas sebelum jadwal, dan larangan mulai sebelum jadwal dalam rangkaian integrasi. |
| Penilaian & hasil | ADA — BELUM TERUJI END-TO-END PADA CI INI | Daftar hasil, filter, penilaian manual, finalisasi, buka kembali dengan alasan, publikasi/tarik publikasi hasil, dan bulk finalize ada di source. CI ini menguji boundary akses/hydration dan navigasi, bukan seluruh transaksi grading/finalize/reopen/publish. |
| Analitik ujian | ADA — TERUJI TERBATAS | Ringkasan, distribusi, dan analisis butir memakai skor current pointer. Test CurrentScoreAnalyticsTest dan ItemAnalysisTest lulus pada CI source HEAD. |
| Monitoring ujian | BELUM TERSEDIA SEBAGAI MENU LANGSUNG | Sidebar menampilkan placeholder nonaktif. Akses monitoring pada halaman ujian bergantung izin/penugasan yang sesuai. Belum boleh dianggap fitur monitoring Guru selesai. |
| Pengaturan Guru | BELUM DIIMPLEMENTASIKAN | Sidebar menandai Pengaturan sebagai tahap berikutnya. |
| RTL Arab/harakat dan persamaan | BELUM TERVERIFIKASI | Textarea menerima Unicode, tetapi kontrol RTL/LTR, preview bidi, editor persamaan, dan konsistensi renderer web/Android belum dibuktikan. |
| Keamanan tenant dan role | TERUJI TERBATAS | Test mencakup exact-one role GURU, role dicabut, permission/assignment dicabut, profil/user soft-delete, tenant mismatch, ID lintas tenant, serta pemeriksaan hydration/action pada komponen utama. Belum menggantikan audit policy/API lintas seluruh backend. |

## 2. AUDIT — temuan penting pada source

1. `routes/teacher.php` mengarahkan Guru ke Dashboard, Penugasan, Pembelajaran, Profil, Bank Soal, Import Wizard, dan Asesmen.
2. Hasil/analitik terdaftar di `routes/web.php`; tautan dari sidebar mengarahkan Guru kembali ke daftar ujian untuk memilih ujian. Monitoring dan Pengaturan masih placeholder/nonaktif.
3. `QuestionBank` dan `ExamManagement` memakai boundary `TeacherPortalAccess::profile()`, permission masing-masing, dan penugasan mapel aktif.
4. `QuestionImportWizard` masih menerima XLSX, memanggil `QuestionImportWorkbookParser` dan `ExcelQuestionImporter`. View Bank Soal masih menampilkan tombol “Impor XLSX” dan “Unduh Template”. Ini bertentangan dengan keputusan produk DOCX-only yang dicatat pada Issue #8 dan dokumen lima kontrak.
5. `TeacherCurriculumContextService` belum menyatakan CP resmi terverifikasi dan belum menyediakan Pembelajaran Mendalam/8 DPL sebagai data referensi terverifikasi.
6. `QuestionVersion` belum memiliki kontrak relasi tahun ajaran dan materi/topik yang final. Mapping kurikulum belum melekat secara immutable pada versi soal dan snapshot.
7. `ExamAnalyticsService` dan `ItemAnalysisService` pada source yang diaudit sudah memakai scope skor current pointer. Jadi temuan lama tentang agregasi semua skor FINALIZED tanpa current pointer tidak berlaku lagi pada source HEAD ini.
8. `ResultList` dan `ResultDetail` memiliki transaksi/lock dan pemeriksaan pointer skor untuk finalisasi serta grading. Namun alur tersebut memerlukan pengujian workflow khusus pada CI.

## 3. PENGUJIAN — bukti yang benar-benar tersedia

GitHub Actions:
- Workflow: **Web Guru Foundation — Isolated MySQL Verification**
- Run: [45064554547](https://github.com/zane0184/cbt_november/actions/runs/45064554547)
- Source SHA: `5962646bdcf5a2702ef162c43373815483906e5c`
- Kesimpulan run: SUCCESS
- PHPUnit: **75 passed, 366 assertions**
- Durasi PHPUnit: 16.54 detik
- PHP lint dan route registration: lulus
- Composer validate/install locked dependencies dan platform requirements: lulus
- Disposable MySQL: MySQL 8.0, database kosong diverifikasi sebelum test
- Hasil migrasi test: **80 base tables, seluruhnya InnoDB**
- Database dan user disposable dibersihkan oleh workflow.

Suite yang tercakup:
- TeacherPortalFoundationTest
- TeacherPracticeWorkflowTest
- TeacherQuestionMediaTest
- TeacherResultAccessTest
- TeacherResultNavigationTest
- QuestionImportTemplateDownloadTest
- QuestionImportTest
- QuestionImportWizardTest
- CurrentScoreAnalyticsTest
- ItemAnalysisTest
- PortalLoginRoleBoundaryTest

Batas bukti:
- Workflow ini tidak menjalankan browser E2E, visual QA, uji multi-user serentak, uji beban, UAT madrasah, atau pengujian deployment VPS.
- Test khusus seperti `QuestionBankTest`, `ExamBuilderTest`, dan workflow grading/reopen/finalize yang terpisah tidak termasuk filter PHPUnit pada run ini.
- Warning deprecation metadata doc-comment PHPUnit terlihat di log; bukan kegagalan test, tetapi perlu dirapikan.
- CI ini membuktikan source SHA tersebut lolos suite terpilih, bukan semua fitur Web Guru sudah berfungsi.

## 4. DESAIN — prioritas perbaikan

### P0 — konsistensi keputusan dan alur utama
1. Hapus jalur import soal XLSX dari UI Guru, route, wizard, parser, exporter/template dan test khusus XLSX setelah audit pemanggil/dependency dan backup byte-exact. Jangan menghapus import akun atau dependency yang masih dipakai modul lain.
2. Rancang dan implementasikan import DOCX sesuai kontrak Agent 2: satu `QuestionDraftDTO`, reuse `ImportBatch/ImportBatchRow`, preview/correction/revalidate/confirm, audit, idempotensi, tenant isolation, validasi keamanan DOCX dan private media staging.
3. Lengkapi tes transaksi grading, finalize, bulk finalize, reopen, publish/unpublish hasil, dan current score pointer.
4. Pertahankan SHORT_ANSWER BLOCKED sampai accepted answer mengalir dari authoring ke snapshot privat dan scoring serta seluruh tes integrasi lulus.

### P1 — kurikulum dan bank soal
1. Finalisasi kontrak registry referensi resmi dan provenance.
2. Finalisasi relasi tahun ajaran, materi/topik, versi kurikulum, CP/TP/ATP, KBC/Panca Cinta, PM, dan 8 DPL ke QuestionVersion.
3. Salin referensi versi tersebut ke immutable exam snapshot tanpa mengubah histori ujian lama.
4. Lengkapi authoring UI untuk field akademik yang telah disetujui dan preview RTL/Arab/harakat/persamaan.

### P2 — kelengkapan portal dan pengalaman pengguna
1. Selesaikan navigasi Monitoring sesuai hak Guru/penugasan sementara yang sah.
2. Implementasikan Pengaturan Guru hanya untuk fungsi yang menjadi kewenangan Guru.
3. Lakukan browser E2E dan visual QA desktop/mobile untuk semua menu dan alur utama.

## 5. IMPLEMENTASI — status pada tahap audit ini

- Tidak ada file source aplikasi yang diubah.
- Tidak ada migration, perubahan database, merge, atau deployment.
- Branch ini hanya menyimpan dokumen audit.
- Tidak ada fitur yang dinyatakan selesai hanya berdasarkan keberadaan komponen atau route.

## 6. CHECKPOINT

**Titik lanjut Agent Web Guru:**
1. Verifikasi respons/approval Agent 2 pada Issue #8 untuk kontrak DOCX dan data bersama.
2. Lakukan audit referensi XLSX menyeluruh sebelum penghapusan; buat backup byte-exact dan manifest sebelum mengubah source.
3. Implementasikan penghapusan jalur XLSX pada branch kerja terpisah, tanpa menghapus subsistem import akun atau dependency yang masih digunakan.
4. Setelah itu lanjutkan kontrak DOCX dan pengujian end-to-end.
5. Jangan merge ke main, deploy, atau menyentuh database produksi tanpa persetujuan owner.

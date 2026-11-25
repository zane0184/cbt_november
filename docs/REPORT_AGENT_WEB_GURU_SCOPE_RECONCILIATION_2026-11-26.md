# Laporan Audit Agent Web Guru — Rekonsiliasi Scope Owner dan Source GitHub
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch audit: `audit/web-guru-scope-reconciliation-20261126`
Basis source: `work/guru-docx-import-e2e-20261125`
Exact source HEAD yang diaudit: `ac2b7539bc184f200199aa502ef56d567dc47b36`
Parent perubahan parser pada sesi ini: `2f19e7f90eb13b6ae40c0ca572dfa942a57864a3`
Commit lanjutan UI/multi-section: `ac2b7539bc184f200199aa502ef56d567dc47b36`
Branch audit dibuat dari exact source HEAD di atas. Tidak ada source aplikasi yang diubah oleh laporan ini.

## Mandat dan batas
- Sumber informasi proyek hanya GitHub. Tidak menggunakan ZIP baseline, ChatGPT Library, Project Files, atau catatan di luar GitHub.
- Peran agent saat ini: Web Guru. Fokus audit dan kelanjutan portal Guru serta kontrak bersama yang memang menjadi dependensinya.
- Jangan mengulang fitur/patch yang sudah ada; bandingkan source aktual, backup, checkpoint, PR, issue, dan CI exact SHA.
- Android native dan Web Super Admin ditunda sampai Juni 2027.
- Satu ZIP generik untuk seluruh madrasah, tetapi setiap madrasah memiliki instalasi, database, storage, cache, queue, log, dan backup sendiri. Tidak ada shared academic database atau pertukaran data akademik antar-madrasah. Integrasi pusat masa depan hanya lisensi/pembayaran.
- Jangan menghapus tenant column/schema secara massal. Tenant legacy/system registry harus dinilai dalam konteks instalasi lokal; perubahan skema besar perlu audit migration/FK/installer/backup dan acceptance tersendiri.
- Role aktif tepat ADMIN_MADRASAH, GURU, SISWA. Pekerjaan Operator dilebur ke Admin Madrasah. Kamad dibekukan/tidak diberi login atau authorization baru; histori tidak dihapus diam-diam. Proktor adalah capability/menu tambahan sementara pada Web Guru, bukan role/portal.
- Kurikulum Merdeka + KBC/Panca Cinta, Pembelajaran Mendalam, dan delapan dimensi profil lulusan merupakan satu kesatuan; tidak boleh dibuat dua ekosistem terpisah.
- Setiap perubahan file existing harus didahului backup byte-exact. Tidak ada merge, migration produksi, deployment, atau ZIP final tanpa gate dan persetujuan owner.

## AUDIT

### 1. Source dan rekonsiliasi checkpoint
- Repository default branch: `main`, tetapi snapshot main tidak berisi seluruh source aktif.
- Checkpoint koordinator 26-11-2026 sebelumnya menggunakan basis source `b451989b1ca2ac39b4ea16746914f6d28d1169a8`.
- Branch Web Guru kemudian bergerak ke `ac2b7539bc184f200199aa502ef56d567dc47b36`; checkpoint b451 tidak lagi cukup untuk menyatakan kondisi source saat ini.
- Branch source memiliki 1.905 tree entries pada commit ac2b.
- Branch audit ini dibuat dari ac2b agar laporan tidak mengubah branch implementer.
- Riwayat branch memuat commit `2f19e7f90eb13b6ae40c0ca572dfa942a57864a3` untuk parser tabel/checkbox/stimulus/bagian mapel dan commit berikutnya ac2b untuk guard multi-subject dan preview struktur tabel. Keduanya harus diperlakukan sebagai source yang sudah ada; jangan membuat ulang patch yang sama.

### 2. Workflow dan artefak terbaru
**Build Standalone Web CBT Deployment ZIP**
- Run ID: `45708540579`
- Exact SHA: `ac2b7539bc184f200199aa502ef56d567dc47b36`
- Status: SUCCESS.
- Artifact ID: `15066502225`
- Nama: `cbt-madrasah-web-deployment-ac2b7539bc184f200199aa502ef56d567dc47b36`
- Ukuran: 19.780.148 byte.
- Artifact belum diterima sebagai ZIP final: laporan ini tidak mengunduh/mengurai artifact, tidak melakukan independent clean extraction, browser E2E, staging, atau UAT.

**DOCX Import Parser CI**
- Run ID: `45708540515`
- Exact SHA: `ac2b7539bc184f200199aa502ef56d567dc47b36`
- Status saat audit: PENDING; belum ada conclusion.
- Jangan mengutip CI lama #45580930015 atau #45618940586 sebagai bukti bahwa perubahan parser pada ac2b lulus.
- Full PHPUnit/parser acceptance untuk exact SHA ac2b belum terbukti.

### 3. Pekerjaan Web Guru yang sudah ada — jangan diulang
- Dashboard, assignment, halaman pembelajaran/kurikulum, materi, profil Guru, Bank Soal, asesmen/Exam Builder, hasil/analytics, dan media soal sudah memiliki implementasi pada source branch.
- Jalur Import Soal DOCX sudah tersedia pada `/guru/bank-soal/import-docx`, dengan template, parser, preview, koreksi, mapping CP/TP, penyimpanan draft, batch/idempotency/audit, serta test.
- Source branch memiliki dukungan yang dilaporkan untuk PG, PGK, B/S tunggal, TRUE_FALSE_GROUP tiga pernyataan, ESSAY manual review, dan shared stimulus. MATCHING tetap belum boleh diaktifkan tanpa acceptance runtime lengkap; SHORT_ANSWER tetap BLOCKED.
- Import XLSX soal lama telah dihapus pada PR #19; jangan hidupkan kembali. PhpSpreadsheet tetap dibutuhkan untuk administrasi Guru/Siswa Admin dan export.
- Import Siswa XLSX/CSV merupakan fitur Admin yang sudah ada; bukan tugas Web Guru untuk dibuat ulang.
- Commit ac2b sudah mengubah UI Import DOCX/Bank Soal dan menambah guard multi-section serta preview struktur tabel; audit source sebelum patch lanjutan.
- Backup DOCX dan UI tersimpan di direktori backup yang tercatat pada branch, termasuk `backend-laravel/backups/guru-docx-import-e2e-20261125/`, `backend-laravel/backups/docx-multisection-ui-20261126/`, serta `backend-laravel/backups/docx-table-stimulus-20261126/`. Periksa manifest masing-masing sebelum perubahan selanjutnya.

### 4. Kontrak owner untuk import DOCX
Acuan GitHub:
- Issue #9 — kontrak import DOCX.
- `docs/ADDENDUM_OWNER_DOCX_FIXTURE_AND_FLEXIBLE_IMPORT_2026-11-25.md`.
- `docs/ADDENDUM_EXTENSIBLE_DOCX_PARSER_QUESTION_TYPES_2026-11-25.md`.
- `docs/CHECKPOINT_GURU_DOCX_IMPORT_2026-11-25.md`.
- `docs/REPORT_GURU_DOCX_IMPORT_E2E_2026-11-25.md`.

Ketentuan yang tetap wajib:
- DOCX guru cukup berisi soal, opsi/pernyataan, dan kunci jawaban. Tidak boleh mewajibkan kisi-kisi/identitas administrasi.
- Fixture kisi-kisi hanya fixture test, bukan seed, template wajib, sumber kurikulum, atau data akademik.
- Jangan mengimpor tabel kisi-kisi/administrasi sebagai soal; unknown/ambiguous content harus ditandai NEEDS_REVIEW/BLOCKED, bukan ditebak.
- Harus mempertahankan provenance locator (part, urutan, paragraph/table/row/cell, relasi media bila ada) tanpa mengarang nilai.
- Jawaban dapat berada di akhir dokumen atau setelah soal; parser tidak boleh menebak kunci.
- Multi-mapel hanya dipisah bila penanda jelas. Bila ambigu, minta konfirmasi Guru.
- Preview/koreksi/revalidation wajib dilakukan sebelum draft disimpan; item invalid/ambigu tidak boleh tersimpan diam-diam.
- Gambar/diagram/grafik/formula/Arabic harakat/RTL dan automatic numbering belum boleh diklaim didukung sebelum extraction/render/persistence/snapshot/runtime acceptance benar-benar lulus.
- Parser recognition tidak mengaktifkan runtime type. Publish gate harus fail-closed.
- PG/PGK/B/S tunggal mengikuti kontrak runtime existing setelah regression. TRUE_FALSE_GROUP harus tepat tiga pernyataan dengan ID stabil dan jawaban boolean per ID. ESSAY manual review. MATCHING belum aktif untuk importer. SHORT_ANSWER tetap blocked.

### 5. Kurikulum Merdeka + KBC — gap yang masih nyata
- Issue #14 tetap OPEN: mapping kurikulum yang sekarang belum dibuktikan versioned per QuestionVersion dan belum disalin ke immutable Exam Snapshot.
- Issue #15 tetap OPEN: Pembelajaran Mendalam dan delapan dimensi belum memiliki reference dataset resmi terverifikasi/provenance.
- `TeacherCurriculumContextService` sebelumnya tercatat mengembalikan `deep_learning_available=false` dan `graduate_dimensions_available=false`; verifikasi source terbaru diperlukan sebelum mengubah status.
- CP resmi tetap PENDING sampai artefak resmi dan checksum/provenance terverifikasi.
- Jangan membuat data CP/PM/dimensi sintetis, jangan menebak mapping dari label, dan jangan mengklaim integrasi Kurmer+KBC selesai hanya karena pilihan mapping tampil di UI.
- Jangan mengubah `ExamSnapshotService`/ScoringService atau schema shared tanpa kontrak tertulis pemilik backend/CBT dan acceptance MySQL 8.

### 6. Proktor Web Guru
- Keputusan owner: Proktor adalah menu/capability tambahan pada akun Guru yang tetap role GURU; tidak ada role/portal Proktor.
- Assignment sementara dan scope Room/Exam harus divalidasi server-side pada seluruh route/query/action.
- Default monitoring-only; tidak boleh melihat soal/kunci/jawaban/nilai atau melakukan reset/force-submit/extension/takeover.
- Proktor tidak boleh dimunculkan sebagai menu operasional lengkap sebelum marker jenis ujian terpusat (UTS/UAS/UM), authority penetapan, Room scope, lifecycle/revoke, dan negative tests kanonis tersedia.
- Source sebelumnya mencatat route assignment berada di Admin dan menu/route Web Guru belum lengkap. Ini perlu dicek ulang pada source ac2b sebelum patch; jangan bypass fail-closed gate.

### 7. UI/UX Web Guru
- Addendum owner 26-11-2026 meminta pengalaman yang familiar dengan eLearning Madrasah Kemenag era Kurtilas, tetapi sistem akademik tetap Kurmer+KBC.
- Arahan tersebut belum merupakan desain rinci yang disetujui dan addendum melarang perubahan kode sebelum audit UI serta rancangan disetujui.
- Untuk Web Guru, audit halaman/layout/sidebar/dashboard/alur assignment → pembelajaran → Bank Soal → asesmen/ujian → hasil/laporan, responsive, aksesibilitas, loading/error/empty states.
- Jangan mengganti engine, kurikulum, API, model, atau CBT backend hanya demi meniru visual lama.
- Buat daftar rancangan halaman/wireframe dan tunggu persetujuan owner sebelum patch UI global.

### 8. Issue/PR GitHub yang relevan
- Issue #9 DOCX import: OPEN; implementasi sudah berkembang, perlu rekonsiliasi acceptance terhadap source ac2b, bukan mengulang parser.
- Issue #14 mapping QuestionVersion/CP-TP-ATP-KBC ke immutable snapshot: OPEN.
- Issue #15 provenance Pembelajaran Mendalam/delapan dimensi: OPEN.
- Issue #17 audit/design lima kontrak: OPEN/DRAFT; dokumentasi saja, bukan implementasi backend.
- PR #19 penghapusan XLSX question-import: OPEN/DRAFT dan branch lama; jangan merge wholesale atau hidupkan kembali XLSX importer.
- PR #17 kontrak kurikulum/import: OPEN/DRAFT; koordinasi dengan owner backend.
- PR #23 terkait parser DOCX pernah tercatat DRAFT pada branch lain; jangan anggap sama dengan branch ac2b atau merge otomatis.
- PR #27 Import Guru adalah pekerjaan Web Admin, bukan importer soal Web Guru.
- PR/issue lama perlu dicek head/base/CI aktual sebelum integrasi.

## DESAIN — urutan kerja Web Guru yang disarankan
1. **Gate pertama: selesaikan pemeriksaan exact SHA ac2b.** Tunggu/ambil hasil DOCX Parser CI; jika gagal, baca log dan perbaiki hanya regression yang terbukti, dengan backup baru.
2. Audit acceptance DOCX owner fixture + synthetic fixtures: answer key setelah soal/di akhir, satu mapel, multi-mapel jelas/ambigu, tabel soal vs tabel kisi-kisi, PG/PGK/B-S, shared stimulus, negative OOXML. Jangan membangun ulang route/template/importer/batch yang sudah ada.
3. Periksa UI preview terbaru pada source ac2b untuk memastikan subject sections, table rows, blocked features, provenance, error/warning, dan required review benar-benar dapat dipahami/dikoreksi Guru sebelum save.
4. Tutup kontrak mapping QuestionVersion → immutable Exam Snapshot hanya setelah pemilik backend menyetujui kontrak dan menyiapkan test MySQL 8. Jika dependency belum tersedia, dokumentasikan BLOCKED.
5. Dapatkan artefak resmi CP/PM/delapan dimensi beserta provenance/checksum. Tanpa bukti, biarkan status unavailable/PENDING.
6. Audit desain UI Guru berbasis familiaritas eLearning Kemenag dan minta persetujuan wireframe sebelum perubahan visual global.
7. Audit Proktor Guru setelah marker ujian pusat/authority/Room scope diselesaikan; fail-closed sampai seluruh gate tersedia.
8. Setelah fitur Web diterima, verifikasi ZIP exact SHA melalui download/extract/checksum/secret scan, browser-to-real-Laravel E2E, staging, backup/restore, dan UAT. Jangan mengklaim siap distribusi dari workflow build saja.

## IMPLEMENTASI
- Tidak ada source aplikasi, migration, workflow, database, VPS, atau PR implementer yang diubah oleh audit ini.
- Branch audit dibuat dari `ac2b7539bc184f200199aa502ef56d567dc47b36`.
- Dua dokumen audit/checkpoint ditambahkan ke branch audit ini.
- Catatan transparansi: riwayat branch source memuat commit parser `2f19e7f...` dan UI guard `ac2b753...` yang sudah ada saat instruksi owner ini diterima. Audit ini tidak mengulang atau menimpa commit tersebut.

## PENGUJIAN
- GitHub repository, branch inventory, tree recursive, checkpoint, laporan, addendum owner, issue, PR, workflow run, dan artifact metadata diperiksa.
- Deployment ZIP workflow #45708540579: SUCCESS pada exact SHA ac2b; artifact 19.780.148 byte.
- DOCX Parser CI #45708540515: PENDING pada exact SHA ac2b; belum PASS.
- Tidak menjalankan PHPUnit lokal, browser E2E, independent download/extract, staging, VPS, migration produksi, atau UAT.
- Tidak ada klaim siap produksi/siap distribusi.

## STATUS
**AUDIT GITHUB WEB GURU DIPERBARUI — DOCX PARSER CI EXACT SHA MASIH PENDING — KURIKULUM SNAPSHOT/PROVENANCE, PROKTOR GURU, UI/UX ACCEPTANCE, DAN ZIP FINAL MASIH TERBUKA.**

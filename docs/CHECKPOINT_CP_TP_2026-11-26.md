# Checkpoint CP/TP — 26 November 2026

Repository: `zane0184/cbt_november`
Branch pencatatan: `audit/coordinator-continuation-20261126`
Commit laporan audit: `4dc0ea552c8875446a211748623052000efaac1f`
Laporan lengkap: [REPORT_CP_TP_AUDIT_2026-11-26.md](REPORT_CP_TP_AUDIT_2026-11-26.md)

## Mandat aktif
Hanya memperbaiki CP/TP/ATP pada Web Admin, Guru, Siswa dan integrasi backend. Android dan Web Super Admin ditunda sampai Juni 2027. Tidak mengulang patch TP/ATP provenance atau QuestionVersion snapshot yang sudah ada.

## Source yang diaudit
- Branch source terbaru: `work/rombel-docx-completion-20261126`
- Exact HEAD: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
- Tree: 2.053 entri.
- Audit hanya dari GitHub; ZIP baseline, Library, Project Files, VPS dan DB produksi tidak dibuka.

## Posisi pekerjaan yang benar-benar terbukti

### Selesai pada source (tidak boleh dibuat ulang tanpa bukti regresi)
- Admin registry versi kurikulum, sync katalog mapel dan mapping mapel lokal.
- Struktur fase/kelas/mapel, Panca Cinta, struktur PM dan delapan dimensi pada bootstrap seeder.
- Guru TP/ATP authoring berbasis assignment, CP parent, provenance, KBC/PM mapping.
- Guard provenance CP pada create TP, create ATP dan publish.
- Mapping kurikulum pada QuestionVersion.
- Salinan konteks CP/TP/ATP/framework ke immutable ExamQuestionSnapshot.

### Belum selesai / blocker
1. Halaman `/admin/curriculum` masih memakai model/tabel legacy `LearningOutcome/LearningObjective`; tidak membaca canonical registry. Halaman itu secara eksplisit read-only legacy.
2. Belum ada row CP resmi pada registry `MultiLearningOutcome` yang berasal dari artefak resmi terverifikasi.
3. PDF asli BSKAP 046/H/KR/2025, Kepdirjen Pendis 9941/2025 beserta annex CP MI, dan naskah/annex BKPDM 020/2026 belum tersedia di source GitHub untuk verifikasi bytes/checksum. 9941 tetap PENDING.
4. Tanpa CP official checksum verified, Guru tidak dapat memilih CP dan membuat TP/ATP resmi; question mapping dan exam snapshot yang mewajibkan CP juga tertahan.
5. Issue #14/#15 masih OPEN; deskripsi awal #14 sebagian stale terhadap source terbaru, perlu rekonsiliasi status berdasar test exact SHA.
6. Belum ada acceptance end-to-end Admin registry → Guru TP/ATP → QuestionVersion → Exam snapshot → payload Siswa pada exact HEAD 841a.

## Pekerjaan berikutnya
1. Sediakan artefak sumber CP asli melalui GitHub (bukan ringkasan/salinan teks); verifikasi penerbit, nomor, versi, halaman/annex, ukuran dan SHA-256.
2. Implementasikan dataset CP per fase/mapel/elemen dari dokumen resmi; jangan gunakan synthetic test fixture sebagai seed produksi.
3. Ganti tampilan legacy Admin agar membaca canonical CP registry dan TP/ATP Guru secara read-only; pertahankan larangan Admin membuat TP/ATP.
4. Jalankan acceptance MySQL 8/InnoDB pada exact commit: no-data, provenance fail, CP valid, TP, ATP, question mapping, immutable snapshot, Siswa payload, dan histori.
5. Rekonsiliasi Issue #14/#15 dan update checkpoint dengan bukti CI exact SHA.

## Catatan keselamatan
- Tidak ada source aplikasi, migration, seeder, database, VPS atau deployment yang diubah pada tahap audit ini.
- Tidak ada backup source dibuat karena tidak ada source file yang diedit.
- Test baru tidak dijalankan pada audit ini. Run #182 pada SHA 185f6d... adalah bukti historis untuk gate provenance/non-CP, bukan bukti CP official positive path atau CI pada HEAD 841a...
- Jangan mengubah status CP PENDING atau mengisi CP sintetis untuk membuat UI tampak penuh.

**Status checkpoint: AUDIT SELESAI — IMPLEMENTASI CP OFFICIAL BLOCKED OLEH ARTEFAK SUMBER — ADMIN LEGACY VIEW GAP TERIDENTIFIKASI — BELUM END-TO-END.**


## Addendum — penelusuran sumber publik (26 November 2026)
Atas arahan koordinator, penelusuran tidak berhenti pada file yang tidak ada di repo. Manifest sumber publik dibuat pada `docs/CP_TP_SOURCE_RECOVERY_MANIFEST_2026-11-26.md` (commit `8c64f2b784210d1de23331c2ce574fe3fcf9b1e5`).

Temuan tambahan:
- PDF salinan BSKAP 046/H/KR/2025 Lampiran II SD/MI ditemukan pada host publik; masih perlu diunduh, hash, dan cross-check.
- Siaran pers resmi Kemendikdasmen 23 Juni 2026 mengonfirmasi BKPDM 020/2026 hanya mengubah CP Agama dan Budi Pekerti. Naskah keputusan dan lampirannya tetap harus diperoleh dan diverifikasi.
- Salinan isi Kepdirjen Pendis 9941/2025 dan folder unduh PDF/DOCX lampiran ditemukan melalui sumber pihak ketiga. Status tetap `UNVERIFIED_COPY` sampai tanda tangan, nomor, lampiran MI, bytes, dan hash dicocokkan dengan sumber resmi.
- Belum ada dataset CP yang diimport, tidak ada file aplikasi yang diubah, tidak ada migration/DB/production action, dan belum ada test baru.
- Langkah berikutnya adalah akuisisi artefak PDF, verifikasi hash/provenance, ekstraksi teks persis beserta nomor halaman, lalu importer idempoten + acceptance test disposable. Jangan mengisi registry dengan data sintetis.

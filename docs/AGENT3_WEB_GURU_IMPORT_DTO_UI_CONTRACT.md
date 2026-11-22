# Web Guru — Draft Kontrak UI Import XLSX/DOCX

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
Baseline HEAD sebelum dokumen: `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`  
Issue: #8 XLSX/PDF dan #9 DOCX — keduanya masih OPEN.

## AUDIT

Source import soal saat ini belum menyediakan alur upload file → preview → koreksi → confirm → report untuk XLSX/DOCX. Import endpoint yang telah diaudit menerima JSON `rows[]`; importer Excel menerima baris yang sudah diparsing, bukan parser workbook end-to-end. Subsystem import Guru/Siswa yang ada bukan otomatis subsystem import soal.

Komentar Agent 2 pada Issue #8 mengusulkan:
- XLSX dan DOCX sebagai adapter terpisah.
- Satu `QuestionDraftDTO`.
- Reuse `ImportBatch`/`ImportBatchRow`.
- Satu lifecycle staging dan idempotency.
- Tidak ada auto-publish.
- SHORT_ANSWER BLOCKED; ESSAY manual.
- Tidak ada dependency DOCX yang disetujui.

Komentar Agent 2 pada Issue #9 menyatakan DOCX adapter menuju DTO dan lifecycle yang sama, menyimpan source locator sebagai metadata staging, tanpa dependency, migration, batch table, atau service kedua.

**Status kontrak: PROPOSAL / NEEDS EXPLICIT APPROVAL.** Belum ada persetujuan field-level DTO, endpoint, error schema, security limits, media lifecycle, mapping kurikulum, maupun persistence contract.

## DESAIN — Kebutuhan UI, bukan definisi backend final

### Jalur upload terpisah

1. **XLSX** — unduh template, pilih file, validasi file, upload.
2. **DOCX** — pilih file, pilih parser profile/document pattern jika backend menyediakan, upload.
3. PDF/OCR tidak masuk tahap ini; tetap ditunda.

Kedua jalur harus menampilkan hasil ke satu tampilan draft yang sama. Nama field di bawah adalah kebutuhan UI; Agent 2 harus menetapkan nama dan tipe DTO final.

### Field draft yang diperlukan UI

| Kelompok | Data yang perlu ditampilkan/diedit |
|---|---|
| Identitas | draft row ID, nomor urut, source locator/range, checksum/fingerprint bila disediakan |
| Soal | stem/content segments, tipe soal, bahasa/arah teks, bobot/poin |
| Opsi | opsi A-D, urutan, isi, pasangan matching bila relevan |
| Kunci | answer-key candidate, sumber marker eksplisit, status verifikasi Guru; jangan pernah auto-accept bila ambigu |
| Pembahasan | explanation/content segments |
| Akademik | subject, kelas/fase, tahun ajaran, versi kurikulum, CP/TP/ATP dan KBC mapping hanya dari referensi tenant resmi |
| Media | media references, image/table/equation segments, status ekstraksi |
| Validasi | warning/error codes, pesan, lokasi sumber, needs-review reason |
| Status | extraction status, review status, persistence status dipisahkan |

### Status yang harus dibedakan

- **Ekstraksi:** queued / processing / extracted / partial / failed.
- **Review draft:** ready / needs_review / corrected / rejected.
- **Penyimpanan:** not_confirmed / confirming / saved_draft / save_failed / cancelled.

Status final backend harus disetujui Agent 2; label di atas adalah kebutuhan semantik UI, bukan enum final.

### Lifecycle UX

Upload → pemeriksaan keamanan → ekstraksi → staging → preview → koreksi Guru → revalidasi server → confirm → simpan DRAFT → audit/report.

UI harus:
- Menampilkan progress dan hasil per baris/soal.
- Menampilkan warning yang dapat ditindaklanjuti.
- Mempertahankan source locator agar Guru dapat membandingkan hasil ekstraksi dengan dokumen.
- Menandai konten yang tidak terbaca/berubah sebagai warning; media, tabel, persamaan, Arabic/RTL dan harakat tidak boleh hilang diam-diam.
- Meminta konfirmasi eksplisit sebelum persist.
- Menyediakan ringkasan jumlah saved draft, needs review, rejected dan failed sesuai response backend.
- Memungkinkan retry hanya bila idempotency contract mengizinkan.
- Tidak menawarkan tombol publish dari alur import.

### Aturan tipe soal

Tetap hanya enam tipe yang didukung source:
- MULTIPLE_CHOICE
- MULTIPLE_RESPONSE
- TRUE_FALSE
- MATCHING
- SHORT_ANSWER
- ESSAY

SHORT_ANSWER harus ditandai BLOCKED dan tidak boleh dipilih/disimpan sampai accepted answers tersimpan, masuk ExamSnapshot dan dibaca ScoringService. ESSAY tetap manual review. Tidak ada opsi E atau tipe ketujuh.

Kunci jawaban hanya candidate jika marker eksplisit dan mapping pasti. Marker hilang/ambigu, nomor tidak cocok, atau opsi tidak lengkap → NEEDS_REVIEW. Dilarang AI/heuristik menebak kunci.

## DESAIN — Kontrak yang harus diputuskan Agent 2

Sebelum UI terhubung, Agent 2 harus menyetujui secara eksplisit:
1. Endpoint upload, preview, correction/update, confirm, cancel, report dan response errors.
2. Field/type/nullable/enum final `QuestionDraftDTO`.
3. Reuse `ImportBatch`/`ImportBatchRow`, tenant/type/checksum uniqueness, idempotency dan retry semantics.
4. Encryption, private temporary storage, retention, cleanup dan audit event.
5. Duplicate detection dan partial failure semantics.
6. Exact-role GURU, permission, tenant, assignment, subject, grade/fase dan academic-year validation server-side.
7. Kurmer/KBC mapping, version/provenance dan source of truth.
8. MediaAsset attachment, file limits, image/table/equation/RTL preservation and warnings.
9. Semua persisted questions berstatus DRAFT.
10. Parser DOCX library, license, PHP 8.3/Laravel 13 compatibility, security/resource limits sebelum dependency.

Agent 3 memiliki UI Guru saja. Agent 2 memiliki API, DTO, parser, batch, staging, persistence, validation, security, idempotency, media dan snapshot. Tidak membuat batch service kedua.

## IMPLEMENTASI

Tidak ada parser, endpoint, DTO, dependency Composer, schema, storage, batch service, atau UI upload yang dibuat. Dokumen ini hanya rancangan kebutuhan UI untuk dibahas dengan Agent 2.

## PENGUJIAN

Belum ada uji parser, upload, staging, preview, correction, persistence atau browser UI. Issue #8 (6 komentar) dan #9 (1 komentar) masih OPEN. Proposal Agent 2 belum menjadi approval implementasi.

**Gate:** tunggu persetujuan eksplisit kontrak backend sebelum membuat UI yang memanggil endpoint import.

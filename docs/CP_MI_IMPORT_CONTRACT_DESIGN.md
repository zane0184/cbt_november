# Kontrak Import CP MI — Desain Aman

**Status:** DESAIN, belum implementasi. Disusun setelah audit source branch `work/rombel-docx-completion-20261126`, commit source `841a1dd90dbe0277026fade1511a39b5dc9216b6`.

## 1. Keputusan data
- CP resmi MI disimpan di `multi_learning_outcomes` sebagai `outcome_type=CP`.
- Sumber dokumen disimpan di `curriculum_reference_sources`.
- Struktur fase/jenjang/mapel/elemen tetap pada `multi_curriculum_structures`.
- TP/ATP tidak masuk ke tabel CP; tetap dibuat Guru pada `tenant_learning_outcomes` dengan relasi `parent_cp_id`.
- Dataset staging tidak langsung aktif. Status sumber yang diterima saat ini belum `ARTIFACT_CHECKSUM_VERIFIED`, sehingga service Guru yang mewajibkan checksum resmi tidak boleh dipintas.
- Satu instalasi madrasah tetap memiliki database sendiri. Identitas `tenant_id` yang sudah ada hanya scope internal aplikasi/defense-in-depth, bukan izin berbagi data akademik lintas madrasah.

## 2. Pemetaan skema existing
Berdasarkan migration:
- `2026_11_05_190007_create_multi_learning_outcomes_table.php`: CP mempunyai struktur, kode, tipe, deskripsi, status, dan unique key struktur+kode.
- `2026_11_23_000001_add_canonical_web_curriculum_contract.php`: menambahkan provenance `source_id`, `source_locator`, `verification_status`, `source_checksum`, metadata, `is_official`, `is_editable`; membuat registry sumber dan tabel TP/ATP per tenant/penugasan.
- Model `MultiLearningOutcome` sudah menyediakan field-field provenance di atas.
- `CurriculumReferenceSource` mengikat sumber ke owner tenant dan menyimpan `checksum_sha256`, `archive_path`, status verifikasi.
- `TeacherCurriculumContextService` hanya menganggap CP siap dipilih jika sumber berstatus `ARTIFACT_CHECKSUM_VERIFIED` dan checksum sumber sama persis dengan checksum outcome. Ini adalah gate yang benar dan tidak boleh dilonggarkan hanya agar dropdown terisi.

## 3. Bentuk record staging (kontrak logis)
Setiap record CP:
- `record_key`: stabil, deterministik dari source doc + phase + subject + element + code + ordinal.
- `document_number`: `SK Dirjen Pendis 9941 Tahun 2025`.
- `source_sha256`: SHA-256 berkas yang diterima.
- `source_pdf_page`: halaman PDF (1-based), bukan nomor footer tercetak.
- `source_printed_page`: nomor halaman yang tercetak di footer jika terbaca.
- `subject_code`: kode mapel resmi dari registry mata pelajaran aplikasi.
- `phase`: A/B/C.
- `grade_start`, `grade_end`: A=1–2, B=3–4, C=5–6.
- `element`: Pemahaman Konsep / Keterampilan Proses / Komponen Bahasa / Keterampilan Bahasa sesuai mapel.
- `sub_element`: Tajwid, Al-Qur'an, Hadis, Akidah, Akhlak, Adab, Kisah Keteladanan, Ibadah, Muamalah, atau label sesuai dokumen.
- `code`: kode eksplisit dari dokumen jika tersedia; jika tidak tersedia, jangan membuat kode yang seolah-olah resmi. Kode teknis internal boleh deterministik tetapi harus dibedakan dari kode sumber.
- `description_source`: teks transkripsi persis.
- `transcription_status`: PENDING_VISUAL_QA / VISUAL_QA_PASSED.
- `source_status`: USER_SUPPLIED_COPY_CONTENT_REVIEWED_SIGNATURE_NOT_CRYPTOGRAPHICALLY_VERIFIED.
- `metadata`: jenis fase, rentang kelas, nomor halaman, versi transkripsi, dan catatan QA.

## 4. Aturan idempotensi importer
1. Importer hanya menerima artefak staging yang telah lulus validasi schema dan QA.
2. Identitas upsert: `(multi_curriculum_structure_id, code)`; untuk sumber tanpa kode CP eksplisit, gunakan internal key deterministik dan simpan `source_code=null` di metadata.
3. Sebelum upsert, hitung checksum konten teks UTF-8 yang telah dinormalisasi secara deterministik; jangan mengganti hash dokumen sumber.
4. Jika record existing dengan identity sama tetapi source hash/locator/deskripsi berbeda, hentikan batch dan buat conflict report. Jangan overwrite record aktif.
5. Dry-run wajib menghasilkan count create/update/unchanged/conflict/orphan; tidak menulis DB.
6. Eksekusi import harus transaction-scoped, lock identitas sumber/versi, dan aman diulang.
7. Seeder tidak boleh menghapus/menonaktifkan CP lama secara massal. Perubahan versi dilakukan dengan versioning/supersession.
8. Jangan mengubah source status menjadi ARTIFACT_CHECKSUM_VERIFIED tanpa bukti verifikasi berkas penerbit/TTE yang sah.

## 5. Gate sebelum aktivasi Guru
- Dokumen sumber dan hash diarsipkan secara immutable.
- Status keaslian/otoritas dokumen disetujui; hash file yang diimpor cocok dengan arsip.
- Semua CP MI fase/mapel/elemen telah ditranskripsi dan visual QA.
- Bahasa Arab dan harakat diperiksa oleh pembaca kompeten.
- Kelengkapan fase SKI B/C diverifikasi; fase A memang tidak ditetapkan dalam dokumen ini.
- Struktur mapel dan phase registry cocok dengan data madrasah.
- Import dry-run tanpa conflict/duplikasi.
- Acceptance test MySQL 8/InnoDB disposable lulus, termasuk idempotensi, provenance, version isolation, dan negative authorization.
- Baru setelah gate tersebut status sumber dapat dipertimbangkan naik sesuai kebijakan verifikasi yang sah. Jangan memalsukan TTE.

## 6. Kontrak tampilan
- Admin Madrasah: lihat status sumber, versi CP, mapel, fase, jumlah record, status QA; tidak dapat mengubah teks CP resmi atau membuat TP.
- Guru: hanya melihat CP yang aktif dan lolos gate; membuat TP/ATP melalui penugasan, terhubung ke parent CP.
- Siswa: tidak memiliki editor CP/TP; menerima konteks pembelajaran/asesmen yang dipublikasikan melalui materi atau snapshot ujian.
- KBC/Panca Cinta dan Pembelajaran Mendalam ditautkan sebagai konteks/pemetaan yang terpisah, tidak menimpa deskripsi CP sumber.

## 7. Batasan saat ini
Belum ada JSON staging final, importer, migration, source code change, database write, atau test. Dokumen ini adalah desain berdasarkan schema/service existing, bukan klaim implementasi.

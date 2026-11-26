# Audit Mendalam Import Soal DOCX — Web Guru
Tanggal: 26 November 2026
Baseline ZIP: CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip
SHA-256: 9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d

## AUDIT

### Temuan terkonfirmasi: NEEDS_REVIEW tetap masuk ke importer
Pada QuestionDocxImport::preview(), seluruh soal diberi included=true. DocxQuestionParser menandai soal READY atau NEEDS_REVIEW berdasarkan errors. UI menyatakan soal NEEDS_REVIEW tidak ikut disimpan.

Namun QuestionDocxImport::importDrafts() membentuk questionsForImport hanya dengan filter section/sequence dan included. Status READY dan errors tidak diperiksa. Validasi komponen juga menerima MATCHING, SHORT_ANSWER, dan UNSUPPORTED.

DocxQuestionDraftImporter::import() kembali hanya memfilter included. validateQuestion() hanya menerima MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, TRUE_FALSE_GROUP, ESSAY. Dengan demikian, satu soal unsupported yang masih included dapat melempar ValidationException dan membatalkan seluruh transaksi, termasuk soal READY yang lain.

Parser secara eksplisit menghasilkan NEEDS_REVIEW untuk unsupported blocks, tabel yang belum terklasifikasi, stem kosong, opsi tidak lengkap/duplikat, dan tipe tidak didukung. Ini merupakan cacat kontrak nyata antara parser, UI, Livewire, dan importer.

Kesimpulan: kandidat kuat penyebab seluruh batch gagal jika DOCX berisi soal NEEDS_REVIEW dengan tipe yang tidak didukung. Belum dapat dinyatakan sebagai akar masalah insiden pengguna tanpa mengaitkan dengan DOCX/log percobaan aktual.

### Temuan tambahan
- Mapping service dipanggil dalam transaksi untuk setiap soal. Mapel umum melewati mapping CP/TP, tetapi tetap memerlukan registration kurikulum aktif ditemukan sebelum cabang skip.
- Mapel yang wajib mapping memerlukan CP resmi dengan checksum terverifikasi, TP/ATP published/current, relasi ke CP, dan struktur framework mapping valid. Satu kegagalan membatalkan batch.
- Importer menyimpan Question, QuestionVersion, media, opsi, mapping, audit log, dan idempotency batch dalam satu transaksi; exception menyebabkan rollback.
- UI tidak menangkap exception tak terduga di sekitar pemanggilan importer. Exception DB/storage dapat menjadi Livewire 500 tanpa pesan terarah.
- Migration import batch sebelumnya dilaporkan sudah Ran; dump DB menunjukkan tabel/kolom utama tersedia, tetapi belum membuktikan DB aktif website sama dengan dump.

### Batas bukti
Belum ada DOCX contoh atau request/stack trace/log PHP-FPM/Nginx yang cocok dengan percobaan pengguna di folder kerja saat audit ini. Runtime lokal tidak memiliki DOM, ZIP, MBSTRING, PDO MySQL; PHPUnit dan browser/MySQL runtime belum dapat dijalankan.

## DESAIN
1. Hanya masukkan soal yang READY, errors kosong, tidak memiliki blocked_features, dan dicentang.
2. Terapkan validasi READY/errors lagi di service importer (defense in depth).
3. Samakan allow-list tipe antara Livewire dan importer.
4. Tangani exception tak terduga dengan correlation ID dan pesan aman untuk Guru; jangan bocorkan SQL/stack trace.
5. Pertahankan tenant, role, permission, assignment, transaksi, idempotensi, audit log, dan CP/TP/ATP tanpa bypass.
6. Tambah feature test MySQL disposable untuk campuran READY/NEEDS_REVIEW, unsupported type, duplicate batch, mapping, media/audit failure, mismatch tenant/assignment, dan rollback.

## IMPLEMENTASI
- Tidak ada source aplikasi yang diubah.
- Backup QuestionDocxImport.php tetap tersedia di backups/20261126-before-import-diagnostic/.
- ZIP baseline tidak diubah; tidak ada migration/database production change.
- ZIP binary 18.5 MB belum dapat diunggah dengan GitHub connector sesi ini: tool menyediakan file teks/blob/tree, bukan upload binary/release asset. Tidak mengklaim ZIP sudah diunggah.

## PENGUJIAN
- PHP lint 10 file terkait pada tahap sebelumnya: PASS.
- Audit statis UI/parser/importer/mapping: selesai.
- PHPUnit, Livewire browser, MySQL runtime/rollback: NOT TESTED.
- Belum ada klaim fitur berhasil.

## STATUS
Cacat READY/NEEDS_REVIEW terkonfirmasi pada source. Root cause spesifik percobaan pengguna belum terbukti. Perbaikan belum diterapkan. ZIP belum tersinkron ke GitHub karena keterbatasan transfer binary.

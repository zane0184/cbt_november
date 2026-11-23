# DRAFT DESAIN UI IMPORT SOAL MICROSOFT WORD (DOCX) — PORTAL GURU
Tanggal: 23 November 2026
Status: **DRAFT — menunggu kontrak final/approval Agent 2**
Owner UI: Agent Web Guru
Repository: `zane0184/cbt_november`

## 1. Tujuan dan batas

Mengganti entry point import soal XLSX yang telah dihentikan dengan alur Microsoft Word DOCX karena Guru umumnya menyusun soal di Word.

DOCX adalah format input utama. Ini bukan izin untuk membuat subsystem import kedua. Hasil extraction wajib menggunakan DTO bersama yang sama dengan authoring soal dan lifecycle `ImportBatch/ImportBatchRow` yang sudah ada.

Batas yang tidak berubah:
- Tidak membuat batch table, service lifecycle kedua, migration, atau Composer dependency DOCX tanpa approval.
- Tidak mengubah shared DTO, parser, ImportBatch/ImportBatchRow, snapshot, atau scoring milik Agent 2 tanpa kontrak/approval.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer tersimpan pada QuestionVersion, masuk immutable snapshot, dan diuji pada scoring.
- ESSAY tetap memerlukan penilaian manual.
- PDF/OCR dan format `.doc` tidak termasuk tahap ini.

## 2. Alur pengguna yang diusulkan

### Tahap A — pilih file
- Entry point Bank Soal: **Impor dari Microsoft Word (.docx)**.
- Hanya menerima format DOCX; tidak menampilkan XLSX atau template spreadsheet.
- Upload berlangsung ke private temporary staging, bukan public disk.
- Batas ukuran, jumlah file, timeout, dan resource limit ditampilkan dari konfigurasi server/kontrak; UI tidak menetapkan angka sendiri.
- Sebelum parsing, UI menampilkan status pemeriksaan file. File gagal validasi tidak masuk preview.

### Tahap B — ekstraksi dan preview
- Hasil parser ditampilkan sebagai daftar butir soal, bukan langsung disimpan.
- Setiap butir menunjukkan nomor sumber/locator (paragraf, tabel, relasi gambar) agar Guru dapat melacak asal konten di dokumen.
- Preview menampilkan stem, tipe soal, opsi, kunci jawaban bila format dokumen dapat mengenalinya, pembahasan, media, dan metadata akademik yang didukung DTO.
- Setiap warning/error diberi lokasi sumber dan penjelasan yang dapat ditindaklanjuti.
- UI tidak menganggap preview client sebagai data tepercaya; backend melakukan validasi ulang.

### Tahap C — koreksi
- Guru dapat mengedit setiap butir melalui field authoring yang sama dengan Bank Soal.
- Validasi tipe soal mengikuti aturan authoring, bukan aturan khusus import yang berbeda.
- Guru dapat menghapus butir yang tidak ingin diimpor.
- Data yang tidak dapat diekstrak tidak boleh diam-diam dibuang; UI harus menandai warning/error dan meminta keputusan Guru.
- Arabic/RTL/harakat dan simbol/persamaan harus dipertahankan. Jika renderer tidak dapat menampilkan dengan benar, tampilkan warning dan jangan mengubah teks sumber secara otomatis.

### Tahap D — konfirmasi
- Tampilkan ringkasan jumlah valid, perlu koreksi, dan ditolak.
- Tombol konfirmasi aktif hanya bila batch memenuhi aturan final backend.
- Backend mengulang validasi pada saat konfirmasi; hasil preview tidak dapat digunakan sebagai bypass.
- Konfirmasi menggunakan satu lifecycle `ImportBatch/ImportBatchRow` yang disetujui Agent 2.
- Penyimpanan soal membuat/menyentuh QuestionVersion DRAFT saja; tidak menerbitkan soal otomatis.
- Media embedded yang valid dipindahkan dari private staging dan ditautkan ke QuestionVersion DRAFT setelah konfirmasi berhasil.

### Tahap E — hasil
- Tampilkan ringkasan berhasil/gagal per butir dan batch.
- Setiap kegagalan menyediakan source locator, error/warning, dan tindakan koreksi yang diperbolehkan.
- Retry harus aman dan idempotent; tidak menggandakan Question/QuestionVersion/media.
- Tombol menuju Bank Soal hanya setelah backend mengembalikan status batch yang final.

## 3. Kontrak UI yang menunggu Agent 2

| Kontrak | Status | Catatan |
|---|---|---|
| Field/type/nullability/enum `QuestionDraftDTO` | PENDING | UI tidak membuat DTO tandingan |
| Source locator DOCX | PENDING | Paragraph, table, image relationship, stable locator |
| Parser profile/document pattern | PENDING | Struktur heading/nomor/opsi yang didukung |
| Warning/error schema | PENDING | Severity, code, locator, recovery guidance |
| ImportBatch/ImportBatchRow state transition | PENDING | Gunakan lifecycle existing |
| Idempotency/retry/duplicate contract | PENDING | Kunci dan semantics dari backend |
| Private media staging/attachment contract | PENDING | Orphan cleanup dan rollback |
| DOCX security limits | PENDING | ZIP/XML, expanded bytes, entry count, OLE, external relationships, macro/object, CPU/memory/time |
| Retention/cleanup | PENDING | TTL dan cleanup policy dari backend |
| Arabic/RTL/harakat/equation behavior | PENDING | Preserve or explicit warning, no silent normalization |
| Accepted answer | BLOCKED | SHORT_ANSWER tidak diaktifkan |

## 4. Keamanan dan integritas UI

- Semua operasi harus berada dalam auth + tenant + license + Guru role/permission + penugasan mapel yang aktif.
- Tenant ID, user ID, question owner, subject, academic scope, dan status DRAFT divalidasi server-side.
- Tidak menerima path file, storage key, tenant ID, atau owner ID dari client sebagai otoritas.
- Tidak ada akses lintas tenant pada preview, file staging, row, atau media.
- Double submit, retry, dan refresh tidak boleh menggandakan batch/soal/media.
- Jika konfirmasi gagal, tidak boleh ada soal parsial yang dianggap berhasil; rollback dan cleanup harus mengikuti kontrak backend.
- Log audit merekam actor, tenant, batch, ringkasan hasil, dan sumber file tanpa membocorkan konten sensitif.

## 5. Kriteria penerimaan Web Guru

1. Tidak ada route/button/template import soal XLSX.
2. Guru dengan assignment sah dapat mengunggah DOCX dan melihat preview tanpa menyimpan soal.
3. Guru dapat memperbaiki atau menghapus butir hasil extraction sebelum konfirmasi.
4. Preview dan konfirmasi melakukan validasi ulang server-side.
5. Hanya butir valid yang dikonfirmasi menjadi QuestionVersion DRAFT.
6. Media embedded dipasang ke versi DRAFT hanya setelah batch berhasil.
7. Gagal parse, file berbahaya, cross-tenant, revoked assignment, dan unauthorized role ditolak.
8. Double submit/retry idempotent dan tidak menghasilkan duplikasi.
9. Rollback membersihkan media staging/orphan sesuai policy.
10. Arab/RTL/harakat/simbol tidak diubah diam-diam; warning ditampilkan untuk konten yang tidak didukung.
11. SHORT_ANSWER tetap tidak dapat diaktifkan dari UI.

## 6. Pemisahan ownership

**Agent Web Guru:** route/UI upload, preview, koreksi, status, pesan error, integrasi form authoring, navigation, UI tests dan browser tests setelah kontrak disetujui.

**Agent 2:** DTO, parser adapter/shared import contract, ImportBatch/ImportBatchRow lifecycle, validation/persistence boundary, private staging/media contract, security hardening, idempotency, audit shared services, accepted answer/snapshot/scoring.

**Owner:** persetujuan perubahan produk dan setiap perubahan shared contract/dependency.

## 7. Keputusan implementasi saat ini

Belum ada UI DOCX atau parser yang ditambahkan. Dokumen ini hanya desain awal untuk mempercepat review kontrak. Implementasi ditahan sampai Agent 2 memberi keputusan eksplisit APPROVED/REJECTED/NEEDS DESIGN pada Issue #9 dan detail kontrak dapat diikat ke source aktual.

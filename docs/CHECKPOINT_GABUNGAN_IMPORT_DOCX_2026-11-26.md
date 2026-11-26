# Checkpoint Gabungan Import DOCX Web Guru — 26 November 2026

## Tujuan
Mengonsolidasikan source Import DOCX Web Guru dari PR #32, #33, dan #39 menjadi satu jalur source yang dapat diunduh sebagai ZIP repository. Ruang lingkup hanya Import DOCX Web Guru; tidak menjalankan PHPUnit, CI, atau runtime VPS sesuai instruksi pengguna.

## AUDIT
Repository: `zane0184/cbt_november`

Branch dasar integrasi: `integration/zip-vps-php83-20261126`
Commit dasar: `ee38818f89627443704bbda2469cf9b16d89625c`

Temuan PR:
1. PR #32 (`work/rombel-docx-completion-20261126`, head `841a1dd90dbe0277026fade1511a39b5dc9216b6`) sudah menjadi leluhur branch integrasi. Perbandingan commit menunjukkan head PR #32 berada di riwayat branch integrasi; tidak ditempel ulang untuk menghindari duplikasi.
2. PR #33 (`work/docx-import-fix-20261126`) berisi perbaikan preview/validasi komponen Livewire. Source komponen pada branch integrasi sudah memuat sejumlah perbaikan inti tersebut, termasuk regex hook yang benar, invalidasi preview lama, hash preview terkunci, pengikatan assignment, serta validasi ulang file di sisi server. Seluruh file PR #33 tidak disalin karena versinya lebih lama dan tidak mempertahankan seluruh alur multi-section yang ada pada source integrasi.
3. PR #39 (`work/cp-tp-save-button-feedback-20261126`) adalah satu perubahan Blade yang belum ada di branch integrasi: menampilkan error `curriculumMapping` dan indikator loading khusus aksi `importDrafts`.

## DESAIN KONSOLIDASI
- Pertahankan branch integrasi sebagai baseline source terbaru.
- Bawa perubahan Blade dari PR #39 ke dalam satu branch gabungan.
- Pertahankan perbaikan PR #32 yang sudah ada di riwayat integrasi.
- Pertahankan implementasi preview pada source integrasi; tidak mengganti dengan file PR #33 yang lebih lama.
- Tidak mengubah database produksi, migration produksi, konfigurasi VPS, atau dependency.

## IMPLEMENTASI
Branch konsolidasi:
`release/docx-import-combined-20261126`

Commit saat branch dibuat:
`eaad8613c8899ca8dba183794effda93ce1c97a7`

Commit tersebut adalah perubahan PR #39 di atas branch integrasi. Source lengkap repository tetap tersedia pada branch tersebut.

Perubahan efektif dari PR #39:
- Pesan validasi `curriculumMapping` ditampilkan kepada Guru.
- Tombol Simpan menampilkan status “Menyimpan soal ke Bank Soal…” selama aksi Livewire `importDrafts`.

## BATASAN DAN TEMUAN YANG BELUM DITUTUP
- Sesuai instruksi pengguna, PHPUnit, CI, browser E2E, dan runtime VPS tidak dijalankan.
- Belum ada bukti transaksi simpan berhasil di runtime VPS.
- Audit source sebelumnya menemukan bahwa pemilihan baris `READY` dalam jalur `importDrafts` masih membaca status dari state Livewire. Status tersebut perlu dihitung/ditetapkan ulang berdasarkan hasil parser server dan validasi field hasil edit, bukan dipercaya dari browser. Ini belum dinyatakan selesai pada checkpoint ini.
- Karena itu paket ini adalah konsolidasi source, bukan pernyataan bahwa Import DOCX sudah terverifikasi atau siap produksi.

## BACKUP / JEJAK SOURCE
- Branch integrasi sebelum konsolidasi tetap tersedia: `integration/zip-vps-php83-20261126`.
- Branch PR #33 tetap tersedia: `work/docx-import-fix-20261126`.
- Branch PR #39 tetap tersedia: `work/cp-tp-save-button-feedback-20261126`.
- Branch PR #32 tetap tersedia: `work/rombel-docx-completion-20261126`.
- Tidak ada source asli yang ditimpa pada branch sumber tersebut.

## PENGUJIAN
Tidak dijalankan atas instruksi pengguna. Tidak ada klaim PASS, keberhasilan transaksi database, atau keberhasilan runtime.

## Unduhan
GitHub menyediakan arsip ZIP otomatis untuk seluruh source pada branch:
`https://github.com/zane0184/cbt_november/archive/refs/heads/release/docx-import-combined-20261126.zip`

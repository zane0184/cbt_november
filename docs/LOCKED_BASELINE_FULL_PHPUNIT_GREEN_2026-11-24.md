# CATATAN PENTING — AREA FULL PHPUNIT SUDAH HIJAU / JANGAN DIULANG

<badge>LOCKED BASELINE — 24 NOVEMBER 2026</badge>

Dokumen ini adalah instruksi operasional wajib untuk semua agent/eksekutor berikutnya.

## Status yang dibekukan
- Lingkup: remediasi 42 kegagalan Full PHPUnit, termasuk akar masalah query pivot `role_user`, batas Rombel Guru, heartbeat rate limiter, canonical curriculum mapping, snapshot, answer-key leakage, scoring/reopen actor, auth token/device/fencing fixtures, dan test waktu.
- Source SHA yang telah terbukti hijau: `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
- CI: [Tenant Backend CI #45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635).
- Environment: PHP 8.3 + disposable MySQL 8.0/InnoDB.
- Hasil: **518 passed, 2,177 assertions, 0 failed**; focused login/tenant smoke 27 passed (90 assertions).
- Laporan akar masalah dan daftar file/backup: [REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md).

## Larangan pengulangan
1. **JANGAN mengulang triage 42 kegagalan dari nol.**
2. **JANGAN menjalankan full PHPUnit lagi hanya untuk memverifikasi hasil yang sudah hijau.** Hasil exact SHA dan CI di atas adalah baseline penerimaan yang sah.
3. **JANGAN mengubah, merapikan, refactor, mengganti assertion, factory, fixture, migration, schema, policy, atau source yang termasuk lingkup remediasi ini** hanya karena agent baru ingin memeriksa ulang atau memiliki preferensi implementasi.
4. **JANGAN mengembalikan bug lama**, khususnya query `role_user.tenant_id`, kemampuan Guru memilih Rombel `ALL`, atau limiter heartbeat global per-IP.
5. **JANGAN membuat patch duplikat** untuk helper academic mapping, historical assignment scope, published question fixtures, waktu ujian, auth token/device/fencing fixture, atau namespace fencing helper.
6. Jangan menyamakan status hijau dengan kesiapan produksi. Ini hanya baseline Full PHPUnit CI.

## Kapan area ini boleh dibuka kembali
Buka kembali hanya jika salah satu kondisi berikut terpenuhi:
- Owner secara eksplisit meminta perubahan pada lingkup ini.
- Perubahan source setelah SHA hijau menyentuh file, service, policy, route, schema, helper, atau dependency yang memengaruhi perilaku/test di lingkup ini.
- CI pada commit source baru yang benar-benar mengubah lingkup ini gagal dan log menunjukkan regresi yang relevan.
- Ditemukan bukti bug keamanan/data-integrity konkret yang terkait langsung dengan lingkup ini.

Jika dibuka kembali:
- Sebutkan file/commit baru yang memicu pembukaan dan bukti kegagalannya.
- Jalankan hanya focused test yang relevan terlebih dahulu.
- Jalankan full PHPUnit hanya bila perubahan menyentuh kontrak bersama atau ada indikasi regresi lintas modul.
- Buat backup byte-exact sebelum mengubah file existing.
- Jangan menghapus atau menimpa baseline hijau. Catat baseline baru sebagai versi baru dengan SHA/CI yang jelas.

## Protokol agent berikutnya
Baca catatan ini sebelum menjalankan test. Tandai area PHPUnit sebagai **CLOSED / GREEN BASELINE** dan lanjutkan hanya pekerjaan lain yang belum selesai. Jika perlu menyebut hasilnya, tautkan CI #45243430635 dan laporan taksonomi di atas; jangan mengulang pekerjaan tersebut.

Catatan ini membekukan ruang lingkup pekerjaan, bukan membuat branch GitHub secara teknis read-only. Proteksi branch/repository tetap mengikuti pengaturan GitHub owner.

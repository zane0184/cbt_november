# CHECKPOINT OPERASIONAL — CBT MADRASAH (27 NOVEMBER 2026)
Repo: `zane0184/cbt_november`
Branch: `fix/manual-question-optional-curriculum-20261127`
PR: #44 OPEN / DRAFT / NOT MERGED
Base: `integration/zip-vps-php83-20261126`
HEAD saat checkpoint: `a8551ae80285041f624493d2b9bf5c971a9de48f`

## Posisi benar
- GitHub source branch adalah sumber kebenaran.
- Compare base → head saat checkpoint: 109 ahead, 0 behind.
- Handover prompt/checkpoint tersedia di docs.
- Lima source blob inti cocok dengan SHA checkpoint lama.
- Path aktual QuestionBank: `backend-laravel/app/Livewire/Admin/QuestionBank.php`.
- Path aktual DOCX importer: `backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php`.
- Patch mapping tanggal nullable, kode soal unik, trim opsi whitespace, dan media extractor test terdapat pada source HEAD.
- Regression tests ditulis; belum dijalankan.
- Jangan mengulang patch tersebut kecuali ada bukti regresi.

## NEXT WAJIB
1. Selesaikan audit expiry status lintas transition/scoring/finalization/student result.
2. Audit dan patch minimum API authorization pasca-lock serta revalidate manual score terhadap locked max_score.
3. Audit finalisasi massal: current pointer, skip anomaly report, idempotency, partial failure.
4. Audit publish gate/current score pointer dan seluruh jalur akses siswa (Web/API/export/report/cache/notification).
5. Audit reopen/regrade carry-forward metadata ESSAY, audit duplication, report aggregation.
6. Tulis regression tests sesuai gap; jangan jalankan sebelum instruksi user.
7. Audit feature matrix Admin/Guru/Siswa/Backend dan integrasi Kurmer+KBC.
8. Audit workflow ZIP distribution, README, sanitasi secret/data, checksum dan ekstraksi.
9. Semua pekerjaan baru: backup source/test/doc sebelum perubahan, byte/blob SHA verification, patch kecil, laporan + checkpoint baru di GitHub.
10. Tidak merge, deploy, migration produksi, ataupun menyatakan siap distribusi tanpa persetujuan dan bukti.

## Batas pengujian
Dilarang menjalankan PHPUnit, CI, runtime, browser, integration, concurrency tests sampai instruksi baru.
Tidak ada DB/VPS/migration/production changes pada audit ini.

## Catatan handover
Jangan menggunakan angka ahead lama (72/74). Lakukan compare ulang pada awal sesi berikutnya. Jangan menganggap laporan tahap Device Lease/Fencing lama identik dengan source HEAD branch; audit ulang berdasarkan class/route aktual.

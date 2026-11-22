# Web Guru — Catatan Pemulihan Trigger CI P0

Tanggal: 23 November 2026  
Agent: Agent 3 — Web Guru  
Branch: `work/web-guru-phase1-audit-implementation-20261122`  
Workflow: `.github/workflows/web-guru-foundation-ci.yml`  
Workflow blob pada branch: `6a683c99d8ae0631095f447f0c791f034d900979`

## AUDIT

1. Workflow pada branch memiliki dua event: `workflow_dispatch` dan `push`.
2. Event `push` dibatasi ke branch Web Guru dan perubahan pada `backend-laravel/**` atau file workflow.
3. Pemeriksaan file yang sama pada branch default `main` menghasilkan HTTP 404: workflow ini belum tersedia pada default branch. Karena itu, tombol manual `workflow_dispatch` melalui GitHub Actions UI belum dapat diandalkan/tersedia untuk workflow branch ini selama file belum ada pada default branch.
4. Run sebelumnya untuk test SHA `616dc82f006e0c8327f5738857cd33d53ec9f071` berakhir `failure` dengan `steps=null`, `logs_url=null`; rerun job ID `137289470391` juga tidak menyediakan steps/log. Ini bukan bukti PHPUnit gagal atau lulus.
5. Workflow menyediakan pemeriksaan PHP lint, route registration, PHPUnit Web Guru, disposable MySQL 8, dan verifikasi InnoDB. Semua gate tersebut tetap belum terbukti berjalan.

## DESAIN / LANGKAH PEMULIHAN

- Tanpa mengubah `main`, jalur trigger yang tersedia adalah push commit baru ke branch Web Guru dengan perubahan pada `backend-laravel/**` atau `.github/workflows/web-guru-foundation-ci.yml`.
- Dokumen ini ditempatkan di `backend-laravel/docs/` sebagai catatan audit yang relevan dan sekaligus berada dalam path filter `push`; tidak mengubah runtime, test, policy, service, migration, database, atau workflow.
- Jika run dari push baru tetap gagal sebelum steps/log, pemulihan runner/Actions memerlukan pemeriksaan owner/repository settings. Penyebab tidak boleh ditebak.
- Jangan menambahkan test HTTP Livewire atau patch runtime sampai workflow dapat menjalankan langkahnya dan hasil red/green dapat diamati.

## IMPLEMENTASI

- Hanya file dokumentasi baru ini yang ditambahkan pada branch Agent 3.
- Tidak ada file existing yang ditimpa sehingga tidak diperlukan backup file pra-perubahan.
- Tidak ada perubahan pada `main`, branch Agent lain, database, VPS, atau deployment.

## PENGUJIAN

- Pemeriksaan statis konfigurasi workflow dan path filter: selesai.
- PHPUnit, PHP lint, route:list, MySQL 8/InnoDB: NOT RUN / NOT VERIFIED.
- Trigger push dari commit dokumentasi ini: menunggu hasil GitHub Actions; jangan menganggap berjalan sebelum metadata run/steps/log tersedia.

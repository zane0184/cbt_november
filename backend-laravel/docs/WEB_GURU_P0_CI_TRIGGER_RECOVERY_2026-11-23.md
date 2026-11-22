# Web Guru — Catatan Pemulihan Trigger CI P0

Tanggal: 23 November 2026  
Agent: Agent 3 — Web Guru  
Branch: `work/web-guru-phase1-audit-implementation-20261122`  
Workflow: `.github/workflows/web-guru-foundation-ci.yml`  
Workflow blob pada branch: `6a683c99d8ae0631095f447f0c791f034d900979`

## AUDIT

1. Workflow pada branch memiliki dua event: `workflow_dispatch` dan `push`.
2. Event `push` dibatasi ke branch Web Guru dan perubahan pada `backend-laravel/**` atau file workflow.
3. Pemeriksaan file yang sama pada branch default `main` menghasilkan HTTP 404: workflow ini belum tersedia pada default branch. Karena itu, manual `workflow_dispatch` melalui GitHub Actions UI belum dapat diandalkan/tersedia untuk workflow branch ini selama file belum ada pada default branch.
4. Run lama pada test SHA `616dc82f006e0c8327f5738857cd33d53ec9f071` gagal dengan steps/log tidak tersedia.
5. Push dokumentasi Agent 3 menghasilkan check suite `121916153101`, run `45050222534`, job `137293473966` pada exact commit `e33517929421810dc8519aedb02fd18b571a43a4`. Job selesai dengan `failure` dalam sekitar empat detik; `steps=[]`, log mengembalikan `BlobNotFound`, dan output check tidak menyediakan detail annotation yang dapat dibaca.
6. Kegagalan ini terjadi sebelum bukti langkah workflow tersedia. Ini bukan bukti PHPUnit, PHP lint, route:list, atau MySQL gagal; semua tetap NOT RUN / NOT VERIFIED. Penyebab runner tidak disimpulkan.

## DESAIN / LANGKAH PEMULIHAN

- Tanpa mengubah `main`, push pada path filter branch adalah jalur trigger yang terbukti membuat check suite.
- Workflow tidak menyediakan detail kegagalan melalui log/steps pada job yang tercatat. Pemulihan berikutnya memerlukan pemeriksaan owner/repository Actions settings dan runner infrastructure.
- Jangan menambahkan HTTP Livewire regression tests atau patch runtime sampai job dapat menjalankan langkahnya dan hasil red/green dapat diamati.
- Setelah CI dapat dieksekusi, lanjutkan HTTP POST revocation regression pada `TeacherPortalFoundationTest.php`, lalu koordinasi dependency Agent 2.

## IMPLEMENTASI

- Satu file dokumentasi baru ditambahkan pada `backend-laravel/docs/` sebagai catatan audit dan trigger push.
- Tidak ada file existing runtime/test/policy/service/workflow yang diubah.
- Tidak ada backup source diperlukan karena hanya file baru.
- Tidak ada perubahan pada `main`, branch Agent lain, migration, database, VPS, atau deployment.

## PENGUJIAN

- Audit statis workflow dan path filter: selesai.
- Push trigger: check suite terbuat, tetapi job failure sebelum steps.
- PHPUnit, PHP lint, route:list, MySQL 8/InnoDB: NOT RUN / NOT VERIFIED.
- Browser E2E dan UAT: NOT RUN.

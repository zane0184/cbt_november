# Checkpoint Agent 3 — P0 HTTP Livewire / Web-only Next Gate

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
HEAD sebelum checkpoint update: `63dc492864385458dc8cffcb37afeb53445255c8`  
Test source commit: `4dc407d091fc6cf786260052cfd86d836edc5908`  
Test blob: `0b273618677c791d82645fa373be57aa2d961643`

Status: **P0 OPEN — TENANT/SECURITY TEST INCOMPLETE**

## 1. AUDIT

### 1.1 Agent 3 source

- QuestionBank blob: `219efc2db8f492452ad95da35ae22eb4849d1e66`.
- ExamManagement blob: `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc`.
- TeacherPortalAccess blob: `8c60fb6db17512d6bbf8845027f69841172e2f60`.
- TeacherAccess blob: `766289c5163b6cf367d511c0bf35207ccbc97684`.
- IdentifyTenant blob: `376a4b2771c3d9a6b128ad847c150d47ac2977db`.
- Test source has 22 test methods. Six post-mount revocation/context regressions remain.
- Current test source is commit `4dc407d...`, blob `0b273618...`.

### 1.2 Cross-agent refs

| Owner | Branch | HEAD aktual | Catatan |
|---|---|---|---|
| Agent 1 | `work/stage4b-room-session-20261122` | `51571ca54ec922466e6230b4e8137a85336bc024` | Commit terbaru hanya manifest dokumentasi; source Stage 4B tetap baseline `d253279...`. |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `462cadc96a95d091bd89819dbc106b4fbd67e1cd` | Commit terbaru checkpoint dokumentasi; source runtime tetap `74bf7178...`. |
| Agent 3 | `work/web-guru-foundation-20261122` | `cf4da2c371d4f942bfb9cc8b4eb613431988b531` sebelum laporan baru | Test hardening + backup/manifest; tidak mengubah source runtime Agent lain. |
| Dokumentasi Agent 4 | `work/agent4-web-siswa-audit-20261122` | `16c7d324ef9d7103bb21f0c5528c3c888ba19568` | Dokumentasi saja, bukan implementasi. |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | Owner belum dikonfirmasi; tidak diubah. |

ExamPolicy Agent 2 blob `c05d96ef...` masih menggunakan hasRole non-exact pada viewAny/view/preview/create/update/delete/manageQuestions/manageParticipants/generateToken/lock/close/publishResults; startAttempt juga belum exact-one SISWA. Jalur KAMAD masih ada. QuestionPolicy Agent 2 blob `af2b3be5...` sudah memiliki exact-GURU helper, tetapi branch Agent 3 masih memiliki QuestionPolicy lama blob `81e3cb6c...`; branch divergence harus direkonsiliasi pada integrasi. Agent 3 tidak mengubah policy Agent 2.

Issue #8 OPEN (7 komentar), #9 OPEN (2 komentar), #10 OPEN (11 komentar sebelum komentar keputusan topologi terbaru). Tidak ada approval lintas-agent yang dapat diverifikasi. Koordinator menambahkan keputusan topologi per-madrasah pada Issue #10: satu instalasi, satu database akademik, storage/config/credential/backup sendiri; tanpa shared academic database antarmadrasah. tenant_id tetap defense-in-depth dalam instalasi.

## 2. DESAIN

Test hardening mencakup:
1. GET HTTP `/admin/questions` dan `/admin/exams` untuk menguji mount route dengan auth/tenant/license.
2. Livewire update dengan TenantContext dipaksa ke Tenant B; update harus membangun ulang context dari user Tenant A.
3. Tenant ID User di database berubah setelah mount; request Livewire berikutnya harus ditolak.
4. Foreign tenant fixtures: Question, QuestionVersion, QuestionOption/correct option, Assessment dan Exam; semua dipastikan tersimpan sebelum request.
5. Anti-enumeration melalui ModelNotFoundException model yang tepat; tidak ada stem/kunci/Exam metadata pada komponen awal.
6. Snapshot raw database questions, question_versions, question_options, assessments dan exams sebelum/sesudah post-mount denial.
7. ADMIN_MADRASAH, SISWA dan KAMAD diberi permission yang sama dengan Guru tetapi tetap ditolak; mixed-role Guru juga ditolak.

## 3. IMPLEMENTASI

Satu file test Agent 3 diubah:
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Source commit `4dc407d091fc6cf786260052cfd86d836edc5908`
- Blob `0b273618677c791d82645fa373be57aa2d961643`
- 22 test methods (21 sebelumnya).

Laporan rinci:
- `docs/AGENT3_P0_HTTP_LIVEWIRE_TEST_HARDENING_2026-11-22.md`
- Commit laporan: `3a4f0b1cbe0ce289fcff3187600a34ee872b201f`

Backup manifest:
- Path: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`
- Blob: `93a9f2b3edd5053eb53444354f01236c4314b722`
- 32/32 backup paths di-fetch ulang dan seluruh blob SHA cocok.
- Backup test dan checkpoint sebelum perubahan dicatat. Tidak ada backup lama yang dihapus/ditimpa.

Tidak ada perubahan source runtime, middleware, policy, controller, service, migration/schema, Composer, workflow, database, atau file Agent 1/2/4. Tidak ada merge/PR/rebase/cherry-pick/deployment.

## 4. PENGUJIAN

- Branch/source/blob/issue audit: PASS sebagai pemeriksaan read-only.
- Backup manifest: PASS 32/32 blob match.
- Test count: 22 method terverifikasi statis.
- PHP lint exact SHA: NOT RUN / NOT VERIFIED.
- PHPUnit exact SHA terbaru: NOT RUN / NOT VERIFIED.
- MySQL 8/InnoDB disposable dan cleanup exact SHA: NOT RUN / NOT VERIFIED.
- Browser QA: BLOCKED; prasyarat CI dan backend exact-role belum lulus, environment browser/akun uji belum tersedia/terverifikasi.
- Database produksi: NOT TOUCHED.

Run #41 `45009039557` pada SHA lama `88b5c135...`: job failure, steps kosong, log BlobNotFound, artifacts kosong, combined status kosong. Runner ID/name, timestamps dan attempt tidak tersedia melalui konektor. PHPUnit execution UNKNOWN. Ini bukan bukti PHPUnit PASS/FAIL pada test source baru. Combined status commit `4dc407d...` kosong. Tidak ada rerun atau workflow dispatch karena izin rerun eksplisit koordinator belum diberikan dan konektor tidak menyediakan dispatch action.

## 5. NEXT GATE

**P0 OPEN — TENANT/SECURITY TEST INCOMPLETE.**

1. Koordinator/repository owner memberi izin eksplisit untuk workflow exact SHA terbaru atau memeriksa metadata runner/Actions melalui UI/API pemilik.
2. Buktikan PHP lint, route registration, PHPUnit PASS, disposable MySQL 8/InnoDB dan cleanup pada exact SHA.
3. Agent 2 menyelesaikan exact-role ExamPolicy/API/service entrypoints, termasuk KAMAD dan mixed-role regression.
4. Agent 2 mengonfirmasi topologi satu instalasi per madrasah pada tenant resolver/installation identity; belum ada patch runtime.
5. Agent 1/2/4 memberi keputusan lintas-owner untuk canonical session, Attempt close/resume/recovery, room monitoring dan lock order.
6. Browser QA Web Guru baru setelah CI/backend gate; catat URL, browser, viewport, akun/role dan bukti tiap skenario.
7. Import XLSX/DOCX tetap desain saja sampai approval eksplisit Agent 2.

Tidak boleh menutup P0 atau menyatakan Web Guru/Web Siswa siap operasional/produksi.

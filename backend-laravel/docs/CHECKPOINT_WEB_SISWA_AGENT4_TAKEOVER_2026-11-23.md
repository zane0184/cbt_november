# Checkpoint Agent 4 — Web Siswa takeover & kontrak auth-session (23 November 2026)

## Posisi repository terverifikasi
- Repository: `zane0184/cbt_november`.
- Branch kerja: `work/agent4-web-siswa-emergency-20261122`.
- Base PR #13: `work/stage4d-account-lifecycle-20261122`.
- Perbandingan branch saat checkpoint: 95 commits ahead, 0 behind.
- PR #13 tetap Draft, open, dan unmerged.
- Source runner saat audit: `backend-laravel/resources/views/student/exams/show.blade.php`, blob `2d1f868bdc877188d2bcd4925c1be3dcec153bc9`.
- Branch metadata/PR snapshot yang dibaca sebelumnya melaporkan SHA lama `7fd1d533...`; itu tidak cocok dengan source ref terkini. Gunakan fetch branch dan file blob saat ini sebagai dasar, bukan SHA lama dari snapshot PR.

## AUDIT
- Master Spec Web Siswa telah dibaca dari `work/agent4-web-siswa-audit-20261122`.
- Runner sudah memiliki IndexedDB v2, draft/queue atomik, request ID/payload/base_revision yang immutable, conflict persistence, restore draft conflict setelah reload, submit barrier, submit marker UNKNOWN dan status recovery, mobile-first layout, serta timer berbasis server.
- Source runner saat audit belum menyimpan `fence_generation` atau `lease_expires_at` ke `attempt_context`; belum mengirim `X-CBT-Fence-Generation`; belum memiliki heartbeat Web; save answer hanya memakai `revision_number` dan belum memverifikasi receipt; media menggunakan elemen native image/audio/video yang tidak bisa langsung menambahkan custom header.
- Perbandingan source Agent 4 dengan base PR #13 pada file berikut menunjukkan blob identik (belum memuat contract Agent 2 yang ditulis pada Issue #10 komentar #7083116647):
  - `ExamAttemptController.php`: `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae`
  - `ExamAnswerController.php`: `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c`
  - `MonitoringController.php`: `90fcfea595c10918e2c3866363356f688452a0a4`
  - `MediaController.php`: `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62`
  - `routes/api.php`: `2e43c37dd669a26446a44b818c1d2c8420b15aca`
  - Test `ExamSessionHardeningTest.php`: `95f2708b5aaf4948571d21b0a3bb3ae60edb2215`
  - Test `ExamAnswerTest.php`: `c6ac21f3db9591e37ffbdf21228f4736d7bd962a`
- Source base masih memakai `X-Device-Binding-ID`/device fingerprint pada start, execution, answer, heartbeat dan submit. API route heartbeat lama tersedia tetapi belum merupakan auth-session fencing lease.
- `StudentExamController` pada base masih memakai `hasRole('SISWA') && !hasRole('ADMIN_MADRASAH')`, bukan exact-one SISWA; ini P0 backend milik Agent 2, bukan patch UI Agent 4.
- Halaman daftar ujian dan hasil telah memiliki struktur responsif dasar; belum ada browser/device UAT.

## DESAIN / koordinasi
- Review kontrak Agent 2 sudah diposting ke Issue #10 sebagai komentar #7084085527: status **NEEDS DESIGN**, menerima arah desain tetapi belum menyetujui integrasi source sampai branch/commit backend, schema endpoint, heartbeat, receipt-request binding, media auth dan recovery queue dipastikan.
- Target client setelah kontrak backend tersedia:
  1. Persist generation + lease expiry per Attempt pada `attempt_context`.
  2. Setiap request Attempt-scoped memakai generation terbaru yang disahkan server; update generation ke IndexedDB sebelum request selanjutnya.
  3. Heartbeat Web perlu cadence/lifecycle yang disepakati; setelah tab kembali aktif atau jaringan pulih, revalidate dahulu.
  4. Queue lama tetap immutable dan ditahan bila generation berubah; jangan auto-replay/rebase saat status commit belum pasti.
  5. Receipt perlu diikat pada request ID (minta `client_request_id` di receipt atau identifier ekuivalen), lalu validasi Attempt, snapshot, revision, payload hash dan committed_at sebelum disimpan di `server_receipts`.
  6. `X-Device-Binding-ID` tetap sebagai metadata perangkat tambahan, bukan pengganti auth-session/fencing.
  7. Media auth memerlukan strategi yang kompatibel dengan elemen media browser; jangan mengarang bearer token query.
- Belum ada implementasi client fencing/receipt pada checkpoint ini karena source base PR belum memuat API target dan perubahan tanpa kontrak runtime dapat memutus seluruh runner.

## IMPLEMENTASI
- Tidak ada source runtime Web Siswa yang diubah dalam takeover audit ini.
- Tidak ada backup baru karena tidak ada file yang dimodifikasi.
- Tidak ada backend Agent 2/3, route, migration, database, Web Guru/Admin, konfigurasi VPS atau ZIP baseline yang diubah.
- Review kontrak Agent 2 adalah satu-satunya aksi GitHub koordinasi pada tahap ini (Issue #10 comment #7084085527).

## PENGUJIAN
Dilakukan:
- Fetch source aktual dari branch Agent 4 dan base PR #13.
- Compare branch terhadap base: 95 ahead, 0 behind.
- SHA comparison pada delapan file backend/test menunjukkan semua identik di kedua branch.
- Pemeriksaan statis terhadap runner mengonfirmasi IndexedDB v2, persist draft/queue, conflict, submit barrier, dan tidak adanya header fencing/heartbeat/receipt verification.
- Source/branch comparison adalah bukti statis, bukan browser runtime.

Belum dilakukan / BLOCKED:
- Browser E2E dan IndexedDB runtime (v1→v2, reload/crash/quota/offline/reconnect/multi-tab).
- PHP lint/PHPUnit pada SHA base aktual.
- Integrasi API dengan backend auth-session/fencing/receipt.
- MySQL 8/InnoDB race/concurrency, multi-device dan expiry/submit race.
- Media endpoint fencing, exact-one SISWA pada seluruh entrypoint, UAT madrasah.

## Keputusan dan pekerjaan selanjutnya
- PR #13 tetap Draft/unmerged; jangan merge/deploy atau gunakan untuk ujian resmi sebelum P0 backend dan browser E2E ditutup.
- Menunggu konfirmasi Agent 2 untuk endpoint dan schema aktual; setelah itu patch client-only dibuat dengan backup byte-exact per file dan uji kontrak.
- Jika tenggat sekolah besok tetap berlaku, batas aman saat ini hanya simulasi non-nilai menggunakan environment uji yang tidak memuat data siswa/ujian resmi. Ini bukan persetujuan operasional.

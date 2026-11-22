# Investigasi CI Exact-SHA — 88b5c135

Tanggal pemeriksaan: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
Baseline branch sebelum dokumen: `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`  
Source test commit: `88b5c1354971bfb7b03d715014b40b9026f1fbed`  
Blob test: `747989ed491dc1ead67b8a990434837df47c8af1`

## AUDIT

### Run exact SHA

Run #41 `45009039557` pada commit `88b5c135...` teridentifikasi sebagai:
- Workflow: Web Guru Foundation — Isolated MySQL Verification
- Event: `push`
- Job: `teacher-portal-tests`, ID `137167012186`
- Job status: `completed`
- Job conclusion: `failure`
- Job steps: endpoint mengembalikan array kosong
- `logs_url`: null; download log mengembalikan `BlobNotFound`
- Artifacts: tidak ada
- Combined commit status: kosong

Status bukti yang wajib dipakai: **WORKFLOW FAILURE — JOB STEPS/LOG UNAVAILABLE — PHPUNIT EXECUTION UNKNOWN**.

Keterbatasan metadata: endpoint job yang tersedia tidak mengembalikan runner ID/name, timestamps, last step, cancellation/failure message, atau run attempt. Tidak ada bukti yang cukup untuk menyimpulkan apakah job gagal sebelum `Set up job`, ketika service MySQL dimulai, saat checkout/dependency setup, atau ketika PHPUnit berjalan. Jangan mengarang runner unavailable maupun hasil PHPUnit.

### Matriks CI

| Exact SHA | Run ID | Job ID | Event | Runner | Step terakhir | PHPUnit | Hasil | Log/artifact |
|---|---:|---:|---|---|---|---|---|---|
| `88b5c135...` | `45009039557` (#41) | `137167012186` | push | Tidak tersedia dari metadata yang dapat diakses | Tidak diketahui; steps kosong | UNKNOWN | workflow/job failure | log BlobNotFound; artifact nihil |
| `834a59f...` | `45003191589` (#39) | `137148981065` | push (sesuai catatan run) | Tidak dicatat dalam bukti yang tersedia | PHPUnit | Berjalan: 18 passed, 2 failed, 86 assertions | FAILURE | review Agent 2 tersedia; bukan exact SHA 88b5 |
| `a338679...` | `44995889398` | Tidak dicatat di checkpoint ini | run lama | Tidak dicatat | PHPUnit dan verifikasi DB | Berjalan: 16 passed, 69 assertions | SUCCESS | hasil lama; bukan bukti untuk 88b5 |

### Workflow YAML pada exact SHA

Blob workflow: `8c0c1f4de31dedf1584d0ee5aeecfff735596eb6`.

Konfigurasi yang terverifikasi:
- `runs-on: ubuntu-24.04`
- Timeout job 45 menit
- MySQL service `mysql:8.0`, health check setiap 5 detik, timeout 5 detik, 20 retries
- Concurrency group per ref, `cancel-in-progress: false`
- Permission workflow hanya `contents: read`
- Checkout eksplisit `ref: ${{ github.sha }}`, `persist-credentials: false`
- PHP 8.3 dan Composer v2
- Provisioning database disposable bernama unik per run/attempt, user terpisah, preflight identity dan schema kosong
- PHP lint dan route registration sebelum PHPUnit
- PHPUnit filter `TeacherPortalFoundationTest|QuestionImportTemplateDownloadTest`
- Verifikasi semua base table menggunakan InnoDB
- Cleanup database/user dengan `if: always()`

YAML menunjukkan konfigurasi runner/service/checkout yang dimaksud, tetapi tidak membuktikan bahwa runner benar-benar dialokasikan atau step mana yang gagal pada Run #41. Tidak ada perubahan workflow dilakukan.

## DESAIN / KEPUTUSAN

Jangan mengubah workflow atau test berdasarkan dugaan. Bukti yang diperlukan untuk menutup gate:
1. Run/attempt dengan checkout SHA persis `88b5c135...` dan metadata job lengkap.
2. Step log atau bukti yang menunjukkan PHPUnit benar-benar berjalan.
3. PHPUnit seluruhnya PASS, dilanjutkan verifikasi InnoDB dan cleanup.
4. Jika job kembali gagal tanpa steps, pemilik repository perlu memeriksa runner/Actions service dan memberikan bukti dari UI/API GitHub yang tidak tersedia melalui konektor ini.

## IMPLEMENTASI

Tidak ada source, workflow, test, migration, database, atau konfigurasi yang diubah dalam investigasi ini. Dokumen audit baru.

## PENGUJIAN

Pemeriksaan dilakukan secara read-only terhadap workflow exact SHA, test blob, run/job/steps/log/artifact dan combined status. Tidak menjalankan PHPUnit lokal atau melalui terminal. Tidak menyentuh database produksi.

**Status:** P0 tetap terbuka. PHPUnit pada Run #41 belum terbukti berjalan.

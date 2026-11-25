# Rencana Audit Independen P0 Packaging ZIP — Agent 2

Tanggal persiapan: 2026-11-25  
Peran: Agent 2 — Independent QA / Security / Release Auditor  
Mode: READ-ONLY terhadap seluruh source, workflow, dan branch Agent 1.

## 1. Batas kewenangan

- Tidak mengubah source, test, workflow, konfigurasi, backup, atau artifact pada branch Agent 1.
- Tidak melakukan merge, migration, deployment, atau operasi database produksi.
- Perubahan yang diizinkan hanya laporan/checkpoint pada branch Agent 2.
- Semua status PASS harus didukung bukti langsung yang dapat diulang.
- PHPUnit/CI bukan pengganti Browser E2E dari ZIP hasil ekstraksi.

## 2. Identitas target audit

Branch Agent 1: `work/limited-zip-p0-20261125`  
HEAD terakhir yang diketahui dari pemeriksaan sebelumnya: `7a89acf5e1ea9227f12b48a1f13e5d69ff0dcd05`  
Workflow: `.github/workflows/build-web-deployment.yml`  
Blob workflow terakhir yang diperiksa: `c2ea4c907001ce7f12aa347f354f8099dffa07e3`  
Branch Agent 2: `audit/independent-qa-admin-guru-siswa-20261125`

Identitas di atas adalah baseline pemeriksaan, bukan jaminan bahwa HEAD saat audit artifact nanti tetap sama. Sebelum setiap audit artifact, fetch ulang branch HEAD dan cocokkan dengan metadata artifact, run, dan manifest ZIP.

## 3. Gerbang A — Identitas dan asal artifact

| Pemeriksaan | Bukti wajib | Status awal |
|---|---|---|
| Branch sumber | Metadata workflow run dan checkout ref | NOT VERIFIED |
| Exact source commit | `head_sha`, checkout SHA, manifest package | NOT VERIFIED |
| Workflow/run | workflow path, run ID, event, attempt, conclusion | NOT VERIFIED |
| Artifact | artifact ID, nama, expired flag, ukuran, created_at | NOT VERIFIED |
| ZIP | nama, ukuran byte, SHA-256 hasil hitung independen | NOT VERIFIED |
| Kesesuaian HEAD | commit artifact sama dengan HEAD yang dilaporkan Agent 1; perubahan setelahnya dianalisis | NOT VERIFIED |

Aturan: artifact yang dibangun dari SHA lain tidak boleh disebut artifact exact HEAD. Jika run dipicu dari branch atau event yang salah, catat sebagai kegagalan provenance/trigger meskipun ZIP dapat diunduh.

## 4. Gerbang B — Audit keamanan isi ZIP

Audit dilakukan pada daftar seluruh entry ZIP, termasuk file tersembunyi, nested path, symlink bila ada, variasi kapitalisasi, dan nama yang di-encode. Jangan hanya membaca hasil grep/guard workflow.

### B1. Backup dan snapshot
- `backups/`, `backup/`, `backup_before_patch/`, nested backup directory.
- `*.before`, `*.bak`, `*.backup`, `*.orig`, `*.old`, snapshot/manifest backup internal.
- File backup database atau source yang tidak dibutuhkan distribusi.

### B2. Environment dan secret
- Semua `.env*`, kecuali `.env.example` yang secara eksplisit diizinkan.
- Credential, password, API key, bearer token, session secret, signing secret.
- Private key/certificate: `.pem`, `.key`, `.p12`, `.pfx`, `.jks`, `.keystore`, dan format sejenis.
- Secret yang tertanam pada file konfigurasi, script, README, atau manifest.

### B3. Database dan data runtime
- `.sql`, `.dump`, `.sqlite`, `.sqlite3`, `.db`, `.ibd`, database runtime, export/import berisi data nyata.
- Arsip terkompresi yang berisi dump/database; periksa isi nested archive bila aman dilakukan.
- Fixture yang memuat data pribadi/tenant nyata.

### B4. Runtime, cache, log, dan temporary
- Log, cache, session, compiled view, temporary, swap, crash/core dump.
- `storage/logs`, `storage/framework/cache`, `storage/framework/sessions`, file runtime privat.
- File editor/OS tersembunyi dan artefak build yang tidak diperlukan.

### B5. Debug/test/internal
- Test suite, test fixture, demo/test seeder, smoke/debug script, profiler, coverage, PHPUnit cache/result, dump request/response.
- File internal audit, catatan kerja, source eksperimen yang tidak diperlukan untuk instalasi.
- Periksa juga pola nama yang tidak tercakup denylist; ketiadaan pola yang dikenal bukan bukti bahwa arsip aman.

### B6. Penilaian
Untuk setiap temuan catat: path entry ZIP, jenis data, bukti (nama/ukuran/hash dan cuplikan aman bila perlu), risiko, syarat perbaikan, status verifikasi ulang. Jangan menyalin nilai secret ke laporan; catat keberadaan dan lokasi saja.

Status gerbang B: NOT VERIFIED sampai bytes ZIP nyata diperiksa.

## 5. Gerbang C — Ekstraksi bersih dan integritas

1. Unduh artifact dari run yang telah diverifikasi.
2. Hitung SHA-256 ZIP secara independen; cocokkan dengan checksum yang diterbitkan workflow, bila ada.
3. Buat direktori tujuan baru yang benar-benar kosong; jangan menimpa direktori lama.
4. Ekstrak ZIP dan simpan daftar entry sebelum/sesudah ekstraksi.
5. Periksa path traversal, absolute path, duplicate path, case-collision, symlink, dan file dengan path abnormal.
6. Verifikasi manifest checksum dari direktori hasil ekstraksi dengan `sha256sum -c SHA256SUMS.txt` (atau verifier ekuivalen yang tervalidasi).
7. Pastikan manifest mencakup semua file yang seharusnya, tidak meng-hash dirinya sendiri, tidak memiliki path di luar root, dan tidak melewatkan file tersembunyi.
8. Cocokkan jumlah file dan path arsip terhadap hasil ekstraksi; catat file hilang/ekstra/berubah.
9. Baca README/panduan dari direktori hasil ekstraksi, bukan staging repository.
10. Periksa struktur Laravel dan konfigurasi:
   - `artisan`, `composer.json`, `composer.lock`, `vendor/autoload.php`.
   - `.env.example`, migrations, routes, config, resources, public, bootstrap.
   - Storage/bootstrap cache placeholders dan permission writable sesuai kebutuhan.
   - Tidak ada `.env` nyata, backup, dump, secret, test/debug internal.
11. Evaluasi instruksi Windows Explorer, penempatan `.env` privat oleh pemilik, prasyarat PHP/extensions/database, setup, permission, dan disclaimer non-production.

Gerbang C hanya PASS jika ekstraksi bersih, integritas penuh, struktur lengkap, dan dokumentasi yang diekstrak terbukti benar.

## 6. Gerbang D — Browser E2E dengan backend nyata

Dilakukan hanya setelah gerbang A-C lulus. Gunakan lingkungan pengujian terisolasi, database disposable, dan konfigurasi non-produksi. Dilarang memakai database produksi.

| ID | Alur | Bukti minimum |
|---|---|---|
| D-01 | Start backend hasil ekstraksi | command/runtime, versi PHP, koneksi DB test, log startup |
| D-02 | Installer dan konfigurasi awal | browser trace/screenshot, respons HTTP, hasil setup |
| D-03 | Login Admin Madrasah | sesi, role/tenant context, akses yang diizinkan |
| D-04 | Setup akademik minimum | data master/rombel/mapel/tahun ajaran yang tersimpan |
| D-05 | Import Siswa | file uji sintetis, hasil import, jumlah sukses/gagal, DB assertion |
| D-06 | Login Guru | role dan tenant scope |
| D-07 | Buat dan publikasikan ujian | data ujian, snapshot, audit event |
| D-08 | Token dan jadwal | token terlihat sesuai aturan; mulai hanya pada jadwal |
| D-09 | Login Siswa | identitas peserta dan tenant scope |
| D-10 | Pelaksanaan ujian | halaman ujian, timer server, soal yang benar |
| D-11 | Simpan jawaban | request/response, persistensi DB, reload/recovery |
| D-12 | Submit dan penilaian | status attempt, score, idempotensi submit |
| D-13 | Isolasi tenant | akun tenant A tidak dapat membaca/mengubah data tenant B |
| D-14 | Pembatasan role | Admin/Guru/Siswa tidak dapat menjalankan aksi di luar permission |
| D-15 | Regresi kehilangan jawaban | refresh/retry/double submit dan pemeriksaan jawaban/score |

Setiap alur harus menyimpan bukti yang dapat ditinjau: langkah, timestamp, URL, hasil HTTP, screenshot/trace yang aman, query/assertion database test, dan log relevan yang sudah disanitasi. PHPUnit tetap bukti unit/integrasi sesuai cakupannya, bukan bukti Browser E2E.

## 7. Format temuan

Setiap temuan wajib memiliki:
- ID dan severity (Critical/High/Medium/Low).
- Gerbang dan status: PASS / FAIL / BLOCKED / NOT VERIFIED / NOT RUN.
- Nama file atau path persis dalam ZIP / lokasi browser.
- Bukti langsung (run, artifact, log, hash, screenshot, trace, atau assertion).
- Risiko/dampak.
- Syarat perbaikan yang dapat diuji.
- Status verifikasi ulang dan artifact/commit yang digunakan.

## 8. Gerbang rilis

ZIP tidak boleh dinyatakan **SIAP UJI OPERASIONAL TERBATAS** sebelum seluruh syarat berikut PASS:
- Seluruh P0 yang relevan PASS; P0-01 Installer/System Tenant PASS/CLOSED dan P0-02A.1 ATP Provenance Regression PASS/CLOSED tetap dicatat terpisah.
- Packaging workflow memicu branch yang benar dan menghasilkan artifact dari exact HEAD.
- Tidak ada backup, secret, credential, token, private key/certificate, dump/database, atau file internal terlarang di ZIP.
- Clean extraction, struktur, dan full checksum PASS.
- README/panduan hasil ekstraksi lengkap dan benar.
- Browser E2E backend Laravel/MySQL nyata PASS.
- Tidak ada risiko kritis kehilangan jawaban atau pelanggaran isolasi tenant.

CP resmi tetap **BLOCKED** secara terpisah sampai dokumen resmi asli, provenance, checksum dari bytes yang sama, dan cakupan dokumen terverifikasi.

## 9. Snapshot audit awal (25 Nov 2026)

- HEAD Agent 1 terakhir yang diketahui: `7a89acf5e1ea9227f12b48a1f13e5d69ff0dcd05`.
- Workflow `.github/workflows/build-web-deployment.yml` pada HEAD tersebut memiliki blob `c2ea4c907001ce7f12aa347f354f8099dffa07e3`.
- Pemeriksaan workflow menunjukkan branch `work/limited-zip-p0-20261125` belum tercantum pada `on.push.branches`.
- Rsync mengecualikan `backup_before_patch/`, tetapi belum mengecualikan seluruh `backups/`.
- Belum tampak guard rekursif menyeluruh untuk semua `.env*` dengan allowlist `.env.example`, dump/database, private key/certificate, credential/token, log/cache/session/temp/swap/crash, dan artefak internal.
- Pemeriksaan arsip yang tampak masih terbatas pada pola tertentu dan `unzip -t`; belum ada bukti clean extraction + verifikasi seluruh checksum dari hasil ekstraksi.
- Belum ada bukti README diverifikasi dari ZIP yang diekstrak.
- Run #182 adalah regression/test gate, bukan packaging workflow.
- Run terbaru yang terlihat pada branch Agent 1 dalam pemeriksaan persiapan ini adalah DOCX Import Parser CI run `45540331755`, branch `work/limited-zip-p0-20261125`, SHA `7a89acf5e1ea9227f12b48a1f13e5d69ff0dcd05`, conclusion success. Ini bukan bukti artifact packaging.
- Karena belum ada artifact packaging yang diverifikasi, seluruh gerbang A-D berstatus NOT VERIFIED atau NOT RUN sesuai tahapnya.

## 10. Langkah saat artifact dilaporkan selesai

1. Fetch ulang Agent 1 branch HEAD dan workflow; bandingkan dengan snapshot di atas.
2. Ambil run packaging terbaru yang benar-benar terkait branch dan exact SHA tersebut.
3. Periksa run metadata, job/step logs, artifact metadata, dan unduh ZIP.
4. Jalankan Gerbang A-C secara independen.
5. Jika A-C PASS, lanjutkan D dengan lingkungan disposable.
6. Tulis laporan temuan dan checkpoint hanya pada branch Agent 2.
7. Laporkan kepada pemilik proyek dan Agent 1; jangan memperbaiki source/workflow sendiri.

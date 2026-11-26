# CHECKPOINT TERBARU — HANDOVER KOORDINATOR CBT MADRASAH
Tanggal: 26 November 2026
Repository: https://github.com/zane0184/cbt_november
Branch checkpoint: `handover/agent-coordinator-web-20261126`
Base branch: `integration/zip-vps-php83-20261126`
Base SHA: `ee38818f89627443704bbda2469cf9b16d89625c`

## 1. Tujuan checkpoint
Checkpoint ini merekam posisi terakhir sebelum pergantian agent. Agent berikutnya wajib meneruskan pekerjaan dari source dan catatan GitHub terkini, bukan mengulang patch yang sudah selesai.

## 2. AUDIT — bukti GitHub terbaru yang dibaca
Repository default branch adalah `main`; repo terakhir menerima push pada 26 November 2026. Branch kerja integrasi yang paling relevan saat checkpoint ini adalah `integration/zip-vps-php83-20261126` pada SHA `ee38818f89627443704bbda2469cf9b16d89625c`. Branch tersebut merupakan kandidat integrasi, bukan bukti bahwa seluruh PR telah digabung atau siap deploy.

Dokumen GitHub yang diperiksa:
- `README.md`
- `docs/CHECKPOINT_KOORDINATOR_2026-11-23_P0_PHPUNIT_518_PASS.md`
- `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md`
- `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`
- `docs/REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md`
- `docs/REPORT_ROMBEL_DOCX_COMPLETION_2026-11-26.md`
- `docs/REPORT_PACKAGE_EXCLUSION_PARTIAL_CP_IMPORTER_2026-11-26.md`
- `docs/REPORT_IMPLEMENTASI_IMPORT_GURU_2026-11-26.md`
- `integration-reports/backend-laravel__docs__WEB_GURU_DOCX_IMPORT_FIX_CHECKPOINT_2026-11-26.md`
- `backend-laravel/docs/AGENT4_WEB_SISWA_BROWSER_LARAVEL_INTEGRATION_GATE_2026-11-23.md`
- `backend-laravel/docs/AGENT4_WEB_SISWA_IOS_SAFARI_UAT_CHECKLIST_2026-11-23.md`

Catatan: pencarian dilakukan pada GitHub saja. Tidak menggunakan ChatGPT Library atau ZIP baseline untuk menyusun checkpoint ini.

## 3. AUDIT — status Pull Request yang relevan
Pada hasil pemeriksaan GitHub:
- PR #39 — tampilan feedback tombol Simpan dan error curriculumMapping pada Import DOCX: OPEN, DRAFT, belum merge. Satu file Blade; belum ada browser E2E/Livewire runtime/VPS UAT.
- PR #38 — integration candidate PR #33/#34/#36/#37: OPEN, DRAFT, belum merge. Head yang diperiksa `ee38818f89627443704bbda2469cf9b16d89625c`. Belum ada ZIP final; PHPUnit/CI/browser/Livewire/VPS UAT tidak dinyatakan lulus untuk kandidat ini.
- PR #37 — route Admin curriculum canonical registry: OPEN, DRAFT.
- PR #36 — perbaikan Web Guru Exam Management dan field jadwal sampai menit: OPEN, DRAFT.
- PR #34 — kumpulan perbaikan Admin, Import Siswa/Guru, branding, self-service penugasan Guru dan kurikulum: OPEN, DRAFT.
- PR #33 — perbaikan Import DOCX assignment binding: OPEN, DRAFT.
- PR #32 — penyelesaian detail Rombel dan DOCX rich content: OPEN, DRAFT.
- Jangan merge, menutup, atau mengganti base PR tanpa compare dan persetujuan yang tepat. Jangan menganggap isi PR sudah berada di main.

## 4. AUDIT — Import DOCX, pekerjaan terakhir
Pada branch integrasi, checkpoint DOCX menyatakan:
- Parser memvalidasi struktur paket DOCX, XML dan relasi internal.
- Gambar yang diterima dibatasi JPEG/PNG dengan relasi tervalidasi; media disimpan di private storage dengan checksum/metadata dan scope instalasi.
- OOXML/RTL/OMML/MathML/tabel bersarang memiliki dukungan bertingkat; formula/tabel kompleks tetap memerlukan review Guru dan fallback untuk konstruksi yang belum didukung.
- Import menghasilkan DRAFT; tidak menerbitkan soal otomatis.
- Pemetaan kurikulum dan histori harus tetap fail-closed.
- Implementasi source pada PR/branch belum sama dengan pembuktian runtime di VPS.

Pekerjaan lokal terakhir yang sudah disiapkan pada sesi sebelumnya (belum boleh dianggap sudah masuk GitHub):
- READY filter pada Save harus ditentukan ulang oleh parser server, bukan mempercayai status preview Livewire yang dapat dimodifikasi.
- Importer menolak row yang bukan READY sebagai defense-in-depth.
- Error `docx` dan `curriculumMapping` ditampilkan; tombol memberi status loading untuk aksi `importDrafts`.
- Runtime guard memeriksa DOM, ZIP, XML, MBSTRING, FILEINFO dan `finfo`.
- PHP lint dan pemeriksaan statis lokal lulus untuk file yang disebut pada laporan sesi. Tidak ada browser E2E, Livewire runtime, PHPUnit/CI, DB write, migration, atau deployment pada tahap tersebut.
- Paket patch lokal sebelumnya: `CBT_PATCH_IMPORT_DOCX_READY_FILTER_2026-11-26.zip`, SHA-256 `a653ca598aa577ea3f5fc5a2b727a4c1725f6083080abe56157918a868c1908a`. Ini hanya artefak kerja lokal, bukan source of truth GitHub dan bukan deployment ZIP. Agent baru tidak boleh mengasumsikan file tersebut tersedia atau menerapkannya ulang; audit GitHub terlebih dahulu.

## 5. AUDIT — VPS PHP 8.3 dari bukti yang diberikan pemilik
Pemilik telah memberikan konfigurasi PHP lengkap, hasil CLI, konfigurasi PHP-FPM, socket dan Vhost. Bukti yang sudah ada:
- PHP CLI 8.3 memuat `dom`, `fileinfo`, `libxml`, `mbstring`, `xml`, `xmlreader`, `xmlwriter`, `zip`.
- Class/function checks: `DOMDocument`, `ZipArchive`, `XMLReader`, `finfo`, `mb_strlen` tersedia.
- PHP-FPM 8.3 berjalan melalui socket `/tmp/php-cgi-83.sock`; Vhost `cbt.min15jakarta.sch.id` memakai include `enable-php-83.conf` yang meneruskan PHP ke socket tersebut.
- Peringatan JIT disabled karena third-party extension bukan kegagalan ekstensi DOCX.
- Konfigurasi yang dikirim memuat `display_errors=On`; ini temuan hardening produksi yang perlu ditangani secara terencana setelah memastikan file tersebut benar-benar konfigurasi aktif FPM, bukan diubah tanpa backup.
- Upload limit 500M sangat tinggi untuk DOCX; jangan ubah tanpa menyelaraskan Livewire/Nginx dan kebutuhan file.
- Bukti ini cukup untuk tidak meminta pemilik mengulang pemeriksaan ekstensi CLI. Namun, belum membuktikan PHP-FPM web runtime benar-benar menjalankan Preview dan Save DOCX.
- Tidak ada konfigurasi VPS yang diubah.

## 6. DESAIN — keputusan produk terbaru dari pemilik (menggantikan asumsi desain lama)
Keputusan baru yang harus diperlakukan sebagai arahan produk paling mutakhir:
1. Fokus sekarang Web CBT Madrasah: backend, Web Admin Madrasah, Web Guru dan Web Siswa.
2. Android dan Web Super Admin ditunda sampai Juni 2027. Jangan jadikan keduanya dependency/blocker saat ini.
3. Tidak ada layanan multi-tenant lintas madrasah. Satu paket ZIP dipasang terpisah untuk setiap madrasah, dan setiap instalasi memakai database akademik masing-masing. Data antar-madrasah tidak saling terhubung atau dapat diakses.
4. Semua instalasi madrasah kelak hanya terhubung ke Web Super Admin untuk urusan lisensi, aktivasi/entitlement dan pembayaran. Web Super Admin ditunda sampai Juni 2027.
5. Portal/role resmi aplikasi madrasah hanya ADMIN_MADRASAH, GURU, SISWA.
6. Tidak ada portal/role Operator. Semua pekerjaan administrasi Operator menjadi tanggung jawab Admin Madrasah.
7. Role/portal Kamad dibekukan atau dihapus dari akses runtime. Data nama kepala madrasah boleh tetap sebagai data identitas dokumen, bukan akun/role.
8. Proktor bukan role permanen dan bukan portal tersendiri; Proktor adalah menu/capability tambahan di Web Guru melalui penugasan sementara.
9. Kurikulum Merdeka (Kurmer) dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) adalah satu kesatuan ekosistem kurikulum, bukan dua kurikulum/modul terpisah. Integrasi harus konsisten pada data akademik, pembelajaran, asesmen, bank soal, ujian, hasil dan laporan.
10. Pemilik menyatakan aplikasi telah dipasang di `https://cbt.min15jakarta.sch.id/login`; pekerjaan utama adalah melengkapi dan memperbaiki fitur yang sudah terpasang. Jangan meminta spesifikasi VPS yang telah diverifikasi ulang tanpa bukti masalah baru.

### Rekonsiliasi dengan source lama
Dokumen GitHub lama masih mengandung istilah tenant, tenant_id, role legacy, atau asumsi SaaS multi-tenant. Jangan langsung menghapus kolom, migration, model, histori, atau mekanisme lama secara membabi buta. Agent baru wajib:
- mengaudit pemakaian tenant_id/System Tenant/tenant scope pada source aktual dan skema lokal satu instalasi;
- membedakan isolasi antar-instalasi (database terpisah) dari scope/ownership internal yang mungkin masih dipakai kode;
- mengusulkan desain transisi aman, kompatibilitas, backup dan rencana migrasi sebelum perubahan destruktif;
- memastikan tidak ada akses lintas database/instalasi;
- menghapus atau menonaktifkan role Kamad/Operator dari alur aktif secara terkontrol, sambil mempertahankan histori/audit dan tanpa merusak login/foreign key.
Keputusan terbaru pemilik mengungguli dokumen desain lama yang bertentangan, tetapi implementasi harus melalui audit dampak terlebih dahulu.

## 7. IMPLEMENTASI — perubahan yang dilakukan pada checkpoint ini
- Tidak ada source aplikasi yang diubah.
- Tidak ada migration, database, konfigurasi VPS, deployment, merge PR atau pembuatan ZIP yang dilakukan.
- Branch handover baru dibuat dari branch integrasi GitHub untuk menyimpan dokumen checkpoint dan prompt agent berikutnya.

## 8. PENGUJIAN — status nyata
- PHP CLI 8.3 extensions dan class/function checks: PASS berdasarkan output yang diberikan pemilik.
- PHP-FPM 8.3 socket dan rute Nginx ke socket: terverifikasi dari konfigurasi yang diberikan.
- PHP syntax lint dan static assertions untuk patch Import DOCX lokal: PASS sesuai catatan kerja sesi sebelumnya.
- Patch ZIP lokal: pemeriksaan arsip `unzip -t` PASS.
- Browser Preview/Save DOCX di VPS: NOT VERIFIED.
- Livewire round-trip dan validasi file DOCX nyata: NOT VERIFIED.
- PHPUnit/CI pada kandidat branch terbaru: tidak dijalankan/dinyatakan lulus dalam tahap terakhir.
- Integrasi penuh Admin/Guru/Siswa, keamanan, pemulihan, UAT madrasah: belum dibuktikan.
- Deployment ZIP final dan clean extraction: belum diverifikasi.

## 9. LANGKAH BERIKUTNYA AGENT BARU
1. Baca prompt handover yang disimpan pada `docs/HANDOVER_PROMPT_AGENT_KOORDINATOR_WEB_CBT_2026-11-26.md`.
2. Audit seluruh PR, issue, branch, commit, workflow, laporan dan checkpoint GitHub terbaru; tentukan branch source aktual yang layak menjadi basis. Jangan hanya mengandalkan PR #38.
3. Jangan membaca ChatGPT Library atau ZIP baseline. Gunakan GitHub sebagai sumber utama sesuai instruksi pemilik. Jangan mengulang source patch yang sudah ada.
4. Rekonsiliasi keputusan produk terbaru (satu instalasi/satu database per madrasah; tidak ada multi-tenant lintas madrasah; role resmi tiga; Proktor capability Guru; Android dan Super Admin ditunda; Kurmer+KBC satu ekosistem) dengan source lama secara aman.
5. Buat matriks backlog per fitur/portal, bukti source, status implementasi, status test, dan gap. Prioritaskan bug/fitur yang benar-benar belum selesai.
6. Lanjutkan perbaikan Import DOCX dari source GitHub aktual dan bukti runtime VPS yang sudah tersedia; jangan meminta pemilik mengulang cek ekstensi. Uji Preview dan Save pada file fixture DOCX nyata.
7. Lengkapi fungsi Admin/Guru/Siswa, keamanan, kurikulum dan Proktor; jangan menganggap menu tampil berarti fitur selesai.
8. Buat laporan, checkpoint, backup manifest dan catatan pengujian di GitHub setiap tahap.
9. Hasil akhir adalah satu ZIP Web CBT lengkap untuk di-upload/extract di VPS per madrasah, setelah seluruh fitur prioritas lulus verifikasi, paket diperiksa dan prosedur backup/rollback tersedia.
10. Jangan menyatakan siap produksi/distribusi sebelum bukti integrasi, keamanan, stabilitas, recovery, UAT, paket dan clean extraction lengkap.

## 10. TAUTAN YANG WAJIB DIBACA PEMILIK
Pada pesan pemilik yang memerintahkan checkpoint ini, terdapat kalimat “kamu harus dan wajib membaca link link di bawah ini:” tetapi daftar URL tidak ikut terkirim setelah tanda titik dua. Jangan menebak URL. Minta pemilik mengirim daftar tautan itu satu kali, lalu baca seluruhnya dari GitHub/URL yang diberikan dan tambahkan hasilnya ke checkpoint berikutnya.

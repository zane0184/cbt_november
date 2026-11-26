# Checkpoint Lanjutan Agent — CBT Madrasah
Tanggal: 26 November 2026

Repository: `zane0184/cbt_november`
Branch kandidat integrasi: `integration/zip-vps-php83-20261126`
PR integrasi: [#38](https://github.com/zane0184/cbt_november/pull/38)
HEAD terverifikasi: `482c5b7109f62760301792325a27e55235499440`
Base PR #38: `work/rombel-docx-completion-20261126` @ `841a1dd90dbe0277026fade1511a39b5dc9216b6`

## AUDIT

### Posisi GitHub
- PR #38: OPEN, DRAFT, belum merged, mergeable=true pada pemeriksaan 26 November 2026.
- PR #34: OPEN, DRAFT, belum merged; branch `work/admin-import-curriculum-fixes-20261126`.
- PR #33, #36, #37: OPEN, DRAFT, belum merged saat pemeriksaan; perubahan terpilih telah direkonsiliasi ke PR #38.
- PR #38 adalah kandidat integrasi terbaru yang diperiksa; jangan menganggapnya siap merge/deploy hanya dari status mergeable.

### Workflow pada HEAD #38
Commit `482c5b7109f62760301792325a27e55235499440`:
- PHP Runtime Validation (PR 34): SUCCESS — run [45783652381](https://github.com/zane0184/cbt_november/actions/runs/45783652381).
- Build Standalone Web CBT Deployment ZIP: SUCCESS — run [45783652401](https://github.com/zane0184/cbt_november/actions/runs/45783652401).
- Artefak CI: `cbt-madrasah-web-deployment-356cb9b7264d055d67153ff78a73a1eea1031c75`, ukuran 19,831,898 byte, digest SHA-256 `aea9c580db51b204445f93364c4fa10b1096f427dae0e07b79ebe3646a16379c`, kedaluwarsa 26 Desember 2026.
- Workflow ZIP berhasil membangun, menguji arsip, mengekstrak ke direktori kosong, dan memeriksa manifest checksum sesuai job yang sukses.
- Ini hanya bukti pipeline build/package; bukan bukti PHPUnit, browser UAT, VPS preflight, migration MySQL, atau kesiapan produksi.

### Temuan source CP
- `DatabaseSeeder` memanggil `CurriculumReferenceSourceSeeder` dan `CurriculumRegistryBootstrapSeeder`, tetapi tidak memanggil `ImportBahasaIndonesiaFaseCCpSeeder`.
- Importer Bahasa Indonesia Fase C masih ada di source PR #38 dan hanya memuat empat outcome Fase C. Jangan jalankan sebagai penyelesaian registry CP penuh.
- Build workflow menyalin `backend-laravel/` dengan daftar pengecualian; daftar yang diperiksa tidak mengecualikan `database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php`. Dengan demikian file seeder satu-kali tersebut masuk ke kandidat ZIP build kecuali ada filter tambahan di scanner/staging. Periksa isi artefak aktual sebelum menyatakan seeder tidak ikut.
- `CurriculumReferenceSourceSeeder` saat ini mencatat BKPDM 020/2026 melalui siaran pers, bukan PDF keputusan; Kepdirjen Pendis 9941/2025 masih PENDING dan URI-nya menunjuk berita Kemenag Batam, bukan PDF keputusan. Keduanya perlu dibenahi dengan provenance yang tepat sebelum aktivasi sumber.
- Gate pada `TeacherCurriculumContextService` tetap mensyaratkan source `ARTIFACT_CHECKSUM_VERIFIED`, checksum sumber, checksum outcome, dan kecocokan hash. Jangan melonggarkan gate.
- Skema canonical CP: unique struktur+kode; FK provenance mengikat source dan tenant; outcome harus berada pada subject node dan phase yang benar.

### Rekonsiliasi regulasi
- BSKAP 046/H/KR/2025 Lampiran II secara eksplisit mencakup SD/MI; fase A kelas I–II, B kelas III–IV, C kelas V–VI. PDF resmi: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- BKPDM 020/2026 mengubah CP Pendidikan Agama dan Budi Pekerti pada Lampiran II/V BSKAP 046. Siaran pers resmi 23 Juni 2026 menyebut hanya mata pelajaran Agama dan Budi Pekerti yang berubah; mata pelajaran lain tetap pada 046/2025: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Salinan PDF BKPDM 020/2026 dapat dibaca pada https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf, tetapi host bukan domain resmi; raw bytes dan tanda tangan digital belum diverifikasi.
- Kepdirjen Pendis 9941/2025 menetapkan CP PAI dan Bahasa Arab pada madrasah, termasuk lima mapel MI. Salinan dapat dibaca di https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab-pada-Madrasah.pdf; sumber salinan bukan domain resmi pusat. Buku MI yang dipublikasikan melalui domain Kemenag juga secara eksplisit menyebut penggunaan Kepdirjen 9941/2025, KMA 1503/2025 dan KBC 6077/2025 (contoh: https://cms2025.kemenag.go.id/storage/files/Alquran%20Hadis%20Kelas%201.pdf).
- Belum ditemukan penjelasan resmi yang secara eksplisit menguraikan hubungan hukum BKPDM 020/2026 dengan CP PAI/Bahasa Arab khas madrasah 9941/2025. Jangan menyimpulkan keduanya identik atau saling menggantikan. CP PAI untuk import tetap BLOCKED sampai hubungan dan cakupan penerapannya dikonfirmasi.
- KMA 1503/2025 mengatur implementasi kurikulum madrasah; bukan pengganti dokumen CP per mapel.
- Pendis 9941/2025: Al-Qur'an Hadis, Akidah Akhlak, Fikih, Bahasa Arab memiliki A/B/C yang ditemukan; SKI yang ditemukan hanya B/C. Jangan membuat SKI Fase A dari asumsi.

## DESAIN / PRIORITAS

1. Pertahankan instalasi mandiri: satu aplikasi dan database terpisah per madrasah; jangan menghidupkan kembali shared academic multi-tenant.
2. Portal aktif saat ini: Admin Madrasah, Guru, Siswa. Super Admin Publisher dan Android ditunda sampai Juni 2027.
3. Fokus berikutnya: inventarisasi CP umum BSKAP per mapel/fase dari Lampiran II, memisahkan bagian PAIBP yang diubah BKPDM 020/2026 dan PAI/Bahasa Arab khas madrasah.
4. Untuk setiap CP, simpan teks persis, halaman/lampiran/elemen/fase, subject node, kode stabil, sumber, masa berlaku, dan checksum sumber.
5. Jangan buat TP/ATP otomatis atau sintetis; TP/ATP disusun Guru dari CP yang sah.
6. Perbaiki packaging agar importer Fase C parsial tidak ikut ZIP distribusi sebelum dihapus/diganti dengan importer penuh yang disetujui.

## IMPLEMENTASI
- Tidak ada source aplikasi, migration, seeder, atau database yang diubah pada checkpoint ini.
- Tidak ada koneksi atau perubahan pada VPS Publisher/Tenant.
- Tidak ada migration produksi.
- Checkpoint ini hanya dokumentasi baru pada branch integrasi.

## PENGUJIAN
- Dua workflow GitHub Actions di atas: PASS pada HEAD #38.
- PHPUnit: belum dijalankan.
- Browser/Livewire UAT: belum dijalankan.
- MySQL 8 disposable, migration, importer, idempotensi, tenant/install isolation, checksum mismatch: belum diuji.
- VPS preflight Publisher dan Tenant: belum dibuktikan pada sesi ini.
- UAT Admin/Guru/Siswa: belum dilakukan.

## Langkah berikutnya
1. Periksa artefak ZIP CI secara langsung untuk memastikan daftar isi dan apakah importer parsial ikut.
2. Buat audit source dan backup sebelum memperbaiki packaging / menghapus importer parsial.
3. Ekstrak CP umum BSKAP 046 per mapel/fase berdasarkan Lampiran II; untuk PAIBP, gunakan revisi BKPDM 020 dengan provenance resmi yang sah.
4. Dapatkan penegasan resmi Kemenag tentang penerapan CP 020/2026 versus Pendis 9941/2025 pada MI.
5. Audit struktur node subject dari bootstrap seeder agar manifest CP terhubung ke node yang benar.
6. Setelah sumber primer dan manifest lengkap tervalidasi, rancang importer idempotent/transaksional untuk MySQL disposable.
7. Jalankan UAT VPS hanya setelah ada bukti akses dan persetujuan tindakan yang relevan.

**Status keseluruhan: kandidat integrasi + build ZIP CI PASS; belum siap merge, belum siap deployment, CP import BLOCKED, VPS/UAT belum terverifikasi.**

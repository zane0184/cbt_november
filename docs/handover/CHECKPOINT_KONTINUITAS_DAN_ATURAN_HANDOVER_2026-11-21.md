# CHECKPOINT KEBIJAKAN KONTINUITAS PROYEK CBT MADRASAH
Tanggal: 21 November 2026
Jenis: Dokumentasi / checkpoint saja
Basis source: `zane0184/cbt_november`
Branch acuan source: `fix/web-portal-usability-rbac-20261120`
HEAD acuan saat checkpoint: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`

## 1. Instruksi pemilik proyek
Mulai checkpoint ini, setiap pekerjaan proyek yang dilakukan ChatGPT wajib dicatat dan diperbarui di GitHub. Setiap tahap harus memiliki laporan dan checkpoint yang dapat ditelusuri. Jangan mengklaim perubahan atau pengujian berhasil tanpa bukti.

Setiap perpindahan coding agent wajib disertai laporan handover dan prompt agent baru yang lengkap, operasional, serta berdasarkan source aktual dan keputusan proyek terbaru.

## 2. Isi minimum laporan setiap tahap
Pisahkan dengan tegas:
- AUDIT: sumber yang diperiksa, versi/branch/commit, temuan, bukti, dan batas pemeriksaan.
- DESAIN: keputusan yang berlaku, opsi yang ditolak/ditunda, dampak, risiko, acceptance criteria.
- IMPLEMENTASI: file yang benar-benar berubah, alasan, backup sebelum perubahan, hash sebelum/sesudah, commit/PR.
- PENGUJIAN: perintah dan lingkungan yang benar-benar dijalankan, hasil aktual, log/bukti, pengujian yang belum dijalankan dan alasannya.
- CHECKPOINT: status, blocker, pekerjaan berikutnya, batas kewenangan, dan keputusan yang masih diperlukan.

## 3. Handover dan prompt agent baru
Setiap handover harus menyertakan:
1. Tujuan dan status proyek terkini.
2. Arsitektur dan keputusan desain yang masih berlaku.
3. Repository, branch, HEAD commit, dan PR relevan.
4. Source yang menjadi acuan serta provenance arsip referensi.
5. Tahap selesai, tahap tertunda, blocker, dan risiko.
6. Daftar file berubah dan lokasi backup beserta checksum.
7. Pengujian yang benar-benar lulus/gagal/belum dijalankan.
8. Langkah kerja berikutnya secara berurutan.
9. Batas larangan: tidak mengubah source di luar scope, tidak menghapus/menimpa baseline, tidak menyentuh database produksi, tidak menjalankan Gradle via terminal, dan tidak mengaktifkan Device Lease/Fencing.
10. Acceptance criteria dan format laporan balik agent.

Prompt agent baru harus dapat dipakai langsung tanpa meminta pengguna mengulang keputusan atau mengirim ulang sumber yang sudah tersedia.

## 4. Aturan GitHub
- Setiap pekerjaan yang berdampak pada status proyek harus menghasilkan pembaruan laporan/checkpoint di GitHub.
- Perubahan source dilakukan pada branch kerja terpisah dan melalui backup serta verifikasi.
- Jangan commit source yang belum diperiksa.
- Jangan menimpa atau mengubah baseline ZIP.
- Jangan menggabungkan PR atau mengubah branch acuan tanpa persetujuan yang sesuai.
- Jika pekerjaan terhenti karena blocker, tetap commit laporan blocker dan bukti pemeriksaan; jangan membuat patch spekulatif.
- Catat URL commit/PR dan SHA setiap pembaruan.

## 5. Status kerja P0.1 saat checkpoint
P0.1 telah disetujui pengguna untuk dimulai, tetapi implementasi belum dilakukan. Gate sebelumnya terhenti karena workspace canonical tidak tersedia pada runtime dan PHP 8.3 + Composer + MySQL 8/InnoDB disposable test environment belum terbukti tersedia. Tidak ada source patch, migration, database, atau deployment yang dilakukan.

HEAD kandidat yang telah diaudit: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`.

## 6. Arsip handover 18 November sebagai referensi
Pengguna mengunggah `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18(02).zip` dan menetapkan bahwa ZIP tersebut hanya referensi historis, bukan source aktif yang boleh diambil mentah-mentah.

Hasil pemeriksaan yang tercatat:
- Arsip memuat source backend Laravel dan dokumentasi historis.
- Migration `2026_11_05_190008_create_question_multi_curriculum_mappings_table.php` ditemukan; memiliki `Schema::dropIfExists('question_multi_curriculum_mappings')` di awal `up()`.
- Migration historis `2026_11_06_200002_enforce_curriculum_integrity_not_null.php` tidak ditemukan sebagai source PHP; status tetap NOT RECOVERED / NOT VERIFIED.
- `CurriculumIntegrityPreflightCommand.php` dilaporkan pada Tahap 4B/4C di path `backend-laravel/app/Console/Commands/CurriculumIntegrityPreflightCommand.php`, tetapi source PHP aslinya belum diverifikasi dalam arsip.
- Library juga berisi laporan Tahap 4B/4C dan provenance 200002. Laporan bukan pengganti source.
- Semua desain historis harus direkonsiliasi dengan keputusan proyek terbaru dan HEAD aktif sebelum digunakan.

## 7. Batas lingkup
- Lanjutkan P0.1 saja sesuai persetujuan pengguna.
- Jangan mulai P0.2 atau P0.3 tanpa persetujuan terpisah.
- Jangan membuat ulang migration 200002 atau menganggap command historis sebagai source aktif.
- Jangan mengaktifkan atau merekonstruksi runtime Device Lease/Fencing.
- Jangan mengubah Android; pekerjaan Android ditunda sampai Juni 2027.
- Jangan menjalankan migration produksi atau deployment.

## 8. Tindakan berikutnya
1. Periksa ulang source aktual, branch, HEAD, dan file migration 190008.
2. Cari serta verifikasi seluruh sumber dan backup relevan, termasuk arsip referensi dan Library.
3. Verifikasi runtime pengujian terisolasi PHP 8.3, Composer, MySQL 8/InnoDB.
4. Jika seluruh gate terpenuhi, backup source yang akan diubah, implementasikan P0.1 sesuai desain yang disetujui, dan uji hanya pada database disposable.
5. Perbarui laporan/checkpoint GitHub dengan diff, hash, bukti tes, blocker, dan rekomendasi.
6. Tunggu review pengguna sebelum P0.2.

Status keseluruhan pada checkpoint ini: P0.1 BLOCKED — BELUM ADA PATCH. Produksi NO-GO.

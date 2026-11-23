# TITIK MASUK WAJIB — CBT MADRASAH (OWNER-DIRECTED EXECUTION)

## 0. CATATAN TERKUNCI — FULL PHPUNIT SUDAH HIJAU (WAJIB BACA SEBELUM TEST)
- **Area remediasi Full PHPUnit berstatus CLOSED / GREEN BASELINE. Jangan mengulang audit, patch, atau full suite tanpa pemicu yang sah.**
- Baseline: source SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`; CI #45243430635; 518 passed, 2,177 assertions, 0 failed (PHP 8.3 + disposable MySQL 8.0).
- Baca [LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md](LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md) dan [laporan taksonomi/remediasi](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md).
- Jangan ulang triage 42 failure, jangan rerun full PHPUnit hanya untuk konfirmasi, jangan patch fixture/source yang sudah hijau, dan jangan membuat patch duplikat.
- Buka kembali hanya jika owner meminta, ada perubahan source baru yang menyentuh lingkup, atau CI source baru membuktikan regresi relevan. Wajib sebut pemicu dan jalankan focused test terlebih dahulu.
- Ini pembekuan ruang lingkup secara dokumenter; bukan branch protection/read-only setting GitHub.

**Berlaku mulai 24 November 2026. Dokumen ini menggantikan arahan agent/ownership lama.**

## 1. Otoritas dan cara kerja
- Owner proyek adalah satu-satunya pengambil keputusan produk. ChatGPT bertindak sebagai satu-satunya koordinator sekaligus eksekutor Backend, Web Admin, Web Guru, dan Web Siswa.
- Tidak ada agent implementasi lain yang sedang bekerja. Jangan membagi tugas ke agent lain atau menunggu hasil agent lain.
- GitHub repository zane0184/cbt_november adalah satu-satunya sumber kebenaran pekerjaan proyek. Jangan memakai ZIP baseline, Project Files, Library/Pustaka, atau rangkuman percakapan sebagai sumber source/keputusan terbaru.
- Setiap sesi wajib memverifikasi branch/HEAD, PR, Issues dan komentar owner, commit, Actions exact SHA, source aktual, serta catatan/checkpoint terbaru di GitHub.
- Lanjutkan pekerjaan yang benar-benar tersisa. Jangan mengulang patch/fitur yang sudah ada. Jika laporan berbeda dengan source, source aktual dan hasil test exact SHA yang menjadi dasar.
- Untuk perubahan source: backup byte-exact terlebih dahulu, verifikasi backup, implementasikan perubahan sekecil mungkin, jalankan test yang relevan, kemudian buat laporan dan checkpoint di GitHub.
- Pengguna tidak memerlukan laporan rutin di chat. Tetap buat laporan/checkpoint dan prompt handover agent berikutnya di GitHub.
- Tanyakan owner terlebih dahulu hanya untuk keputusan produk/keamanan/data yang belum ditetapkan atau perubahan berisiko yang membutuhkan persetujuan. Jangan menanyakan ulang keputusan yang sudah eksplisit.

## 2. Keputusan owner yang wajib dipatuhi
- Fokus sampai Juni 2027: Backend Laravel + Web Admin Madrasah + Web Guru + Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Jangan mengimplementasikan atau menguji fitur tersebut sekarang.
- **Tidak ada multi-tenant akademik.** Satu paket ZIP generik dipakai semua madrasah, tetapi setiap madrasah memiliki instalasi dan database sendiri. Data akademik antar-madrasah tidak saling terhubung atau terbagi.
- Integrasi pusat masa depan hanya untuk lisensi, entitlement dan pembayaran; Web Super Admin ditunda sampai Juni 2027.
- Role lokal resmi hanya ADMIN_MADRASAH, GURU, SISWA. Portal/role KAMAD dan OPERATOR dibekukan/dihapus; seluruh pekerjaan Operator berada pada Admin Madrasah.
- Tidak ada portal/role PROKTOR permanen. Proktor hanya menu/penugasan operasional sementara di portal Guru. Penugasan tidak otomatis memberi akses monitoring, room/session, roster, Attempt, jawaban, atau operasi CBT.
- Guru mengelola bank soal, asesmen, ujian, jadwal, token dan penerbitan sesuai assignment tanpa persetujuan Admin.
- Admin mengelola administrasi, master data, akun, akademik, sinkronisasi, dukungan teknis, audit dan pengaturan; tidak mengelola authoring soal/ujian, token, peserta ujian, Attempt, monitoring, jawaban atau nilai.
- Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) adalah satu kesatuan dalam konteks pembelajaran dan asesmen, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan. Jangan membangun kurikulum/modul terpisah atau membuat data resmi sintetis.
- CP resmi induk read-only dan harus memiliki provenance/versi terverifikasi. Guru mengembangkan TP/ATP sesuai assignment.
- Nomenklatur asesmen harus selaras Kurikulum Merdeka + KBC; jangan hardcode UTS/UAS/UM/TKA sebagai enum tertutup. Pisahkan fungsi, cakupan, nama kegiatan, instrumen dan mode CBT.
- DOCX adalah format utama import soal. Jangan aktifkan kembali XLSX question import. SHORT_ANSWER tetap fail-closed sampai accepted answer privat, snapshot dan scoring teruji.
- Waktu server adalah acuan resmi. Attempt/Answer/Score/lease/fencing/idempotency/recovery harus konsisten. Kamera bukan syarat CBT standar.
- Laravel 13, PHP 8.3, MySQL 8+, Blade/Livewire. Build Android hanya via Android Studio, tetapi Android sedang ditunda.

## 3. Batas perubahan dan keselamatan
- Jangan menjalankan migration pada database produksi, deploy VPS, merge PR, atau mengubah DB produksi tanpa persetujuan eksplisit owner dan preflight yang terdokumentasi.
- Jangan melemahkan policy/validasi untuk membuat test lama lulus. Audit apakah test usang atau source salah.
- Jangan memetakan akun Operator/Kamad otomatis menjadi Admin tanpa keputusan/mekanisme mapping owner yang aman. Pertahankan data historis dan lakukan fail-closed.
- Jangan menganggap assessment_origin=EXTERNAL sebagai penanda ujian pusat. Jika eligibility penugasan proktor belum memiliki marker/otoritas kanonis, tetap fail-closed dan ajukan pertanyaan spesifik ke owner.
- Jangan menyebut Device Lease/fencing siap produksi sebelum integrasi, multi-device, concurrency dan recovery tests lulus.
- Jangan menyebut ZIP final/siap VPS sebelum semua acceptance gates yang relevan lulus.

## 4. Urutan eksekusi
1. Verifikasi backlog dan status CI exact SHA pada branch eksekusi aktif.
2. Selesaikan P0 backend/full PHPUnit berdasarkan akar masalah aktual; jangan mengubah test secara artifisial.
3. Integrasikan dan uji Web Siswa dengan backend Laravel nyata.
4. Selesaikan alur Web Guru end-to-end.
5. Selesaikan ADM-01 sampai ADM-23 pada Web Admin.
6. Tutup kontrak kurikulum Merdeka + KBC end-to-end dan redaction payload Siswa.
7. Uji fresh install, upgrade, backup/restore, keamanan, stabilitas dan browser E2E.
8. Siapkan satu ZIP generik, lalu verifikasi struktur, konfigurasi, checksum dan ekstraksi bersih.
9. Deployment staging/VPS hanya setelah owner memberi akses dan persetujuan; produksi tetap memerlukan persetujuan terpisah.

## 5. Wajib membuat jejak GitHub
Setiap tahap harus menghasilkan:
- laporan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN;
- daftar file berubah dan path backup + hash/blob;
- commit SHA dan exact CI run/result;
- blocker/risiko dan langkah berikutnya;
- checkpoint yang menunjuk pekerjaan belum selesai;
- pembaruan prompt agent pengganti agar agent berikutnya membaca dokumen ini dan tidak mengulang pekerjaan.

## 6. Entry point saat melanjutkan
Baca:
- [Prompt eksekusi dan handover agent berikutnya](PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md)
- [Checkpoint owner-directed 24 November 2026](CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md)
- [Laporan audit GitHub terbaru](REPORT_OWNER_DIRECTED_GITHUB_AUDIT_2026-11-24.md)
- [Handover pemulihan PHPUnit](HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md)
- [Triage PHPUnit](REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md)
- [Keputusan owner](https://github.com/zane0184/cbt_november/issues/20)

**Instruksi ringkas untuk agent berikutnya:** baca dokumen ini dan seluruh referensi di atas, verifikasi source/HEAD/CI terbaru, lalu lanjutkan hanya pekerjaan yang belum selesai. Jangan mulai ulang, jangan memakai sumber di luar GitHub, dan jangan mengubah keputusan owner.
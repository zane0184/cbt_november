# Addendum Audit Sumber CP MI — 26 November 2026

Addendum untuk `REPORT_AUDIT_CP_MI_COMPLETENESS_2026-11-26.md`.

## AUDIT — sumber berhasil dibuka

### 1. BSKAP 046/H/KR/2025
Dokumen PDF lengkap berhasil dibuka dan teksnya dapat ditelusuri melalui sumber resmi:
https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf

Bukti dalam dokumen:
- Halaman keputusan menyebut Lampiran II memuat CP SD/MI (baris dokumen 32–45 pada ekstraksi web).
- Fase A = kelas I–II MI, Fase B = kelas III–IV MI, Fase C = kelas V–VI MI.
- Dokumen ditetapkan 16 Juli 2025.
- Lampiran II berisi CP per mata pelajaran.
- Siaran pers Kemendikdasmen tanggal 23 Juni 2026 menyebut BKPDM 020/2026 hanya mengubah CP Agama dan Budi Pekerti; mata pelajaran lainnya tetap mengacu BSKAP 046/2025:
  https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti

### 2. Kepdirjen Pendis 9941 Tahun 2025
PDF lengkap berhasil dibuka dan teksnya dapat ditelusuri:
https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab.pdf

Dokumen menyebut:
- Ditetapkan 28 November 2025.
- CP PAI dan Bahasa Arab untuk RA dan madrasah.
- Bagian MI mencakup Al-Qur’an Hadis, Akidah Akhlak, Fikih, Sejarah Kebudayaan Islam, dan Bahasa Arab.
- Setiap mata pelajaran memiliki bagian CP per fase; contoh Al-Qur’an Hadis secara eksplisit memuat Fase A (kelas 1–2 MI), Fase B (kelas 3–4 MI), dan Fase C (kelas 5–6 MI).
- Bahasa Arab MI merupakan bagian tersendiri dari CP Pendis.

Tautan pendukung penjelasan struktur dokumen:
https://www.ayomadrasah.id/2025/12/cp-pai-bahasa-arab-di-madrasah-pdf-docx.html

### 3. Batas verifikasi artefak
PDF dapat dibaca melalui ekstraksi dokumen web, tetapi pengunduhan byte PDF ke workspace belum berhasil pada sesi ini. Karena itu:
- belum ada file PDF lokal untuk diarsipkan;
- belum ada SHA-256 hasil hitung dari file lokal;
- sumber tidak boleh diubah menjadi `ARTIFACT_CHECKSUM_VERIFIED`;
- importer belum boleh menandai outcome sebagai terverifikasi checksum sampai artefak yang benar diarsipkan dan di-hash.

## DESAIN — pemetaan sumber CP

| Kelompok mata pelajaran | Sumber acuan |
|---|---|
| Al-Qur’an Hadis | Kepdirjen Pendis 9941/2025 |
| Akidah Akhlak | Kepdirjen Pendis 9941/2025 |
| Fikih | Kepdirjen Pendis 9941/2025 |
| Sejarah Kebudayaan Islam | Kepdirjen Pendis 9941/2025 |
| Bahasa Arab | Kepdirjen Pendis 9941/2025 |
| Pendidikan Pancasila | BSKAP 046/H/KR/2025 |
| Bahasa Indonesia | BSKAP 046/H/KR/2025 |
| Matematika | BSKAP 046/H/KR/2025 |
| IPAS | BSKAP 046/H/KR/2025; fase/mapel mengikuti dokumen dan struktur MI |
| PJOK | BSKAP 046/H/KR/2025 |
| Seni dan Budaya | BSKAP 046/H/KR/2025; pilihan seni harus mengikuti struktur kurikulum madrasah |
| Bahasa Inggris | BSKAP 046/H/KR/2025; status penerapan mengikuti ketentuan transisi KMA 1503/2025 |
| Muatan Lokal | Tidak ada satu CP nasional generik yang boleh diisikan ke semua madrasah; konten ditetapkan sesuai kurikulum satuan pendidikan/ketentuan daerah dan harus diberi sumber serta status yang benar |
| Koding dan Kecerdasan Artifisial | Tidak boleh otomatis dianggap CP wajib MI; struktur source saat ini menandainya pilihan dan perlu cek dokumen CP khusus serta kesiapan satuan pendidikan |

Catatan: pemetaan di atas adalah peta dokumen sumber, bukan pernyataan bahwa isi CP per mapel/fase sudah seluruhnya diekstrak dan diverifikasi.

## IMPLEMENTASI
Tidak ada source aplikasi, database, migration, atau VPS yang diubah. Addendum ini hanya memperbarui dokumentasi audit.

## PENGUJIAN
Belum dilakukan import, checksum lokal, tes idempotensi, uji relasi, atau UAT dropdown CP.

## Tindakan berikutnya
1. Peroleh byte PDF lokal yang identik dengan dokumen resmi yang dibuka.
2. Hitung SHA-256 dan arsipkan secara immutable.
3. Ekstrak seluruh isi CP per mapel/fase dari dua dokumen, kemudian lakukan review kelengkapan.
4. Verifikasi cakupan perubahan BKPDM 020/2026 terhadap mapel Agama dan Budi Pekerti dalam konteks madrasah. Jangan menggabungkan otomatis dengan Pendis 9941/2025 tanpa kajian regulasi.
5. Baru buat importer idempoten dengan cakupan verifikasi per sumber dan per outcome.

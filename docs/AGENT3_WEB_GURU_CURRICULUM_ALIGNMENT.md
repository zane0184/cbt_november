# Web Guru — Alignment Kurikulum dan Scope Produk Web

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
Baseline HEAD sebelum dokumen: `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`

## AUDIT

### Keputusan distribusi produk

Sampai Juni 2027, implementasi produk dibatasi pada:
1. Web Admin Madrasah.
2. Web Guru.
3. Web Siswa.

Ditunda sampai Juni 2027:
- Web Super Admin.
- Aplikasi Android native, Android Studio, Kotlin/Compose/Room/WorkManager/Retrofit.
- Android API client, APK/AAB dan distribusi Android Studio.

Produk akhir Web CBT didistribusikan sebagai satu ZIP source yang dapat dipasang pada instalasi madrasah yang berbeda. Setiap madrasah memiliki instalasi, database akademik, storage, kredensial, konfigurasi dan backup masing-masing. Data akademik antarmadrasah tidak saling terhubung. Tenant ID dapat tetap menjadi pertahanan internal dalam satu instalasi, bukan dasar shared academic database. Hubungan Super Admin pada masa mendatang terbatas pada lisensi, pembayaran dan status layanan; tidak boleh mengakses data akademik.

### Kurikulum

Keputusan proyek mengharuskan Web Guru konsisten dengan:
- Kurikulum Merdeka.
- Kurikulum Berbasis Cinta / Panca Cinta.
- Pembelajaran Mendalam.
- Kurikulum dan versi berikutnya.

Audit sebelumnya menemukan model CP/TP dan registrasi versi kurikulum, serta representasi KBC melalui `MultiLearningOutcome` dengan `outcome_type=KBC_CINTA`. Hal tersebut belum membuktikan bahwa seluruh alur Guru telah terhubung atau bahwa seluruh mapping resmi sudah tersedia.

## DESAIN

Kurikulum bukan toggle opsional dan bukan metadata Bank Soal saja. Konteks akademik harus konsisten pada:
1. Data mapel dan assignment Guru.
2. Rombel, kelas dan fase.
3. CP/TP/ATP sesuai versi kurikulum yang terdaftar.
4. Pemetaan Panca Cinta dari sumber resmi dan memiliki provenance.
5. Bank Soal dan QuestionVersion.
6. Assessment.
7. Exam dan immutable question snapshot.
8. Hasil belajar dan report.

Prinsip integrasi:
- Gunakan versi kurikulum yang terdaftar pada instalasi/tenant madrasah dan simpan identitas versinya pada relasi akademik yang relevan.
- Jangan mengarang CP, TP, ATP, dimensi Panca Cinta, atau relasi Pembelajaran Mendalam.
- Bila sumber resmi atau mapping belum tersedia, tampilkan status belum ditetapkan/perlu data; jangan membuat daftar placeholder yang tampak resmi.
- Perubahan versi kurikulum tidak boleh menghapus atau menulis ulang histori akademik lama.
- Guru hanya dapat memilih mapel/rombel/kurikulum dalam assignment dan lingkup madrasahnya.
- Web Admin Madrasah mengelola master data/registrasi yang menjadi sumber resmi; Web Guru mengonsumsi data yang diotorisasi.
- Snapshot ujian harus mempertahankan konteks versi akademik saat ujian diterbitkan; perubahan kurikulum sesudahnya tidak mengubah snapshot historis.

### Tipe soal

Hanya enam tipe yang didukung source:
MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY. Tidak ada tipe ketujuh atau opsi E. SHORT_ANSWER tetap BLOCKED sampai accepted answers tersimpan, tersnapshot dan dipakai scoring; ESSAY manual review.

### Pengalaman Web Guru

Arah UX: profesional, modern/premium tetapi ringan; adaptif desktop/tablet/mobile; mendukung Arabic/RTL dan harakat serta persamaan matematika. Isi soal/kunci tidak boleh berubah diam-diam. Guru harus melihat konteks subject, grade/fase, academic year dan curriculum version sebelum menyimpan/menerbitkan soal atau ujian.

## IMPLEMENTASI

Tidak ada perubahan source, mapping kurikulum, seed data, model, migration atau UI dilakukan. Tidak ada mapping resmi yang dibuat dari asumsi.

## PENGUJIAN

Belum ada browser E2E atau validasi operasional Guru/Admin/Siswa untuk alur kurikulum end-to-end. Butir uji berikut menjadi acceptance gate:
- Tenant A tidak dapat memilih referensi akademik Tenant B.
- Assignment Guru membatasi mapel/rombel.
- CP/TP/ATP dan KBC terikat ke versi resmi yang tepat.
- Perubahan versi mempertahankan histori.
- QuestionVersion, Assessment, Exam snapshot dan report menunjukkan konteks versi yang konsisten.
- Arabic/RTL, harakat, tabel dan rumus tampil tanpa kehilangan data.

**Status:** alignment adalah persyaratan desain; implementasi lintas modul belum terbukti selesai.

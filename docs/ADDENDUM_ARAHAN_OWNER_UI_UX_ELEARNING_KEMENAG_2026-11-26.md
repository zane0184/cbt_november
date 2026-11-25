# Addendum Arahan Owner — Familiaritas UI/UX eLearning Kemenag Kurtilas
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Status: arahan desain; belum ada implementasi source.

## Arahan owner
Owner mengonfirmasi bahwa ZIP Web CBT telah diuji langsung di VPS dan berhasil diunggah/diekstrak/dijalankan pada lingkungan VPS miliknya. Kendala utama yang dirasakan bukan instalasi, melainkan visual dan alur penggunaan Web Admin/Guru yang masih berantakan, membingungkan, dan terasa kaku dibandingkan eLearning Madrasah Kemenag yang biasa digunakan pada era Kurtilas.

Owner ingin CBT yang secara visual dan pengalaman penggunaan terasa familiar seperti eLearning Madrasah Kemenag era Kurtilas, karena Admin dan Guru sudah terbiasa dengan pola tersebut. Sistem akademik/asesmen tetap Kurikulum Merdeka dan KBC sebagai satu kesatuan. Ini adalah arahan UI/UX, bukan perubahan kurikulum atau pengembalian engine ke Kurtilas.

## Batas wajib
- **Jangan ubah kode pada tahap ini.** Dokumen ini hanya mencatat arahan.
- Jangan mengubah database, migration, API, model akademik, engine CBT, atau kontrak integrasi Kurmer + KBC hanya demi meniru tampilan lama.
- Jangan membuat ulang fitur backend yang sudah selesai.
- Jangan menganggap desain persis eLearning lama telah diketahui hanya dari ingatan atau deskripsi. Sebelum desain rinci, audit source UI saat ini di GitHub dan kumpulkan referensi visual eLearning Kemenag Kurtilas yang disetujui owner.
- Perubahan UI nantinya harus mempertahankan tiga portal aktif: Admin Madrasah, Guru, Siswa. Proktor tetap tugas/menu tambahan di Web Guru.
- Android dan Web Super Admin tetap ditunda sampai Juni 2027.
- Perhatikan aksesibilitas, responsivitas, performa, konsistensi navigasi, serta layar ujian siswa yang harus sederhana dan fokus.

## Arah desain yang harus dieksplorasi (belum keputusan detail)
1. Familiaritas navigasi: struktur menu, istilah, pengelompokan fitur, breadcrumb, dan posisi aksi mengikuti pola yang mudah dikenali pengguna eLearning Kemenag.
2. Dashboard: hirarki informasi, ringkasan aktivitas, status kelas/ujian, dan pintasan kerja ditata agar mudah dipindai, bukan sekadar kumpulan kartu dekoratif.
3. Alur Guru: penugasan → materi/pembelajaran → bank soal → asesmen/ujian → hasil/laporan harus terasa runtut dan mudah ditemukan.
4. Alur Admin: data madrasah → data Guru/Siswa → kelas/rombel → mapel/penugasan → dukungan operasional dan laporan.
5. Form dan tabel: label bahasa Indonesia yang familiar, filter/pencarian jelas, tombol aksi konsisten, status mudah dipahami, serta pesan validasi yang membantu.
6. Visual: lebih hangat, ramah, tidak kaku, tidak terlalu padat, dengan tipografi, warna, ikon, jarak, dan hierarki yang konsisten.
7. Kurmer + KBC tetap menyatu dalam konten dan alur akademik; jangan memecahnya menjadi dua sistem atau dua menu kurikulum yang tidak perlu.

## Tahap sebelum implementasi UI
- Audit semua layout, sidebar, dashboard, halaman utama, komponen Livewire, view Admin/Guru/Siswa, state kosong/error/loading, dan responsive behavior pada branch source terbaru.
- Petakan setiap halaman ke pola eLearning Kemenag yang ingin dipertahankan. Tandai mana yang hanya perlu penataan visual dan mana yang perlu penyederhanaan alur.
- Siapkan moodboard/wireframe atau daftar rancangan halaman untuk disetujui owner sebelum patch.
- Buat screenshot baseline dan catat daftar file yang akan diubah.
- Saat implementasi kelak, backup byte-exact per file, patch kecil bertahap, jalankan test terkait dan visual regression/E2E yang tersedia. Jangan sentuh backend kecuali audit membuktikan kebutuhan dan ada persetujuan terpisah.

## Koreksi status paket
Catatan audit koordinator sebelumnya menyatakan artifact GitHub berhasil dibuat tetapi independent extraction/UAT belum dibuktikan oleh audit agent. Owner kini mengonfirmasi ZIP sudah diuji dan berhasil pada VPS miliknya. Status yang tepat: **VPS ZIP installation/runtime smoke test dilaporkan berhasil oleh owner; bukti uji dan screenshot belum diperiksa oleh koordinator.** Ini tidak otomatis membuktikan semua fitur berfungsi atau UI sudah diterima.

## Checkpoint
- Fokus terdekat berubah: dari sekadar mengejar kelengkapan fitur menjadi audit dan perancangan ulang UI/UX Web Admin, Guru, dan Siswa berbasis familiaritas eLearning Kemenag.
- Belum ada perubahan kode.
- Belum ada desain rinci yang disetujui.
- Tunggu persetujuan owner atas rancangan UI sebelum implementasi.

Status: **OWNER UX DIRECTION RECORDED — DESIGN AUDIT PENDING — NO SOURCE CHANGES**.

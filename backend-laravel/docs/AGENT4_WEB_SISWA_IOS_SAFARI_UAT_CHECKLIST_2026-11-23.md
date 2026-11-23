# Agent 4 — Checklist UAT Web Siswa, iOS Safari, dan Madrasah
Tanggal: 23 November 2026
Status: CHECKLIST DISIAPKAN — belum dijalankan pada perangkat dan pengguna madrasah.

## AUDIT
Web Siswa memiliki empat halaman utama: login, daftar ujian, runner ujian, dan hasil. Chromium headless dan MySQL disposable dapat menguji sebagian perilaku; keduanya tidak menggantikan Safari iOS, VoiceOver, jaringan sekolah, dan praktik madrasah.

## DESAIN UAT
Pelaksanaan hanya pada instalasi dan database uji yang terpisah dari produksi. Gunakan akun uji Admin Madrasah, Guru, dan Siswa. Jangan memakai data pribadi siswa sungguhan kecuali telah mendapat izin dan perlindungan yang sesuai.

### A. iOS Safari
- Safari iOS versi aktual dan satu versi sebelumnya yang masih didukung.
- iPhone portrait/landscape; iPad portrait/landscape bila tersedia.
- Login, remember-me bila kebijakan mengizinkan, logout, back/forward, reload.
- Daftar ujian: status jadwal, token, attempt count, label status, tautan hasil.
- Runner: safe area/notch, viewport resize, keyboard virtual, scroll saat input, drawer nomor soal, tombol sentuh minimal 44–48 CSS px.
- Arabic/harakat/RTL dan konten campuran Arab-Indonesia.
- Input pilihan tunggal, pilihan ganda kompleks, benar/salah, matching, essay.
- IndexedDB: izin penyimpanan, reload, tab background/foreground, Safari memory pressure, private browsing behavior.
- Jaringan putus/sambung, perubahan Wi-Fi ke seluler, tab ditutup paksa, perangkat sleep/wake.
- Timer dan expiry dibandingkan waktu server, bukan jam perangkat.
- Media image/audio/video/PDF; audio/video seek dan Range; Object URL cleanup.
- Submit, response loss, status recovery, receipt, dan hasil.
- VoiceOver: label kontrol, pengumuman nomor soal, fokus keyboard, kontras dan zoom.
- Tidak mengandalkan deterrent copy sebagai kontrol keamanan.

### B. UAT operasional madrasah
1. Admin Madrasah memastikan data siswa/rombel/penugasan benar.
2. Guru menerbitkan ujian, token otomatis tersedia sebelum jadwal, dan pengaturan require_token sesuai.
3. Siswa yang tidak ditugaskan tidak dapat melihat/membuka ujian.
4. Ujian tidak dapat dimulai sebelum jadwal; dapat dimulai pada waktu yang diizinkan server; setelah jadwal ditutup start baru ditolak.
5. Attempt limit, resume, perangkat berbeda, logout, dan session expiry.
6. Simpan jawaban per jenis soal yang didukung; SHORT_ANSWER tetap nonaktif.
7. Putus jaringan, reload, reconnect, konflik, response loss, dan pemulihan tanpa duplikasi.
8. Media soal, submit normal, expiry otomatis, dan hasil setelah dipublikasikan.
9. Guru memeriksa hasil dan Admin memeriksa audit log; siswa tidak dapat melihat hasil siswa lain.
10. Simulasikan insiden: perangkat rusak, takeover resmi, reset/force submit/perpanjangan waktu hanya oleh petugas berwenang dan tercatat.

## FORM CATATAN HASIL
Untuk setiap skenario catat: ID skenario, tanggal/waktu lokal, versi aplikasi/backend, perangkat dan versi OS/browser, jaringan, akun/role uji, langkah, hasil yang diharapkan, hasil aktual, bukti tersanitasi, PASS/FAIL/BLOCKED, severity, dan tindak lanjut.

## PENGUJIAN
Belum ada UAT nyata yang dilakukan. Checklist ini bukan hasil PASS. UAT harus dilakukan bersama Admin, Guru, dan Siswa pada instalasi uji, setelah kontrak API dan browser-to-Laravel integration lulus.

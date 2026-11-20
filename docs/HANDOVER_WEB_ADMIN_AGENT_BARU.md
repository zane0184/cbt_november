# HANDOVER AGENT BARU — WEB ADMIN MADRASAH

Dokumen ini adalah halaman masuk untuk agent baru.

## Baca berurutan
1. [Prompt Induk Agent Baru Web Admin](PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md)
2. [Spesifikasi Induk Web Admin Madrasah](SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md)
3. [Checkpoint Web Admin 21 November 2026](CHECKPOINT_WEB_ADMIN_2026-11-21.md)
4. [Prompt Induk Audit Menyeluruh CBT Madrasah](PROMPT_INDUK_AUDIT_MENYELURUH_CBT_MADRASAH.md) — sebagai aturan umum proyek, bukan izin memperluas scope.

## Status singkat
- Fokus sekarang hanya Web Admin Madrasah.
- Belum ada audit source Web Admin menyeluruh berdasarkan spesifikasi baru.
- Belum ada desain yang disetujui untuk implementasi.
- Belum ada implementasi Web Admin yang boleh diklaim selesai.
- Portal Guru dan Siswa ditahan sampai tahap sebelumnya selesai dan disetujui.
- P0 database dan Device Lease/Fencing adalah pekerjaan terpisah; jangan disentuh sebagai bagian Web Admin.

## Perintah pertama
Baca semua dokumen di atas, verifikasi status GitHub/PR/branch terkini, kemudian lakukan audit source Web Admin secara READ-ONLY. Serahkan laporan AUDIT dan DESAIN untuk persetujuan pengguna sebelum coding.

## Branch dokumentasi
`docs/web-admin-agent-handover-20261121`

Dokumentasi ini tidak mengubah source aplikasi, migration, database, .env, konfigurasi VPS, atau deployment.


## Temuan UI terbaru yang wajib dibaca
Pengguna melaporkan sidebar gelap tidak dapat di-scroll dan brand “Panel Admin & Guru” tidak konsisten untuk halaman Admin. Requirement lengkap dan acceptance criteria sudah ditambahkan pada:
- Bagian 11 di `PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md`
- Bagian 25 di `SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md`
- Bagian 10 di `CHECKPOINT_WEB_ADMIN_2026-11-21.md`

Statusnya masih temuan untuk diaudit; belum ada klaim perbaikan. Agent harus memperbaiki source setelah audit, desain, dan persetujuan pengguna.

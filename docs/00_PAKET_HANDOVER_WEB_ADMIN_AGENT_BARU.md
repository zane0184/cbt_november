# PAKET HANDOVER TERPADU — AGENT BARU WEB ADMIN CBT MADRASAH

Tanggal paket: 21 November 2026  
Repository: https://github.com/zane0184/cbt_november  
Branch dokumentasi: `docs/web-admin-agent-handover-20261121`  
Fokus: penyelesaian total Web Admin Madrasah — audit, desain, implementasi, pengujian, validasi.

## Tujuan paket

Dokumen ini adalah pintu masuk tunggal untuk agent baru. Baca seluruh dokumen yang dirujuk sebelum menyentuh source. Paket ini mengonsolidasikan keputusan pengguna dan mengunci scope agar tidak terjadi pengulangan kerja, konflik desain, atau implementasi di luar kewenangan.

## Urutan baca wajib

1. [Prompt Induk Agent Baru Web Admin](PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md) — mandat operasional dan instruksi eksekusi.
2. [Spesifikasi Induk Web Admin Madrasah](SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md) — kebutuhan produk dan acceptance criteria.
3. [Checkpoint Web Admin 21 November 2026](CHECKPOINT_WEB_ADMIN_2026-11-21.md) — posisi dan status yang tercatat; verifikasi ulang terhadap source/PR aktual.
4. [Handover Agent Baru Web Admin](HANDOVER_WEB_ADMIN_AGENT_BARU.md) — ringkasan transfer dan langkah pertama.
5. [Register Keputusan Web Admin](REGISTER_KEPUTUSAN_WEB_ADMIN.md) — keputusan final dan prioritas jika ada dokumen lama yang bertentangan.
6. [Matriks Audit Web Admin](MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md) — format bukti audit source.
7. [Matriks Pengujian dan Acceptance Web Admin](MATRIKS_PENGUJIAN_WEB_ADMIN.md) — cakupan tes dan bukti penerimaan.
8. [Peta Dokumen dan Provenance CBT](PETA_DOKUMEN_PROVENANCE_CBT.md) — cara menemukan laporan historis, sumber, dan batas pemakaiannya.

## Status yang diketahui pada saat paket disusun

- Spesifikasi Web Admin telah ditulis sampai bagian 30.
- Prompt agent telah ditulis sampai bagian 16.
- Checkpoint dan handover terdahulu tersedia di branch dokumentasi ini.
- Belum ada bukti bahwa audit source Web Admin menyeluruh berdasarkan spesifikasi final sudah selesai.
- Belum ada desain hasil audit yang disetujui pengguna.
- Karena itu, agent baru wajib memulai dari verifikasi GitHub/source aktual dan audit READ-ONLY; bukan langsung melakukan patch.
- PR #5/#6/#7 dan branch terkait harus diperiksa status aktualnya. Status yang pernah dicatat di dokumen lama bukan bukti status hari ini.
- Tidak ada izin merge, deployment, migration produksi, perubahan VPS, atau perubahan konfigurasi rahasia yang diberikan oleh paket ini.

## Keputusan alur ujian yang paling baru dan mengikat

Guru membuat soal/ujian, menentukan mapel dan jadwal akademik. Setelah tersimpan, metadata ujian otomatis muncul pada halaman Admin **Daftar Ujian & Penugasan Proktor**. Admin hanya memilih ujian tersebut dan menentukan Guru pengawas. Admin tidak membuat ulang jadwal, tidak mengetik ulang mapel, dan tidak mengubah detail akademik ujian. Jika Guru mengubah jadwal/mapel, data list harus tersinkron; assignment yang terdampak ditandai perlu ditinjau dan tidak diubah diam-diam.

Jika bagian lama menyatakan Admin membuat kalender/jadwal ujian dari awal, ketentuan itu dikoreksi oleh bagian 30 spesifikasi dan bagian 16 prompt agent serta Register Keputusan ini.

## Gerbang kerja wajib

**Verifikasi → Audit READ-ONLY → Laporan Audit → Desain → Persetujuan pengguna → Implementasi bertahap → Pengujian nyata → Validasi end-to-end → Persetujuan penutupan.**

Agent harus berhenti setelah menyerahkan Audit + Desain. Tidak boleh coding sebelum pengguna menyetujui desain dan urutan implementasi.

## Aturan pembaruan paket

Setiap agent:
- memverifikasi branch/PR/commit sebelum bekerja;
- memperbarui checkpoint setelah setiap tahap;
- menambahkan laporan audit/desain/implementasi/pengujian dengan bukti;
- memperbarui register keputusan hanya jika pengguna membuat keputusan baru;
- tidak menghapus catatan historis; tandai statusnya superseded/obsolete dengan rujukan keputusan baru;
- membuat handover baru sebelum pergantian agent.

## Mulai di sini

Salin seluruh isi [Prompt Induk Agent Baru Web Admin](PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md) sebagai instruksi kepada agent. Minta agent menjalankan hanya langkah pertama: membaca paket dan melakukan verifikasi + audit READ-ONLY. Jangan minta agent langsung memperbaiki source sebelum hasil audit dan desain ditinjau.

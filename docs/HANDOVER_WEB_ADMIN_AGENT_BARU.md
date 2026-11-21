# HANDOVER AGENT BARU — WEB ADMIN MADRASAH

Dokumen ini adalah halaman masuk untuk agent baru. Gunakan [Paket Handover Terpadu](00_PAKET_HANDOVER_WEB_ADMIN_AGENT_BARU.md) sebagai indeks utama.

## Baca berurutan
1. [Paket Handover Terpadu](00_PAKET_HANDOVER_WEB_ADMIN_AGENT_BARU.md)
2. [Prompt Induk Agent Baru Web Admin](PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md)
3. [Spesifikasi Induk Web Admin Madrasah](SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md)
4. [Checkpoint Web Admin 21 November 2026](CHECKPOINT_WEB_ADMIN_2026-11-21.md)
5. [Register Keputusan Web Admin](REGISTER_KEPUTUSAN_WEB_ADMIN.md)
6. [Matriks Audit Web Admin](MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md)
7. [Matriks Pengujian Web Admin](MATRIKS_PENGUJIAN_WEB_ADMIN.md)
8. [Peta Dokumen dan Provenance CBT](PETA_DOKUMEN_PROVENANCE_CBT.md)
9. [Prompt Induk Audit Menyeluruh CBT Madrasah](PROMPT_INDUK_AUDIT_MENYELURUH_CBT_MADRASAH.md), bila tersedia pada branch/source yang dipilih — aturan umum proyek, bukan izin memperluas scope.

## Posisi terakhir yang tercatat
- Fokus aktif hanya Web Admin Madrasah.
- Spesifikasi induk sudah memuat kebutuhan sampai bagian 30.
- Prompt agent sudah memuat instruksi sampai bagian 16.
- Belum ada bukti audit source Web Admin menyeluruh berdasarkan spesifikasi final.
- Belum ada desain hasil audit yang disetujui pengguna.
- Belum boleh melakukan patch source sebelum pengguna menyetujui hasil audit, desain, dan urutan implementasi.
- Portal Guru/Siswa ditahan.
- P0 database dan Device Lease/Fencing tetap pekerjaan terpisah.
- Tidak ada izin merge, deployment, perubahan VPS, atau migration produksi.

## Keputusan alur ujian/proktor paling baru
Guru membuat soal/ujian, memilih mapel dan jadwal akademik. Setelah disimpan, metadata ujian otomatis muncul di halaman **Daftar Ujian & Penugasan Proktor** Web Admin. Admin hanya menetapkan Guru pengawas dari list tersebut. Admin tidak membuat ulang jadwal/mapel dan tidak mengubah detail akademik ujian. Detail ujian read-only. Perubahan jadwal Guru harus tersinkron dan assignment terdampak ditandai perlu ditinjau ulang.

Jika ada teks terdahulu yang menyebut Admin membuat kalender/jadwal ujian dari awal, ikuti bagian 30 spesifikasi, bagian 16 prompt, dan Register Keputusan; teks terdahulu itu telah dikoreksi.

## Instruksi pertama agent
1. Baca paket di atas sampai selesai.
2. Periksa GitHub: default branch, semua branch relevan, PR #5/#6/#7, head/base SHA, checks, reviews, dan commit terbaru. Jangan mengandalkan status historis.
3. Cari seluruh laporan audit, P0, provenance Device Lease, backup, manifest, checkpoint, dan source terkait.
4. Pilih baseline Web Admin yang benar-benar ada; catat alasan dan commit SHA.
5. Audit seluruh source Web Admin secara READ-ONLY.
6. Periksa runtime aktual dan jalankan hanya pemeriksaan aman yang tidak mengubah DB/production.
7. Isi matriks audit dan pengujian; tandai PASS/FAIL/BLOCKED/NOT RUN berdasarkan bukti.
8. Serahkan laporan AUDIT + DESAIN kepada pengguna.
9. Berhenti. Jangan coding sebelum persetujuan eksplisit.

## Batas keselamatan
Dokumentasi ini tidak mengizinkan:
- merge PR #5/#6/#7;
- mengubah source aplikasi sebelum gerbang persetujuan;
- migration/eksperimen pada DB produksi;
- mengubah .env, secret, APP_KEY, vhost/Nginx, VPS, storage produksi;
- mengaktifkan Device Lease/Fencing;
- mengerjakan Portal Guru/Siswa, Android, atau P0 database sebagai bagian Web Admin.

## Branch dokumentasi
`docs/web-admin-agent-handover-20261121`

Semua perubahan di paket ini adalah dokumentasi. Source aplikasi tidak diubah.



## ADDENDUM HANDOVER — KEPUTUSAN FINAL STATUS PROKTOR (21-11-2026)
Keputusan pengguna final dan ACTIVE:
- PENDING tidak memberikan capability.
- Hanya CONFIRMED yang dapat memberikan capability setelah Guru aktif, TeacherProfile valid/aktif, tenant, exam, rombel, schedule revision, permission/scope, revoke/conflict dan waktu server tervalidasi.
- ACTIVE bukan status assignment.
- NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED tidak memberikan capability.
- Capability hanya pada starts_at <= serverNow < expires_at; validasi setiap request, tidak bergantung cron.
- Reschedule oleh Guru melalui ExamScheduleChangeService mengubah assignment terdampak menjadi NEEDS_REVIEW; setelah review sah status kembali CONFIRMED.
Rujukan normatif: Addendum 35 spesifikasi, DEC-WA-026 register, nota keputusan final. Addendum 34 dan nota OPEN/PROPOSED lama SUPERSEDED.

Agent penerus wajib melanjutkan seluruh temuan yang masih OPEN, bukan hanya Proktor: role OPERATOR/ADMIN legacy dan migrasi identitas, exact permission Admin, penolakan endpoint CBT Admin, Guru ownership, Proktor eligibility/assignment, overlap/concurrency/audit, shared schedule service, tenant/User isolation, domain/installation identity, Kurmer-KBC provenance, dan seluruh test matrix. Semua test yang belum dijalankan tetap NOT RUN. Dokumen ini tidak memberi izin mengubah source, database, migration, atau melakukan merge.

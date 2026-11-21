# CHECKPOINT W0 — WEB ADMIN MADRASAH
Tanggal: 21 November 2026
Status: AUDIT READ-ONLY DILAKUKAN; MENUNGGU PERSETUJUAN SOURCE/DESAIN

## Posisi aktual
- Scope aktif: Web Admin Madrasah saja.
- Working source kandidat: recovery/source-preservation-20261121 @ 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0.
- Source code pada branch recovery sama dengan PR #5 head ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2; perbandingan menunjukkan 7 commit tambahan berupa lima dokumen recovery, tanpa perubahan source aplikasi.
- Default branch main bukan source Laravel aktif menurut audit tree.
- PR #5/#6/#7 tetap OPEN, DRAFT, UNMERGED.
- Dokumentasi paling mutakhir berada pada docs/source-canonical-design-closure-20261121 @ 59aa76ae60de396216e9754d200cb1b6d806e9e0; branch ini enam commit di depan branch handover PR #7 dan belum di-merge.

## AUDIT
- Source statis 338 PHP/Blade aktif dan 23 kelompok modul ADM direkonsiliasi dalam audit 21 November.
- P0: Admin masih mempunyai akses close/monitor, detail jawaban/nilai, finalize/reopen; permission dan policy tidak sesuai keputusan Admin metadata-only + assignment.
- Role OPERATOR dan PROKTOR permanen masih dibuat/diizinkan; portal Operator/Proktor masih ada.
- Proctor picker/service masih mensyaratkan PROKTOR; belum menerima semua Guru aktif.
- Assignment belum punya overlap check, rombel_id, NEEDS_REVIEW, schedule revision/event.
- TenantScoped fail-closed dan X-Tenant-Override diabaikan; User model, existing installation domain binding, dan SystemTenantSeeder masih perlu hardening.
- Arsip baseline/recovered ditemukan pada Library, tetapi raw bytes tidak dapat dimaterialisasi; CRC/hash/isi belum diverifikasi.
- Workspace /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ tidak tersedia pada runtime ini.

## DESAIN
Rencana W0–W10 sebagaimana LAPORAN_AUDIT_READ_ONLY_W0_WEB_ADMIN_2026-11-21.md. Keputusan inti:
- ADMIN_MADRASAH satu-satunya role aktif Admin; OPERATOR/ADMIN hanya alias migration-only.
- Tidak ada role/portal Proktor permanen; semua Guru aktif dapat ditugaskan secara sementara.
- Admin hanya administrasi, master data, metadata ujian Guru read-only, dan penugasan pengawas.
- Guru kelak memiliki operasional CBT penuh; tidak dikerjakan sekarang.
- Satu madrasah = satu instalasi = satu DB; tenant_id/TenantContext tetap defense-in-depth.
- Kurmer/KBC dan seluruh histori akademik dipertahankan.
- Daftar ujian Admin membaca record ujian yang dibuat Guru; Admin tidak membuat ulang mapel/jadwal.
- Assignment memakai interval [start,end), overlap locking/current read pada MySQL 8/InnoDB, NEEDS_REVIEW saat reschedule, dan audit event transaksional.

## IMPLEMENTASI
Tidak ada source aplikasi, migration, database, .env, secret, PR existing, merge, deployment, atau VPS yang diubah. Dokumentasi saja.

## PENGUJIAN
- Pemeriksaan branch/PR/commit dan source statis: dilakukan.
- Runtime: PHP 8.4.24; PHP 8.3, Composer, MySQL, pdo_mysql, Docker tidak tersedia.
- CI #671 bukan pada head terkini: 23 smoke tests passed/83 assertions; full suite 18 failed, 462 passed/1.866 assertions pada commit d264c8d.
- Tidak ada CI PASS yang dibuktikan untuk PR #5 head ade5daa.
- T-001–T-132 belum dijalankan sebagai acceptance Web Admin terkini.
- Browser/responsive, MySQL concurrency, import/rollback, restore, UAT: NOT RUN.

## Pekerjaan berikutnya
1. Minta persetujuan working source candidate dan desain.
2. Setelah disetujui, buat branch implementasi terpisah dari baseline yang disepakati.
3. Mulai W1 dengan backup sebelum setiap patch dan route/action matrix.
4. Jalankan test di environment PHP 8.3 + Composer 2 + MySQL 8 disposable; jangan gunakan database produksi.
5. Update checkpoint dan manifest per tahap.

## Gerbang
W0 belum dinyatakan selesai karena raw ZIP baseline belum diverifikasi dan persetujuan source candidate belum diberikan. W1 belum dimulai. Tidak boleh coding, merge, migration, deployment, atau aktivasi Device Lease/Fencing sebelum persetujuan.



## CATATAN SUPERSESSION — KEPUTUSAN PROKTOR FINAL (21-11-2026)
Dokumen ini adalah checkpoint W0 historis pada waktu pemeriksaan. Untuk desain assignment Proktor, gunakan keputusan final pengguna pada Addendum 35 spesifikasi dan DEC-WA-026 register: PENDING deny; CONFIRMED satu-satunya status yang dapat memberi capability setelah seluruh validasi; ACTIVE bukan status assignment; NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED deny; interval starts_at <= serverNow < expires_at. Catatan lama yang menggunakan ACTIVE sebagai status assignment adalah HISTORICAL/SUPERSEDED. Source tetap OPEN dan test runtime NOT RUN.

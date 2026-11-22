# CHECKPOINT PENUTUPAN STAGE 4B–4C — AUDIT DESAIN UJIAN SERENTAK
Tanggal: 22 November 2026

## Status akhir

- Stage 4B: **PASS pada lingkup implementasi/CI**; audit desain selesai dengan guardrail operasional session masih menunggu keputusan.
- Stage 4C: **PASS pada lingkup implementasi/CI**.
- Device Lease/fencing: belum dinyatakan siap produksi.
- Tidak ada source, migration, schema, workflow, atau database produksi yang diubah pada audit desain.

## Git dan bukti CI

- Repository: `zane0184/cbt_november`
- Branch Agent 1: `work/stage4b-room-session-20261122`
- SHA source yang lulus CI: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`
- HEAD dokumentasi saat checkpoint: `5d4d9dc7350fbab49a38842ce1f8964bed42cd88`
- Perbandingan source CI ke HEAD: 3 commit dokumentasi saja; source aplikasi/migration/workflow tidak berubah.
- CI run: https://github.com/zane0184/cbt_november/actions/runs/44984303003
- Job `safe-runtime-tests`: success; 35/35 step, 0 failure, 0 skipped.
- Empat suite target: 24 test, 120 assertions.
- Artifact: `wa01-alternative-runtime-44984303003-1`, ID `14739069544`, SHA-256 `62856a1a0b94b77db9e096c711f24985cca4c7c0dfbaf354362ba9ab44034904`.

## Kesimpulan audit desain Stage 4B

1. Siswa memulai attempt berdasarkan `Exam.start_at/end_at` dan waktu server. Jalur start tidak membaca ExamSession atau room assignment. Jadi ExamSession **tidak membuat gelombang akses peserta**.
2. ExamSession bukan sekadar grouping: ia punya starts_at/ends_at, status lifecycle, dapat di-reschedule dalam batas Exam, dipakai menghitung overlap ruang, dan menjadi batas window assignment Proktor.
3. Perbedaan waktu session tidak mengubah eligibility peserta, tetapi dapat menimbulkan mismatch: siswa sudah boleh mulai berdasarkan Exam global sementara assignment Proktor untuk room tertentu belum aktif.
4. Token otomatis dibuat saat Guru menerbitkan/mengunci Exam melalui `ExamController::lock`; token melekat pada Exam dan divalidasi saat start.
5. Proktor adalah Guru dengan assignment sementara pada Exam + session-room, bukan role permanen. Monitoring endpoints membatasi peserta/attempt/events menggunakan confirmed assignment dan active room placement.
6. Room/rombel/participant dapat dipindah sebelum session mulai dengan melepas assignment lama dan membuat mapping baru; histori disimpan, schedule_revision naik, assignment Proktor terdampak menjadi NEEDS_REVIEW. Exam.start_at/end_at tidak diubah.

**Guardrail desain yang direkomendasikan tetapi belum disetujui/diimplementasikan:** satu pelaksanaan serentak per Exam; idealnya satu ExamSession operasional per Exam dengan banyak ExamSessionRoom. Jika beberapa session record diperlukan untuk administrasi, semua starts_at/ends_at harus sama dengan Exam.start_at/end_at. Alternatifnya, definisikan secara eksplisit session window sebagai window coverage Proktor dan sediakan prosedur coverage seluruh ruang sejak Exam dimulai.

Laporan rinci dan bukti source: `docs/AUDIT_DESAIN_STAGE4B_UJIAN_SERENTAK_2026-11-22.md`.

## Dokumen terkait

- `docs/LAPORAN_STAGE4B_STAGE4C_VERIFIKASI_2026-11-22.md`
- `docs/CHECKPOINT_STAGE4B_STAGE4C_VERIFIED_2026-11-22.md`
- `docs/AUDIT_DESAIN_STAGE4B_UJIAN_SERENTAK_2026-11-22.md`
- Backup manifest Stage 4B: `docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md`
- Backup manifest Stage 4C: `docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md`

## Handover dan batas Agent 1

- Agent 1 menutup Stage 4B–4C dan menyerahkan temuan desain.
- Agent 2 tetap pemilik Stage 4D/Device Lease-fencing. Jangan mengubah branch atau source Agent 2.
- Agent 3 tetap pemilik Web Guru. Koordinasikan bahwa waktu peserta berasal dari Exam global, bukan ExamSession.
- Tidak ada merge, rebase, PR, deployment, atau perubahan database produksi.
- Tidak ada test baru dijalankan selama audit desain; CI yang dirujuk adalah bukti sebelumnya.
- Menunggu persetujuan desain guardrail session sebelum patch apa pun.

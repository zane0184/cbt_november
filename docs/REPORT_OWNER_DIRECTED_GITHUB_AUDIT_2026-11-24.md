# LAPORAN AUDIT KOORDINATOR — OWNER-DIRECTED EXECUTION

Tanggal: 24 November 2026 (WIB)
Repository: zane0184/cbt_november
Branch pemeriksaan: work/solo-web-cbt-completion-20261123-execution
Source HEAD yang diperiksa: acc24ed24ea9c37baf1355067235e97af06ca325

## AUDIT
- Owner menegaskan hanya ada satu eksekutor: ChatGPT sebagai koordinator sekaligus pelaksana backend, Web Admin, Web Guru dan Web Siswa. Agent lain tidak sedang bekerja.
- GitHub adalah satu-satunya sumber kebenaran. ZIP baseline, Project Files dan Library/Pustaka dilarang digunakan sebagai dasar audit/implementasi.
- Keputusan owner yang berlaku: Web CBT saja sampai Juni 2027; Android dan Web Super Admin ditunda; instalasi/database mandiri per madrasah; tidak ada multi-tenant akademik atau sharing data; pusat hanya untuk lisensi/pembayaran pada fase mendatang; role lokal hanya Admin Madrasah/Guru/Siswa; Kamad/Operator dibekukan/dihapus; Proktor hanya menu/penugasan sementara pada Guru.
- Issue #20 memuat keputusan owner terperinci termasuk kewenangan Guru/Admin, taxonomy asesmen Kurmer+KBC, kontrak akademik, redaction Siswa dan batas penugasan Proktor.
- Branch execution HEAD yang diperiksa: acc24ed24ea9c37baf1355067235e97af06ca325. CI run #45228426438 pada exact SHA tersebut: 42 failed, 476 passed, 1,863 assertions. Focused login/tenant tests lulus pada run tersebut; full PHPUnit gagal.
- Log CI menunjukkan antara lain: insert assessment_curriculum_mappings gagal karena academic_year_id wajib tetapi tidak disertakan oleh fixture; query role menghasilkan role_user.tenant_id yang tidak tersedia di schema pada StudentRombelManagement; dan TeacherPracticeWorkflowTest mengalami model Exam null setelah proses saveExam. Ketiganya perlu ditelusuri terhadap migration, model scope, authorization dan fixture aktual sebelum patch.
- ExamListContractTest pada source yang diperiksa telah mendefinisikan assignment lokal dan memanggil bindCanonicalTeacherAssignment; jangan mengulang perbaikan lama atau menambahkan assignment kedua tanpa membuktikan regresi pada exact HEAD.
- Handover lama memuat beberapa branch/SHA/angka CI historis. Status tersebut tidak boleh dianggap terkini; selalu cek ulang Actions dan source exact HEAD.
- Proctor eligibility tetap fail-closed selama tidak ada marker/otoritas kanonis. Jangan mengubahnya hanya untuk meluluskan test.

## DESAIN
- Satu jalur eksekusi dan satu kontrak backend; PR lama hanya bahan audit sampai bagian yang sesuai keputusan owner direkonsiliasi.
- Perbaikan test harus mencerminkan kontrak produk terkini. Test usang dapat diganti dengan negative access/regression tests; jangan mengembalikan portal/permission yang telah dilarang owner.
- Tidak ada perubahan schema atau deployment produksi dalam tahap audit ini.

## IMPLEMENTASI
- Backup byte-exact dibuat sebelum memperbarui entrypoint:
  - docs/backup/AGENT_START_HERE.md.before-owner-directed-single-executor-2026-11-24
  - blob asal: ae554d9c8b023fea3995fe8f84ca7efdc84c11a7
  - backup commit: e1714723f5d9c7f34d90739b25b65faa4b8a7bd0
- docs/AGENT_START_HERE.md diperbarui agar menghapus instruksi ownership multi-agent dan memuat keputusan owner terkini.
  - commit update: cf362ccb6de2bc2a8876ac7be84580c97249052d
- Dibuat prompt handover untuk agent berikutnya di docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md.
- Tidak ada source aplikasi, migration, konfigurasi runtime, atau database yang diubah pada tahap audit ini.

## PENGUJIAN
- Backup entrypoint disalin dari konten GitHub yang di-fetch sebelum perubahan; commit backup berhasil.
- Entry point diperbarui menggunakan blob SHA yang di-fetch dari branch target; update commit berhasil.
- Belum ada test aplikasi baru pada tahap dokumentasi ini. CI #45228426438 tetap bukti kegagalan source SHA acc24ed..., bukan bukti patch dokumentasi.
- Tidak ada merge, production migration, deployment, atau perubahan DB.

## CHECKPOINT / PEKERJAAN BERIKUTNYA
1. Verifikasi ulang branch HEAD dan Actions terbaru setelah commit dokumentasi.
2. Ambil seluruh kegagalan dari log CI exact SHA dan kelompokkan berdasarkan akar masalah aktual.
3. Mulai dari kegagalan backend P0 yang dapat diperbaiki tanpa mengubah kontrak owner; backup file satu per satu sebelum patch.
4. Lanjutkan Web Siswa, Guru, Admin dan integrasi kurikulum secara berurutan.
5. Buat laporan/checkpoint setelah tiap tahap dan perbarui prompt agent pengganti.
6. Deployment/VPS dan final ZIP tetap diblokir sampai acceptance tests dan persetujuan owner.

Status: IN PROGRESS — NOT READY FOR DISTRIBUTION.
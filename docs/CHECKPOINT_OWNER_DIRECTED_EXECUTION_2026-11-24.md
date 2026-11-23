# CHECKPOINT — OWNER-DIRECTED EXECUTION

Tanggal: 24 November 2026 (WIB)
Repository: zane0184/cbt_november
Branch kerja: work/solo-web-cbt-completion-20261123-execution

## Keputusan yang mengikat
- Satu owner, satu eksekutor ChatGPT; tidak ada agent implementasi lain.
- GitHub satu-satunya sumber kebenaran; jangan buka ZIP baseline, Project Files atau Library/Pustaka.
- Fokus Backend + Web Admin + Web Guru + Web Siswa. Android dan Web Super Admin ditunda sampai Juni 2027.
- Satu ZIP generik untuk semua madrasah, instalasi dan database terpisah per madrasah. Tidak ada multi-tenant akademik/data sharing.
- Role aktif hanya ADMIN_MADRASAH, GURU, SISWA. Kamad/Operator dibekukan/dihapus; Proktor hanya menu/penugasan sementara pada Guru.
- Kurikulum Merdeka + KBC satu kesatuan bersama Pembelajaran Mendalam dan 8 dimensi profil lulusan.

## Status aktual yang telah diverifikasi
- Source HEAD yang diperiksa pada branch execution: acc24ed24ea9c37baf1355067235e97af06ca325.
- CI #45228426438 pada exact SHA: 42 failed, 476 passed, 1,863 assertions; full PHPUnit FAIL.
- Focused login/tenant smoke tests pada run tersebut PASS. Ini bukan bukti full suite atau kesiapan produksi.
- Log CI memuat failure fixture academic_year_id, query role_user.tenant_id, serta workflow Guru yang gagal membentuk Exam. Akar masalah harus dikonfirmasi dengan audit source/migration terkait sebelum perubahan.
- Source ExamListContractTest yang diperiksa telah memiliki assignment dan canonical binding; jangan mengulang patch assignment.
- Belum ada browser E2E nyata seluruh portal, UAT, staging VPS, backup/restore acceptance, atau ZIP final siap distribusi.

## Pekerjaan yang dilakukan pada checkpoint ini
- Backup dan pembaruan docs/AGENT_START_HERE.md untuk menghapus ownership multi-agent lama dan menetapkan satu eksekutor.
- Prompt agent pengganti dibuat pada docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md.
- Laporan audit dibuat pada docs/REPORT_OWNER_DIRECTED_GITHUB_AUDIT_2026-11-24.md.
- Tidak ada perubahan source aplikasi.

## Langkah berikutnya
1. Verifikasi ulang exact HEAD/CI terbaru.
2. P0 backend: triage akar masalah dan remediasi PHPUnit tanpa melemahkan policy.
3. Web Siswa real Laravel E2E.
4. Web Guru end-to-end.
5. Web Admin ADM-01..23.
6. Kurikulum Merdeka + KBC end-to-end, snapshot dan redaction.
7. Fresh install, upgrade, backup/restore, security, stability, UAT.
8. ZIP generik final dan staging/VPS setelah persetujuan.

Status: IN PROGRESS — NOT READY FOR DISTRIBUTION.
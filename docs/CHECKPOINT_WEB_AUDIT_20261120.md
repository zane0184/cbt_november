# Checkpoint Web CBT — Audit & Perbaikan Lanjutan
Tanggal: 2026-11-20
Branch: `fix/web-portal-usability-rbac-20261120`
PR: #5 (Draft)

## AUDIT
- Memeriksa komponen UserManagement, StudentRombelManagement, ProctorAssignmentManagement, ResultList, QuestionBank, ExamManagement, AcademicManagement, CurriculumManagement; model tenant terkait; TenantScoped; IdentifyTenant; ExamPolicy; ReportPolicy; QuestionPolicy; TeacherAccess; routes/web.php; layout navigasi.
- StudentProfile, Rombel, Subject, LearningOutcome, LearningObjective memakai TenantScoped. StudentRombelManagement juga membatasi operasi mutasi dengan tenant_id, mengunci baris terkait, dan menulis audit log; query render mengikuti global scope fail-closed. Belum ada bukti runtime khusus untuk seluruh skenario ini.
- Ditemukan ketidaksesuaian navigasi Data Akademik & Rombel: link ditampilkan untuk OPERATOR, GURU, KAMAD, sedangkan komponen hanya mengizinkan ADMIN_MADRASAH/ADMIN dengan permission academic.manage. Ini menyebabkan menu terlihat tetapi halaman ditolak untuk beberapa role.
- ExamManagement menampilkan akses untuk ADMIN_MADRASAH/GURU; operasi mutasi tetap dilindungi Gate/policy dan TeacherAccess. Guru dibatasi penugasan mapel/rombel; Admin tidak otomatis mendapat kewenangan menerbitkan ujian.
- QuestionBank memeriksa question.view saat masuk; operasi tulis melalui QuestionPolicy dan penugasan Guru. Hak Admin untuk membuat soal tidak diberikan otomatis.
- ProctorAssignmentManagement membatasi Admin Madrasah + exam.proctor.assign dan memeriksa tenant pada query target/service.
- UserManagement membatasi Admin Madrasah + user.manage; pembuatan akun hanya GURU/SISWA, konsisten dengan pemisahan akun operasional yang tidak boleh dibuat sembarang dari layar ini.
- Hasil pemeriksaan source belum menggantikan pengujian integrasi/multi-tenant runtime.

## DESAIN
- Navigasi harus mengikuti otorisasi aktual komponen. Menyembunyikan link yang pasti ditolak bukan pengganti pemeriksaan server-side.
- Tidak memperluas role/permission hanya demi membuat menu tampak aktif.
- Tidak mengubah skema DB atau menjalankan migration.

## IMPLEMENTASI
- Mempertahankan tiga perbaikan terdahulu pada CurriculumManagement, AcademicManagement search, dan visibilitas menu User Management.
- Perubahan baru: kondisi menu Data Akademik & Rombel kini hanya tampil bagi ADMIN_MADRASAH dengan permission academic.manage.
- Commit terbaru: `58cf081e36b0c018feda8de024e1d010b58e6208`.

## PENGUJIAN
- Pemeriksaan source dan konsistensi kondisi menu vs authorize() dilakukan.
- Belum ada workflow run GitHub Actions yang terdeteksi untuk commit terbaru (fetch_commit_workflow_runs mengembalikan daftar kosong).
- Belum menjalankan PHPUnit, browser test, atau runtime test pada perubahan terbaru. Status: BELUM LULUS UJI.

## TERTUNDA
- Audit mendalam seluruh view/form/tombol dan controller ekspor/hasil, termasuk pagination/filter, empty state, error state, serta permission per aksi.
- Tambah regression tests untuk mismatch menu/authorization dan skenario tenant.
- Jalankan CI dan verifikasi hasil.
- Deployment menunggu review/merge PR dan backup VPS yang disetujui secara eksplisit. Jangan timpa .env, DB, storage privat, atau installer state.

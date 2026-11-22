# CHECKPOINT PENGAMBILALIHAN DAN PENYELESAIAN WEB ADMIN CBT
Tanggal pemeriksaan: 22 November 2026
Repository: `zane0184/cbt_november`
Branch kerja terisolasi: `work/web-admin-completion-20261122`
Baseline source: `work/coordinator-web-admin-audit-20261122`
Baseline HEAD: `dc434ea3beb4838a49194089f546a8c6c799161d`
Parent HEAD: `0f5c92dcd55db3321326d9b59b927c7f410692ec`
Acuan produk: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` (main, blob `68150acf7b9abf130776bee27444c99889ce53a5`) dan Issue #12.

## STATUS
PENGAMBILALIHAN SELESAI — AUDIT LANJUTAN BERJALAN.
WEB ADMIN BELUM ACCEPTED. Belum ada source aplikasi/migration/database/VPS yang diubah dalam checkpoint ini. Branch baru dibuat dari exact HEAD koordinator untuk mengisolasi pekerjaan berikutnya.

## AUDIT

### 1. Kondisi repository dan baseline
- Repository default branch: `main`, HEAD `e6af675ebbd5aabc48d11956c9c5476d41f76234`.
- Tree `main` saat pemeriksaan hanya memuat 33 entri dokumentasi dan tidak memuat `backend-laravel/`. Karena itu `main` bukan baseline source aplikasi untuk implementasi Web Admin saat ini.
- Branch source terlengkap yang dipakai agent sebelumnya: `work/coordinator-web-admin-audit-20261122`, HEAD `dc434ea3beb4838a49194089f546a8c6c799161d`, tree 975 entri.
- Branch koordinator berasal dari Paket 1; perubahan agent sebelumnya tetap dipertahankan dan tidak di-reset.
- Branch baru `work/web-admin-completion-20261122` dibuat dari exact SHA koordinator di atas. Tidak ada merge, rebase, cherry-pick atau perubahan branch `main`.

### 2. Keputusan produk yang mengikat
- Scope sekarang WEB CBT. Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- Tidak ada portal/role Operator, Kamad, atau Proktor permanen. Proktor adalah Guru dengan assignment sementara untuk Exam dan Room.
- Satu madrasah satu instalasi dan database akademik terpisah; tenant_id tetap defense-in-depth.
- Ujian serentak berdasarkan Exam schedule/token/server time; tidak ada ExamSession/SessionRoom/session/wave sebagai lifecycle operasional.
- Guru mengelola soal/asesmen/ujian sesuai penugasan tanpa approval Admin.
- Admin mengelola administrasi, master data, ruang/placement, assignment Proktor, monitoring, dukungan, dan laporan; Admin tidak otomatis mengubah soal/kunci/jawaban Guru.
- Android dan Web Super Admin deferred sampai Juni 2027.
- Issue #12 dan master spec tanggal 22-11-2026 mengesampingkan desain historis yang bertentangan.

### 3. Temuan branch dan pekerjaan agent sebelumnya
| Branch | Exact HEAD yang diperiksa | Temuan |
|---|---|---|
| `work/coordinator-web-admin-audit-20261122` | `dc434ea3` | Baseline kerja koordinator; edit/reset akun ada di source, tetapi HEAD belum memiliki bukti CI yang dapat diamati |
| `work/web-admin-package1-implementation-20261122` | `d7e2034e` | Dasar Paket 1; bukti CI hanya berlaku untuk SHA test masing-masing |
| `work/import-guru-siswa-implementation-20261122` | `f38ceec7` | Memiliki import XLSX, layanan batch, template, preview/confirm dan tes; perlu adopsi terpilih serta pemeriksaan exact-SHA |
| `work/stage4a-monitoring-rbac-20261122` | `32bb7663` | Laporan menyebut 109 test/578 assertions PASS pada SHA `88f1510a`; desainnya menolak Admin monitoring, bertentangan dengan master spec yang memberi Admin fitur monitoring administratif. Tidak boleh digabung apa adanya |
| `work/stage4b-room-session-20261122` | `0f6921ed` | Memuat model operasional ruang/sesi; bertentangan dengan keputusan terbaru tanpa ExamSession/SessionRoom operasional. Jangan adopsi migration/komponen session |
| `work/web-guru-foundation-20261122` | `cf9a6ed8` | Pekerjaan portal Guru terpisah; bukan scope implementasi Web Admin saat ini |
| `work/agent4-web-siswa-audit-20261122` | `69acf087` | Dokumentasi Web Siswa; bukan scope implementasi Web Admin saat ini |

Catatan: hasil CI lama hanya berlaku untuk commit SHA yang benar-benar diuji. Tidak boleh memindahkan label PASS ke HEAD branch terbaru.

### 4. Status modul awal (berdasarkan source yang ditemukan, bukan acceptance)
| Modul | Status | Bukti/gap awal |
|---|---|---|
| Dashboard | PARSIAL | Livewire AdminDashboard ada; angka/aktivitas/peringatan perlu audit query dan test |
| Profil Madrasah | PARSIAL | TenantProfileManagement ada; branding, favicon, kop cetak dan preview belum lengkap |
| Guru/Siswa | PARSIAL | UserManagement create/edit/reset ada; status akun aktif/nonaktif dan lifecycle/history belum lengkap |
| Import/Export | PARSIAL | ImportManagement dan branch import terpisah ada; template, idempotensi, batch, error report, export harus direkonsiliasi pada satu baseline |
| Tahun Pelajaran/Semester | PARSIAL | AcademicManagement dan migration active-period ada; concurrency dua koneksi dan seluruh CRUD perlu diuji |
| Kelas/Rombel/Mutasi | PARSIAL | StudentRombelManagement ada; pindah/naik/tinggal kelas/kelulusan/histori belum lengkap |
| Mata Pelajaran | PARSIAL | Fondasi akademik ada; relasi kurikulum dan histori perlu audit |
| Penugasan Guru | PARSIAL/BLOCKED | Model/service ada; UI Livewire Admin belum terbukti pada baseline koordinator |
| Kurikulum | PARSIAL | CurriculumManagement ada; registrasi, versi, struktur, ATP, mapping KBC/Panca Cinta dan Pembelajaran Mendalam belum lengkap |
| Manajemen Ujian Admin | PARSIAL | Komponen ExamManagement tercampur jalur Guru/Admin; batas akses Admin harus diperketat sesuai spesifikasi |
| Ruang & placement | BELUM TERVERIFIKASI | Tidak boleh mengadopsi session/wave; desain langsung Exam–Room–placement harus diaudit |
| Penugasan Proktor | PARSIAL | Komponen dan service ada; perlu verifikasi Guru target, assignment lifecycle, interval, overlap concurrency, dan scope Room |
| Monitoring Admin | BLOCKED UNTUK ACCEPTANCE | Route/policy dan komponen harus diselaraskan dengan spesifikasi: monitoring administratif tanpa jawaban/kunci |
| Laporan Admin | BELUM TERVERIFIKASI | API laporan Guru ada; laporan administratif Admin, PDF/XLSX, kop, permission dan audit export belum dibuktikan |
| Audit & keamanan | PARSIAL | AuditService dan AuditLog ada; UI log, login/access denied dan cakupan event belum lengkap |
| Pengaturan | BELUM TERVERIFIKASI | Tema, preferensi, zona waktu, branding dan operasional perlu audit |
| Backup/restore & health | BELUM TERVERIFIKASI | Tidak boleh dianggap ada hanya dari dokumentasi deployment |

### 5. Konflik keputusan identitas login yang wajib ditahan
- Dokumen master/Issue #12 menyebut NIP/NUPTK Guru opsional dan melarang mewajibkan NIP untuk semua Guru.
- Lampiran percakapan agent sebelumnya menyatakan ada ketentuan login yang lebih spesifik: Guru ASN login NIP, Guru Honorer login NIK, Siswa login NISN, Admin login username; tetapi percakapan yang dilampirkan tidak memuat pesan keputusan asli pengguna yang menetapkan klasifikasi tersebut.
- Kedua aturan tidak boleh direkonsiliasi dengan tebakan. Implementasi field klasifikasi ASN/Honorer, migrasi data, dan perubahan resolver login ditahan sampai keputusan asli dapat dipastikan. Fitur Web Admin lain yang tidak bergantung pada konflik ini tetap dapat dilanjutkan.
- Source baseline koordinator mengizinkan Guru tanpa NIP/NIK; ini selaras dengan teks master yang saat ini tersedia.

### 6. Risiko/ketidaksesuaian yang harus ditangani
1. `routes/web.php` mengelompokkan sejumlah route Admin dan Guru di grup auth/tenant/license umum. Setiap komponen/policy/action harus fail-closed; route grouping belum cukup sebagai bukti RBAC.
2. `QuestionBank` dan `ExamManagement` masih berada di namespace Admin dan menu lama. Harus dipastikan Admin tidak mendapat akses authoring, kunci, jawaban, score, atau token.
3. ImportManagement dari branch import menggunakan hasRole('ADMIN_MADRASAH') dan permission; harus diuji exact-one role/stale permission, tenant, ownership batch, dan semua action.
4. Source/komponen Proktor harus mengikat assignment sementara ke Guru, Exam, Room, interval dan status; jangan menggunakan role PROKTOR permanen.
5. Stage 4A menolak seluruh monitoring Admin, sedangkan master spec mewajibkan monitoring Admin. Implementasi harus membatasi informasi dan tindakan, bukan menutup fitur Admin sepenuhnya.
6. Stage 4B menambah operational session/wave. Semua source/migration tersebut dikecualikan dari baseline baru sampai desain langsung Exam–Room disahkan.
7. CI branch koordinator belum memberikan bukti test runtime pada HEAD `dc434ea3`; check-runs untuk commit ini kosong. Tidak boleh mengklaim source branch lulus.
8. Workspace lokal rekonstruksi `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan pada sesi ini. Repository GitHub adalah sumber source yang dapat diverifikasi; ZIP baseline dan workspace lain tidak tersedia pada filesystem sesi ini.

## DESAIN / URUTAN PENYELESAIAN
Satu owner integrasi: branch `work/web-admin-completion-20261122`. Tidak ada patch paralel pada file Admin yang sama.

1. P0 — tutup kontrak role/auth/session, lifecycle akun, status aktif/nonaktif, exact-one role, dan tenant boundary. Konflik identitas login ditahan; jangan membuat migration tebakan.
2. P1 — rekonsiliasi import/export Guru/Siswa dari branch import secara file/dependency-level, bukan merge branch.
3. P2 — selesaikan penugasan Guru Mapel–Kelas/Rombel–Tahun/Semester beserta histori.
4. P3 — selesaikan lifecycle akademik Siswa: penempatan, mutasi, naik/tinggal kelas, lulus, arsip.
5. P4 — selesaikan master kurikulum/versioning/registrasi/struktur/CP/TP/ATP dan pemetaan Kurmer–KBC/Panca Cinta–Pembelajaran Mendalam. Jangan mengarang dataset resmi.
6. P5 — administrasi ujian: daftar Exam Guru, Room, placement peserta dan assignment Guru Proktor sementara; tanpa ExamSession/SessionRoom.
7. P6 — monitoring Admin dengan data minimum dan tindakan yang diotorisasi/audit; tidak membuka jawaban/kunci tanpa hak.
8. P7 — laporan administratif, export PDF/XLSX, kop/branding.
9. P8 — audit UI, keamanan, pengaturan, backup/restore/health.
10. P9 — MySQL 8/InnoDB, concurrency, browser E2E, responsive, accessibility, UAT.
11. P10 — paket distribusi satu instalasi madrasah dan runbook aaPanel File Manager; tanpa secret/backup/temp. Deployment produksi hanya setelah preflight, backup, rencana rollback dan persetujuan.

## IMPLEMENTASI PADA CHECKPOINT INI
- Branch `work/web-admin-completion-20261122` dibuat dari exact SHA `dc434ea3beb4838a49194089f546a8c6c799161d`.
- Tidak ada file aplikasi, migration, database, workflow, konfigurasi, VPS atau branch `main` yang diubah.
- Dokumen ini menjadi checkpoint awal agent penyelesaian; semua backup source yang sudah ada tetap dipertahankan.

## PENGUJIAN
- Audit GitHub refs/trees/source/docs/issue #12: dilakukan.
- Test runtime oleh agent ini pada exact HEAD: NOT RUN.
- PHP lint/PHPUnit/MySQL 8/InnoDB/concurrency/browser/E2E/UAT: NOT RUN pada sesi ini.
- Stage 4A CI lama: 109 test / 578 assertions PASS hanya pada SHA `88f1510a7d1992f6cd8d4b727daf93fe28a3afd3`, bukan pada baseline branch baru.
- Tidak ada bukti acceptance Web Admin menyeluruh.

## NEXT ACTION
Mulai audit dan implementasi P0 pada branch ini: periksa seluruh file auth/model/migration/session dan tests, desain account active/inactive tanpa soft-delete, backup tiap file sebelum patch, lalu jalankan CI disposable MySQL 8 pada exact SHA. Setelah P0 teruji, lanjut P1 dan seterusnya.

# Agent 3 — Review Boundary P0 Web Guru

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
HEAD dokumentasi sebelum penambahan: `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`  
Source test: `88b5c1354971bfb7b03d715014b40b9026f1fbed`  
Test blob: `747989ed491dc1ead67b8a990434837df47c8af1`

## AUDIT

### Boundary Agent 3

| Area | Source/blob terakhir yang diaudit | Hasil statis |
|---|---|---|
| QuestionBank | `219efc2db8f492452ad95da35ae22eb4849d1e66` | `boot/mount` memanggil boundary; permission `question.view`; Gate/policy, tenant-scoped query dan assignment pada aksi utama |
| ExamManagement | `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc` | `boot/mount` memanggil boundary; permission `exam.manage`; Gate/policy, tenant-scoped query dan assignment pada aksi utama |
| TeacherPortalAccess | `8c60fb6db17512d6bbf8845027f69841172e2f60` | Memeriksa user persisted/non-soft-deleted, tenant context, tepat satu role GURU dan tepat satu TeacherProfile tenant-lokal |
| TeacherAccess | `766289c5163b6cf367d511c0bf35207ccbc97684` | Assignment tenant-lokal, status ACTIVE dan rentang tanggal berlaku |
| User | `74dd080f703d7ef3256b406176e53dad5786a937` | Tidak ada kolom status/is_active; model tidak menggunakan trait SoftDeletes |
| TeacherProfile | `437ed33366007c26a071a08fb0b8a9e165dbdbc6` | SoftDeletes; tidak ada kolom status aktif |
| Question | `737b7cefa945f448aeea3a236c4015fab539e4fb` | TenantScoped + SoftDeletes |
| Exam | `b679bf6d6a0f68074ef1a000c0345f0248f696f3` | TenantScoped + SoftDeletes |
| TenantScoped | `cf192a8ccb6ca530122cb105ed3086adb09a3c5a` | Fail-closed bila tenant context tidak tersedia |

Jangan menambahkan asumsi `is_active` atau `status` pada User/TeacherProfile. Definisi aktif yang dapat dibuktikan dari schema saat ini adalah User tidak memiliki `deleted_at` dan TeacherProfile tidak soft-deleted; assignment tetap harus ACTIVE dan berada dalam date window.

### Review test 21 method

Test file memiliki 21 method. Enam regresi post-mount yang ada:
1. Permission dicabut setelah mount.
2. Assignment dinonaktifkan setelah mount.
3. TeacherProfile di-soft-delete setelah mount.
4. User di-soft-delete setelah mount.
5. TenantContext diubah setelah mount.
6. Role GURU dicabut setelah mount.

Pemeriksaan statis:
- Test multi-role GURU+SISWA dan ADMIN+GURU ditolak oleh kedua komponen.
- Permission dan assignment yang hilang diuji pada mount awal.
- Foreign Question dan Exam dibuat pada Tenant B, lalu dibuka dari konteks Guru Tenant A.
- Exception ditangkap secara spesifik sebagai `ModelNotFoundException`, bukan `Throwable`; model yang tidak ditemukan harus masing-masing `Question::class` dan `Exam::class`. Bila action kembali normal, `$this->fail()` tidak tertangkap oleh catch tersebut.
- Helper tenant mismatch tetap diuji.
- Enam regresi post-mount tetap ada.

### Kekurangan test yang belum boleh ditutupi

1. Test tenant-context change memanggil `boot()` secara langsung pada instance yang telah di-mount. Ini menguji boundary method fail-closed, tetapi **belum merupakan simulasi penuh request Livewire hydration melalui HTTP middleware dan lifecycle**. Middleware `IdentifyTenant` normalnya membangun ulang context dari authenticated user; test perlu memisahkan pengujian perilaku middleware tersebut dari pengujian perubahan context server-side di antara lifecycle.
2. Test cross-tenant sudah membuat fixture pada Tenant B sebelum beralih ke Tenant A, tetapi belum memiliki assertion eksplisit tambahan yang membuktikan record tersebut tetap tersimpan tepat sebelum akses lintas tenant. Assertion model pada exception sudah lebih sempit daripada catch generik, tetapi bukti fixture dapat diperkuat pada revisi berikutnya.
3. Test method count bukan bukti runtime. Belum ada PHPUnit PASS untuk SHA 88b5.
4. Browser QA desktop/tablet/mobile, keyboard/accessibility dan uji interaksi UI belum dilakukan.

### Backend Agent 2 — blocker lintas boundary

ExamPolicy pada branch Agent 2 yang diperiksa masih memakai `hasRole()` biasa pada banyak method, termasuk `viewAny`, `view`, `preview`, `create`, `update`, `delete`, `manageQuestions`, `manageParticipants`, `generateToken`, `lock`, `close`, dan `publishResults`. `startAttempt` memeriksa keberadaan role SISWA, bukan tepat satu role SISWA. `monitor` mempunyai pemeriksaan exact-role GURU, tetapi jalur KAMAD juga masih perlu diselaraskan dengan keputusan bahwa KAMAD bukan portal/role akses CBT.

Ini ownership Agent 2. Agent 3 tidak mengubah policy/controller/API/service Agent 2. Diperlukan hardening seluruh policy/API/service entry point dan regresi untuk akun multi-role serta pemanggilan service langsung.

## DESAIN

Boundary Web Guru wajib tetap:
- Tepat satu role GURU yang dibaca dari database.
- User persisted dan belum soft-deleted.
- Tenant User dan TenantContext konsisten.
- Tepat satu TeacherProfile tenant-lokal dan belum soft-deleted.
- Permission komponen yang sesuai.
- Assignment mapel aktif dan tanggal berlaku.
- Gate/policy + tenant-scoped resource query.
- Validasi ulang pada boot/hydration/action; tidak mempercayai state browser/Livewire.

Aksi sensitif yang wajib tetap dilindungi: listing, create/edit, publish, delete, participant mapping, token, lock, question selection, validasi, search/pagination, media/file, export dan preview. Kunci jawaban tidak boleh terpapar ke Siswa atau role campuran.

## IMPLEMENTASI

Tidak ada perubahan source pada review ini. Tidak ada file Agent 2/1/4, migration, schema, Composer, DB, atau CBT Engine yang diubah.

Backup sebelumnya: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`, blob `3fb900cb175df29cfb0d3f0d7b908763b1295b26`; 28/28 snapshot dinyatakan MATCH dalam checkpoint terakhir. Manifest ini tidak diperbarui oleh dokumen ini.

## PENGUJIAN

- 21 method: dihitung melalui pembacaan source, bukan dieksekusi.
- Run #41 / `45009039557`: workflow/job failure, steps/log unavailable, PHPUnit execution UNKNOWN.
- Run #39 / `45003191589`: 18 passed, 2 failed, 86 assertions pada SHA `834a59f...`; bukan bukti untuk 88b5.
- Run `44995889398`: SUCCESS pada SHA lama `a338679...`; bukan bukti untuk 88b5.
- Tidak ada browser QA.
- Tidak ada database produksi disentuh.

**Status:** P0 OPEN — CI exact-SHA belum membuktikan PHPUnit; backend exact-role Agent 2 masih menjadi blocker.

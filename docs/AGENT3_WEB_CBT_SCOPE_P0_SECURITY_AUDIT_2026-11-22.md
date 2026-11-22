# Agent 3 — Audit Koreksi Scope Web CBT & P0 Security Gate

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD aktual saat refresh: `4c695181bafa43ad0f02006b8a63e551b8829a03`
Source test: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`
Test blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`
Checkpoint sebelumnya yang dikoreksi: `docs/CHECKPOINT_AGENT3_P0_EXACT_SHA_HTTP_LIVEWIRE_AUDIT_2026-11-22.md`
Instruksi koordinator terbaru: Issue #10 comment `7078921888`
Status: **P0 OPEN — WEB CBT BELUM MEMILIKI BUKTI ACCEPTANCE LENGKAP**

Dokumen ini mengoreksi dan menggantikan rumusan scope yang bertentangan pada laporan/checkpoint Agent 3 sebelumnya. Referensi lama tetap menjadi histori, bukan keputusan aktif.

## IDENTITAS PEKERJAAN

- Agent: 3 — Web Admin Madrasah dan Web Guru, termasuk menu Proktor dalam Web Guru.
- Branch: `work/web-guru-foundation-20261122`.
- HEAD aktual: `4c695181bafa43ad0f02006b8a63e551b8829a03`.
- Target test source: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`.
- Checkpoint aktif baru: `docs/CHECKPOINT_AGENT3_WEB_CBT_P0_SCOPE_2026-11-22.md`.
- Environment: audit GitHub read-only; tidak ada checkout proyek aktif/PHP 8.3/Composer/PHPUnit/MySQL 8 disposable pada environment lokal.

## KEPUTUSAN SCOPE YANG BERLAKU

Berdasarkan instruksi terbaru koordinator Issue #10 comment `7078921888`:

1. Sampai Juni 2027 hanya WEB CBT yang aktif.
2. Android native/Android Studio/API Android dan Web Super Admin ditunda. Keduanya bukan dependency, blocker, atau prasyarat Web CBT.
3. Tiga portal aktif: ADMIN MADRASAH, GURU, SISWA.
4. Tidak ada akun/portal/role OPERATOR; fungsi administrasi ditangani Admin Madrasah.
5. Tidak ada akun/login/portal/role permanen PROKTOR. Proktor adalah assignment sementara untuk akun GURU dan menunya berada di Web Guru.
6. SISWA hanya peserta ujian, bukan identitas Proktor.
7. Tidak ada fungsi produk/akses CBT untuk role lain. Referensi legacy di source hanya dicatat bila memengaruhi authorization atau fungsi Web CBT.
8. Satu ZIP distribusi untuk semua madrasah; setiap madrasah memiliki instalasi dan database akademik terpisah. Tenant ID tetap dipakai sebagai defense-in-depth. Database, credential, konfigurasi, domain, session, cache, queue, storage, log, export, temp dan backup harus terisolasi per instalasi.
9. Kontrak operasional: satu Exam = satu ExamSession kanonis untuk satu pelaksanaan serentak; seluruh Room berada dalam session tersebut. Admission tetap ditentukan Exam schedule/token/eligibility/server time. Attempt aktif yang sah dapat resume sesuai expiry setelah Exam ditutup; Attempt baru ditolak. Device Lease/Fencing belum siap produksi tanpa uji end-to-end dan multi-device.

## AUDIT

### 1. Refresh branch dan source

- Agent 3 HEAD terverifikasi: `4c695181bafa43ad0f02006b8a63e551b8829a03`.
- Test source blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`, 22 test methods secara statis.
- Source test tidak berubah setelah target commit; compare target ke HEAD berisi perubahan dokumentasi.
- Agent 1 HEAD: `69f16da92452fd28a85aec2a8239c5d5ba9a10dd`.
- Agent 2 HEAD: `103e543ae38d4b8646d5ba0405aa4574a22d8ca4`; runtime baseline yang dinyatakan Agent 2 tetap `74bf7178f46673e9be90523671490c0749034acd`. Compare dari checkpoint Agent 2 sebelumnya menunjukkan empat file dokumentasi baru/berubah, tidak ada source runtime berubah.
- Agent 4 dokumentasi HEAD: `05447d93da90063389b822ce2468a459c5bbac11`.
- Kandidat Agent 4 `f38ceec7b0653fcf3363eec40f3d4577c323cca8` belum dikonfirmasi owner sebagai branch resmi.
- Issue #8 OPEN (7 komentar), #9 OPEN (2 komentar), #10 OPEN (16 komentar). Semua komentar Issue #10 yang diperiksa tercatat dari akun `zane0184`; belum ada approval owner teknis terpisah yang dapat diverifikasi.

### 2. Web Guru — route, menu, dan boundary

Source aktual yang diperiksa:

| Path | Blob | Temuan |
|---|---|---|
| `backend-laravel/routes/web.php` | `0cf0c73d28a1f8ab948e7594b6a588c1ca864d6a` | Mengimpor `routes/teacher.php`; halaman QuestionBank/ExamManagement masih URL `/admin/questions` dan `/admin/exams`; halaman assignment Proktor dan Room/Session juga berada di namespace Admin. |
| `backend-laravel/routes/teacher.php` | `ace47ce471f1c87776dd991632f4c98221027a8b` | Group `/guru` memakai auth, tenant, license dan EnsureTeacherPortalAccess. Rute yang ada hanya dashboard, pembelajaran, kurikulum, profil, template import. Belum ada route bank soal, ujian, menu/halaman Proktor atau monitoring Proktor. |
| `backend-laravel/bootstrap/app.php` | `d1137fce9e10bc490a044f950170008d55cab914` | Mendaftarkan route web/api dan alias middleware tenant/license; tidak mendaftarkan custom IdentifyTenant sebagai persistent middleware Livewire. |
| `backend-laravel/app/Support/Security/TeacherPortalAccess.php` | `8c60fb6db17512d6bbf8845027f69841172e2f60` | Memvalidasi user persisted, satu role GURU, satu TeacherProfile tenant-local, dan membangun TenantContext dari authenticated user. |
| `backend-laravel/app/Livewire/Teacher/TeacherPortalComponent.php` | `d1bba0a7d731f4637e3f096b661730c46ce6aae9` | Memanggil TeacherPortalAccess pada boot setiap request Livewire. |
| `backend-laravel/resources/views/layouts/teacher.blade.php` | `c6b11bab9eb5869ad7c0867b8a444a62f78c7a8c` | Bank Soal, Asesmen/Ujian, Monitoring, Penilaian, Analytics masih placeholder disabled. Teks UI menyebut assignment Proktor dikelola Admin dan Guru hanya memperoleh monitoring bila policy/assignment mengizinkan. |
| `backend-laravel/resources/views/livewire/teacher/portal-dashboard.blade.php` | `ba07cdfb30145205ff48c683b7de2512bbcc03bc` | Dashboard masih menyebut agenda/penugasan Proktor akan ditampilkan setelah modul terkait tersedia. |

Temuan: fondasi Portal Guru sudah ada, tetapi alur authoring/Exam dan menu Proktor belum terintegrasi di route/menu Guru. Komponen QuestionBank/ExamManagement saat ini berada di namespace `App\\Livewire\\Admin` dan URL `/admin/*`, walaupun boundary komponen mensyaratkan Guru. Ini ketidaksesuaian arsitektur/routing yang perlu dirapikan setelah kontrak backend dan ownership disepakati.

### 3. Admin Madrasah — exact-role dan permission

Route group admin berada di bawah `auth, tenant, license`; tidak ada middleware exact-role Admin yang terlihat di route group. Karena itu, guard pada setiap Livewire component dan setiap action menjadi batas keamanan penting.

Source yang diperiksa:

| Komponen | Blob | Temuan statis |
|---|---|---|
| AdminDashboard | `b81ce1ea19a50db07ec439b15bec0a034f340afc` | Memakai `hasRole('ADMIN_MADRASAH')`, belum exact-one. |
| UserManagement | `9f2287a677586cca0fd829b3b2c87c2a05200c88` | Memakai `hasRole('ADMIN_MADRASAH')`, belum exact-one; daftar role yang dapat dibuat dibatasi GURU/SISWA. |
| AcademicManagement | `3edf4ce374328d957f4320e32a92882ffdcd302c` | Query memastikan role ADMIN_MADRASAH exists, bukan satu-satunya role. |
| CurriculumManagement | `e3422650ea71709cba5ae07cd2b887cf812166a8` | Guard manage masih menerima role literal legacy `ADMIN`; guard awal `curriculum.view` hanya memeriksa permission. Ini jalur legacy yang berdampak langsung pada authorization dan harus diselaraskan dengan tiga portal aktif. |
| TenantProfileManagement | `da65d6b9d8536c31c85f3dd2574df65a6be93fb6` | Memakai `hasRole('ADMIN_MADRASAH')`, belum exact-one. |
| ProctorAssignmentManagement | `9886feb0475281fc40797caf9b12926edf3b5862` | Admin guard memakai `hasRole`; pilihan calon Proktor memakai `whereHas(role=GURU)` sehingga UI belum menyaring exact-one GURU. Service backend tetap harus menjadi enforcement final. |
| ExamRoomSessionManagement | `223e3100097723f244e0e0bf679278c9275ee88a` | Admin guard memakai `hasRole`, belum exact-one. |

Route `/operator/dashboard` masih ada sebagai URL compatibility yang hanya redirect ke `/admin/dashboard`; audit perlu memastikan ia tidak memberi capability atau mempertahankan akses identitas legacy. Jangan membangun portal/fitur Operator.

### 4. Policy dan kontrak exact-role

- Agent 2 `ExamPolicy.php` blob `c05d96ef932fdc81b67336240af4e1726864c3d6` masih memiliki 38 pemanggilan `hasRole()`. Source memuat predicate legacy non-portal yang memberi akses view, serta `startAttempt()` memeriksa keberadaan SISWA, bukan tepat satu role SISWA. Ini temuan authorization yang nyata, bukan kebutuhan fitur aktor legacy.
- Agent 2 `QuestionPolicy.php` blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e` memakai helper privat `hasExactGuruPermission()`, yang membaca role dan permission dari database serta mensyaratkan tepat satu role GURU.
- Agent 3 `QuestionPolicy.php` blob `81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566` masih memakai `hasRole('GURU')` dan permission relation biasa. Perbedaan ini harus direkonsiliasi dengan Agent 2; Agent 3 tidak menyalin atau mengubah source Agent 2 secara sepihak.
- Model User blob `c231e60c5a25597b4528b4d0adaac7580b70f8d3` memiliki relasi many-to-many roles, `hasRole()` memakai relasi yang dapat tersimpan di memory, dan `hasPermission()` menggabungkan permission dari semua role. Karena itu exact-one harus didefinisikan sebagai kontrak bersama yang membaca state persisted dan fail-closed terhadap multi-role/role revoke.
- Agent 2 Stage 4E audit report blob `ed5810366dfdf9b4ea16e7d3882c516649833e6e` dan checkpoint blob `975a67877806cc744f79635a751a3f3730ba5463` adalah dokumentasi read-only. Agent 2 menyatakan exact-role, host/install binding, isolasi dua instalasi, dan test runtime belum selesai. Tidak ada owner approval terpisah yang dapat diverifikasi.

### 5. Monitoring dan Proktor

- UI assignment Proktor yang ditemukan berada di Admin, bukan menu Guru.
- Komponen assignment Admin menampilkan Proktor yang dipilih dari user dengan role GURU, tetapi query UI hanya `whereHas(role=GURU)`; exact-one harus ditegakkan oleh service/backend.
- UI Guru saat ini menampilkan placeholder monitoring dan menyatakan assignment Proktor dikelola Admin. Belum ada route/halaman monitoring Proktor pada `routes/teacher.php`.
- Route monitoring lama `/exams/{exam}/monitoring` hanya mengandung parameter Exam pada URL dan memanggil Gate `monitor`; route-level ini tidak membuktikan room scope. Scope data aktual seluruh dashboard/participant/attempt/event/media/export harus diverifikasi di backend Agent 1/2 sebelum UI Proktor dihubungkan.
- Proktor hanya boleh mengakses ExamSession kanonis, SessionRoom/Room dan peserta yang tercakup assignment aktif. Tidak otomatis memperoleh jawaban, nilai, bank soal, konfigurasi Exam, Force Submit, Room lain, atau assignment lain.
- Kontrak ExamSession/Room/placement/assignment/recovery lintas Agent 1/2/4 masih PENDING. Agent 3 belum menerima approval owner yang dapat diverifikasi.

### 6. HTTP Livewire, tenant/session, CSRF, replay dan integritas data

Test blob `4f1bb48...` berisi 22 test methods. HTTP POST Livewire terbaru hanya membuktikan component boundary memulihkan TenantContext dari authenticated user dan menolak stale tenant reassignment pada dua komponen. Enam regresi post-mount lain masih memakai Livewire test harness atau direct boot.

Belum terbukti:
- POST update memakai middleware persistent yang disepakati untuk semua portal;
- permission/role/assignment/profile/user revocation via HTTP update baru;
- CSRF pada environment non-test;
- snapshot replay/tampering/checksum mismatch;
- session/cookie/token lintas instalasi dan perpindahan akun;
- integritas seluruh tabel relevan setelah denial, termasuk placement, Attempt, Answer, Score, assignment dan audit log;
- cache/queue/job effects atau browser multi-session.

`IdentifyTenant` tidak terdaftar sebagai persistent middleware Livewire pada AppServiceProvider. `TeacherPortalComponent::boot()` dan `TeacherPortalAccess` menjadi component boundary Guru. Untuk Admin, pola equivalent dan exact-role guard pada setiap component/action perlu diverifikasi.

## DESAIN

### Kontrak authorization/exact-role

- Hanya tiga portal/role aktif: ADMIN_MADRASAH, GURU, SISWA.
- Satu identitas login harus tepat satu role yang sah; mixed role, role kosong/tidak dikenal, role revoke dan permission stale fail-closed.
- Proktor bukan role: akun tepat satu GURU + assignment aktif/confirmed + expiry/revocation + tenant/installation + Exam/ExamSession/Room/SessionRoom/placement.
- Agent 2 diminta mengonfirmasi satu kontrak helper/layanan exact-role yang authoritative untuk seluruh portal/policy/Livewire. Belum ada approval owner teknis terpisah: **PENDING**.
- Agent 3 hanya mengubah file ownership Agent 3 setelah kontrak bersama diterima. Tidak ada perubahan source Agent 2.

### Kontrak Livewire

- Verifikasi request GET dan POST update sebagai request terpisah.
- Authenticated user, session version, tenant/installation identity dan role persisted harus diperiksa pada POST.
- Jangan menganggap route middleware GET otomatis dijalankan kembali pada POST.
- Uji revocation sesudah mount memakai HTTP POST update baru dengan session yang sama dan snapshot valid terbaru.
- Uji replay snapshot ke session/user/installation berbeda, tampering payload, checksum/signature rejection, CSRF dan expired session.
- Ambil snapshot database dari seluruh tabel domain relevan dan audit log sebelum/sesudah denial; dokumentasikan tabel yang tidak dicakup.

### Kontrak Admin/Guru/Proktor UI

- Admin Madrasah: master data, administrasi, sinkronisasi, monitoring administratif dan dukungan teknis sesuai permission.
- Guru: bank soal, membuat/mengatur/menerbitkan ujian sesuai penugasan tanpa approval Admin.
- Menu Proktor berada dalam Web Guru dan tampil hanya jika assignment sah/aktif atau tampil sebagai status aman tanpa capability.
- Halaman Proktor harus room-scoped di semua list/detail/monitoring/event/export/media. Tidak ada Proktor login/portal/role baru.
- Admin tetap mengelola master Room/Session/placement/assignment sesuai kontrak backend. UI tidak boleh menjadi sumber otorisasi.

### Kontrak lintas-agent

| Agent | Kontrak | Status |
|---|---|---|
| Agent 1 | ExamSession kanonis, Room/SessionRoom, placement, Proktor assignment lifecycle, scheduler, recovery dan monitoring room scope | PENDING owner approval |
| Agent 2 | Exact-role, policy, lifecycle Exam/Attempt, DB integrity, lock order, host/install/session isolation | PENDING owner approval |
| Agent 4 | Web Siswa admission, Attempt, Answer, submit/resume/recovery dan scope peserta | PENDING owner approval |
| Agent 3 | Web Admin/Guru, Livewire boundaries, menu Proktor dan consumer UI room-scoped | Audit selesai sebagian; implementasi UI Proktor menunggu kontrak |

Issue #8/#9 import XLSX/DOCX tidak menjadi dependency langsung untuk menutup HTTP Livewire P0; tetap jangan menghubungkan UI import tanpa kontrak DTO/API dan approval Agent 2.

## IMPLEMENTASI

- Tidak ada source runtime, test, route, policy, middleware, controller, service, migration, schema, dependency, workflow, konfigurasi, database, branch Agent lain, merge/rebase/cherry-pick, PR atau deployment yang diubah dalam audit koreksi ini.
- Dokumen audit dan checkpoint baru ditambahkan sebagai file baru; tidak ada file existing ditimpa sehingga tidak perlu backup overwrite.
- Source test tetap blob `4f1bb48b390b03b2602df6348fd3b9788581e6c9`.
- Backup pre-patch test tetap blob `0b273618677c791d82645fa373be57aa2d961643`, byte-exact dengan source pre-patch yang sudah diverifikasi.
- Tidak ada akses atau perubahan database produksi.

## PENGUJIAN

| Tes | Status | Bukti/batas |
|---|---|---|
| Refresh Agent 3 HEAD dan source blob | PASS — read-only | HEAD `4c695181...`, test blob `4f1bb48...` |
| Refresh Agent 1/2/4 refs | PASS — read-only | SHA tercatat pada bagian Audit |
| Issue #8/#9/#10 | PASS — read-only | 7/2/16 komentar; owner response terpisah belum terverifikasi |
| Source route/layout/component/policy inspection | PASS — statis | Blob SHA dicatat di atas |
| Exact-role runtime | NOT RUN | Belum ada PHP/PHPUnit execution |
| PHP lint PHP 8.3 | NOT RUN | Environment PHP target tidak tersedia |
| Route registration | NOT RUN | `route:list` tidak dijalankan |
| PHPUnit | NOT RUN | Tidak ada checkout/vendor/PHPUnit di environment |
| HTTP Livewire GET/POST | PARTIAL — statis | Source test membentuk POST; PHPUnit belum dijalankan |
| Permission/role/assignment/profile/user revocation via HTTP | NOT RUN | Belum semua diuji melalui POST baru |
| Tenant/session isolation dua instalasi | NOT RUN | Belum ada environment A/B |
| CSRF | NOT RUN | Belum diuji pada environment non-test |
| Snapshot replay/tampering | NOT RUN | Belum ada negative test |
| Database side effects | PARTIAL / NOT RUN | Snapshot lama hanya lima tabel dan belum dieksekusi |
| MySQL 8/InnoDB | NOT RUN | Disposable DB tidak tersedia |
| CI exact-SHA | UNKNOWN | Tidak ada bukti run/log/steps exact target |
| Browser QA non-production | BLOCKED | Menunggu PHP/PHPUnit/MySQL dan kontrak backend |
| Database produksi | NOT TOUCHED | Tidak ada koneksi/operasi produksi |

## STATUS P0

**OPEN.**

Acceptance gate yang sudah terbukti:
- Branch/source/checkpoint refresh read-only.
- Backup pre-patch byte-exact.
- Source HTTP Livewire POST test ada secara statis.
- TeacherPortalAccess memeriksa persisted user, exact-one GURU dan satu TeacherProfile tenant-local pada component boundary.

Acceptance gate yang belum terbukti:
- PHP lint, route registration dan PHPUnit exact-SHA.
- HTTP revocation matrix penuh.
- Exact-role untuk seluruh portal/policy/route/action.
- Session/installation isolation, CSRF, snapshot replay/tampering.
- Database side-effect coverage lengkap.
- Proktor menu/route Web Guru dan room-scoped monitoring.
- MySQL 8/InnoDB, CI exact-SHA, browser QA dan UAT.

Tidak ada klaim Web CBT siap operasional/produksi.

## LANGKAH BERIKUTNYA

1. Agent 2 — berikan review teknis langsung dan status APPROVED/REJECTED/NEEDS DESIGN untuk helper/kontrak exact-role dan konsistensi QuestionPolicy/ExamPolicy/Admin authorization. Source owner: Agent 2. Dependency: belum ada approval.
2. Agent 1 — berikan kontrak ExamSession kanonis, Room/SessionRoom, placement, assignment Proktor, expiry/revocation, scheduler dan room-scoped monitoring. Dependency UI Proktor: pending.
3. Agent 4 — konfirmasi contract peserta/Attempt/Answer/submit/resume dan API yang akan dipakai untuk menjaga scope participant.
4. Agent 3 — setelah kontrak disetujui, rapikan route/menu QuestionBank/ExamManagement ke Web Guru, buat menu/halaman Proktor dalam Web Guru, dan pastikan Admin component guards exact-role. Jangan mengubah file Agent 2.
5. Agent 3 — tambah HTTP POST Livewire tests untuk seluruh revocation, session identity, replay/tampering, CSRF dan data-integrity snapshot.
6. Siapkan environment PHP 8.3 + lockfile + MySQL 8/InnoDB disposable, lalu jalankan tes exact-SHA.
7. Browser QA non-production setelah seluruh prasyarat backend lulus.

Tidak ada pekerjaan Android atau Web Super Admin. Tidak ada migration/deployment produksi. P0 tetap OPEN sampai bukti runtime, CI dan acceptance Web CBT lengkap.

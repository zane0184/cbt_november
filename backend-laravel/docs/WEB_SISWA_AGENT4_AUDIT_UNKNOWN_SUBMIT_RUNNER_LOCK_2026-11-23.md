# Audit Web Siswa Agent 4 — runner lock saat submit outcome unknown — 23 November 2026

## AUDIT
- File diperiksa: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Temuan: saat submit request telah dikirim tetapi hasilnya tidak pasti, `state.submitOutcomeUnknown` menjadi true dan tombol submit dinonaktifkan. Namun `runner-panel` tetap interaktif; input soal, navigasi, media controls, dan elemen lain tidak dikunci.
- Jika siswa mengubah jawaban selama status submit belum diketahui, draft baru dapat terbentuk ketika server mungkin telah menerima Attempt sebagai `SUBMITTED` atau `EXPIRED`. Draft tersebut tidak boleh tampak seolah bagian dari jawaban yang telah dikirim.
- Jalur pemulihan startup juga dapat mendeteksi marker `SUBMITTING/UNKNOWN` dari IndexedDB sebelum membuka Attempt. Runner harus terkunci sejak pemeriksaan status dimulai, termasuk saat panel baru ditampilkan setelah GET execution.
- Status `IN_PROGRESS` hanya boleh membuka interaksi setelah GET Attempt, GET execution dengan Attempt ID yang sama, dan `loadExecution()` berhasil. Kegagalan recovery harus tetap fail-closed.

## DESAIN
- Tambahkan helper client-only `setRunnerLocked(locked)`.
- Gunakan properti DOM `inert` pada seluruh `runner-panel` agar kontrol soal/navigasi tidak dapat difokuskan atau dioperasikan selama status tidak pasti.
- Set `aria-busy` mengikuti status lock untuk memberi indikasi aksesibilitas.
- Kunci runner saat:
  1. startup menemukan marker submit yang belum definitif;
  2. pemeriksaan status dimulai;
  3. submit POST sudah dikirim dan respons tidak dapat dipastikan.
- Buka runner hanya setelah status server `IN_PROGRESS`, execution cocok dengan Attempt yang sama, `loadExecution()` sukses, dan status recovery tersimpan sebagai `RESUMED`.
- Status terminal `SUBMITTED/EXPIRED` tetap menampilkan completion; status lain atau kegagalan jaringan tetap terkunci.
- Tidak mengubah API, backend, schema, database, aturan waktu, jawaban, atau Web Guru/Admin.

## IMPLEMENTASI
- Backup pra-patch:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-unknown-submit-runner-lock-2026-11-23`
- Blob backup: `c6ad92640e9358e33a9b4980c022e374dedf9c10`; byte-exact diverifikasi terhadap source pra-patch.
- Final source file: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Final runner blob: `defb1f7717b53a9115ea80d67b758986e78d8dbe`.
- Final patch commit: `3ebf87cce024e61112c5374a3344479f229ba790`.
- Jejak koreksi: satu intermediate commit `755930d0492d9251a108cbff1d6b696d95e57b3c` terbukti berisi hasil penggantian teks yang malformed akibat mismatch nama properti pada script patch. Kesalahan terdeteksi melalui fetch ulang sebelum pengujian; blob intermediate disimpan sebagai forensic backup `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.corrupted-intermediate-before-restore-2026-11-23`, lalu runner dipulihkan byte-exact dari backup pra-patch pada commit `6959913af2d41984efe37c22d04330f445a42718`. Patch final kemudian diaplikasikan ulang dan diverifikasi terhadap expected source hasil transformasi. Net source pada HEAD adalah source benar; riwayat commit tetap menyimpan koreksi tersebut.
- Chromium headless tersedia sebagai binary, tetapi percobaan harness IndexedDB terisolasi timeout saat startup browser. Tidak ada hasil browser runtime yang diklaim.

## PENGUJIAN
- JavaScript inline runner dikompilasi dengan `new Function()`: PASS.
- Final file dibandingkan dengan backup pra-patch setelah menerapkan tepat lima perubahan yang direncanakan: exact match PASS.
- Simulasi fungsi recovery dengan stub:
  - `IN_PROGRESS` + execution cocok + load sukses: runner terkunci saat pemeriksaan dan execution; terbuka setelah recovery; submit aktif; status-check disembunyikan — PASS.
  - `IN_PROGRESS` + loadExecution gagal: runner tetap terkunci; status unknown dan tombol pemeriksaan tetap tersedia — PASS.
  - `SUBMITTED`: runner tetap terkunci dan completion terminal tampil — PASS.
  - `EXPIRED`: runner tetap terkunci dan completion terminal tampil — PASS.
  - status nonterminal tak dikenal: runner tetap terkunci dan error tampil — PASS.
- Static checks: helper dipanggil pada startup-unknown, awal recover, submit unknown; unlock hanya pada recovery IN_PROGRESS sukses — PASS.
- Browser E2E, accessibility testing pada browser nyata, IndexedDB runtime, PHP lint/PHPUnit, API integration, MySQL concurrency, multi-device/fencing, dan UAT: BELUM DIUJI.

## STATUS
- Runner kini menerapkan fail-closed interaction lock selama submit outcome tidak pasti.
- Ini bukan bukti kesiapan operasional/produksi.
- PR #13 tetap Draft/unmerged; tidak ada merge, deployment, migration, atau database mutation.
- Scope hanya Web Siswa. Agent 2 tetap dependency terpisah untuk dual-login/auth-session binding dan enforcement.

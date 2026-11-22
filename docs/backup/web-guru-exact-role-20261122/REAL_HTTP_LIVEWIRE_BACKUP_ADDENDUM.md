# Backup Addendum — Real HTTP Livewire Lifecycle Test

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`

## Backup byte-exact sebelum patch

- Source: `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Source blob SHA sebelum perubahan: `0b273618677c791d82645fa373be57aa2d961643`
- Backup: `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-real-http-livewire-20261122`
- Backup blob SHA: `0b273618677c791d82645fa373be57aa2d961643`
- Hasil: **MATCH — blob SHA identik**
- HEAD sebelum backup: `6db04429613537c8219e6d8d873332d25f8c1ced`
- Commit backup: `45f2048c085bafae5247109d16de190648a71491`

## Alasan

Test sebelumnya menjalankan GET halaman asli, tetapi update komponen dilakukan melalui `Livewire::test()`. Pada Livewire 3.8.11, test harness menonaktifkan middleware saat initial dan subsequent request. Karena itu, test tersebut belum membuktikan request HTTP ke endpoint update Livewire menjalankan middleware/pipeline produksi.

Patch berikutnya dibatasi pada file test Agent 3. Tidak mengubah middleware, component, policy, controller, service, route, schema, migration, Composer, workflow, atau file milik Agent lain.

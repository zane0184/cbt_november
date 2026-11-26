# Implementasi Terarah CP Admin — Canonical Registry Route
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch implementasi: `work/cp-admin-canonical-route-20261126`
Source branch dasar: `work/rombel-docx-completion-20261126`
Commit implementasi route: `f711f176be3d0d69ba3740e67228eb0469b426ee`

## AUDIT
- Source aktual pada branch dasar masih mengarahkan `GET /admin/curriculum` ke `CurriculumManagement`, yang membaca model legacy `LearningOutcome/LearningObjective`.
- Halaman canonical registry sudah tersedia di `GET /admin/curriculum/registry` melalui `CurriculumRegistryManagement`.
- Komponen legacy memiliki guard penolakan pembuatan TP oleh Admin. Otorisasi tersebut tidak diubah.
- Pemeriksaan file route sebelum patch menunjukkan kedua route terdaftar terpisah; entrypoint utama masih mengarah ke halaman legacy.
- Backup source dibuat sebelum perubahan:
  `backend-laravel/backups/cp-admin-canonical-route-20261126/web.php.before`
  Blob SHA source sebelum patch: `07ace9376aa18865023aef585e4bed7728332259`.

## DESAIN
- Pertahankan URL lama `/admin/curriculum` sebagai entrypoint kompatibilitas.
- Alihkan entrypoint tersebut ke canonical registry `/admin/curriculum/registry`.
- Pertahankan route registry dan komponen registry yang sudah ada.
- Jangan menghapus file komponen legacy pada patch ini; route tidak lagi menggunakannya, tetapi file tetap tersedia untuk audit/rollback.
- Tidak mengubah permission, model, schema, migration, seeder, CP data, TP/ATP service, atau database.

## IMPLEMENTASI
- File diubah: `backend-laravel/routes/web.php`.
- Menghapus import `CurriculumManagement` yang tidak lagi dipakai oleh route.
- Mengganti route lama dengan:
  `Route::redirect('/admin/curriculum', '/admin/curriculum/registry')->name('admin.curriculum');`
- Route canonical tetap:
  `Route::get('/admin/curriculum/registry', CurriculumRegistryManagement::class)->name('admin.curriculum.registry');`
- Backup: `backend-laravel/backups/cp-admin-canonical-route-20261126/web.php.before`.
- Commit implementasi: `f711f176be3d0d69ba3740e67228eb0469b426ee`.

## PENGUJIAN
- Pemeriksaan pasca-update melalui GitHub fetch pada branch implementasi mengonfirmasi:
  - redirect `/admin/curriculum` → `/admin/curriculum/registry` ada;
  - import/route `CurriculumManagement` sudah tidak ada pada file route;
  - route canonical registry tetap ada.
- Workflow runs untuk commit implementasi belum mengembalikan run terkait pada saat pemeriksaan.
- PHPUnit, route:list, browser/Livewire, dan acceptance test belum dijalankan. Karena itu perubahan belum dinyatakan runtime-tested atau lulus CI.
- Tidak ada database, migration, VPS, atau deployment yang disentuh.

## BATAS DAN PEKERJAAN LANJUT
1. Jalankan CI dan route/browser test pada branch ini; verifikasi tidak ada konflik nama route `admin.curriculum`.
2. Audit registry read-only untuk menampilkan status sumber CP, record CP yang tersedia, dan TP/ATP Guru tanpa membuka hak tulis Admin.
3. Selesaikan QA visual per halaman dokumen 9941/2025 dan proofread Bahasa Arab/harakat sebelum dataset final.
4. Buat dataset terstruktur dan validasi schema; status tetap staging sampai QA/provenance memadai.
5. Implementasikan importer dry-run dan acceptance MySQL 8 disposable hanya setelah dataset dinyatakan layak.
6. Jangan import ke database produksi dan jangan membuat CP sintetis.

**Status:** perubahan route tersimpan di branch kerja terpisah; pemeriksaan statis berbasis isi GitHub PASS; runtime/CI PENDING; CP official import dan E2E masih BLOCKED.

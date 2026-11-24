# Matriks Acceptance ADM-03 — Tenant Branding Assets
Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source SHA yang diuji: `41038aa303ed728c777a32b3254d2ec672ff1860`  
CI: [Tenant Backend CI #45438946455](https://github.com/zane0184/cbt_november/actions/runs/45438946455)

> ID ADM03-01 s.d. ADM03-41 di bawah adalah ID pelacakan yang ditetapkan dalam matriks ini agar 41 acceptance dapat diaudit satu per satu. ID tersebut bukan ID bawaan Laravel atau database.

| ID | Acceptance / skenario | Bukti test utama | Status |
|---|---|---|---|
| ADM03-01 | Authenticated User A + Context A cocok dengan instalasi A | `test_admin_can_upload_tenant_isolated_logo_document_logo_and_square_favicon`; `test_authenticated_branding_user_must_match_local_installation_identity` | PASS |
| ADM03-02 | User B + Context B pada shared database legacy | `test_legacy_shared_database_tenant_context_switch_remains_scoped_for_compatibility` | PASS — LEGACY REGRESSION ONLY; bukan bukti isolasi antar-instalasi |
| ADM03-03 | User A + Context B ditolak | `test_legacy_shared_database_tenant_context_switch_remains_scoped_for_compatibility` | PASS |
| ADM03-04 | User B + Context A ditolak | `test_legacy_shared_database_tenant_context_switch_remains_scoped_for_compatibility`; `test_authenticated_branding_user_must_match_local_installation_identity` | PASS |
| ADM03-05 | Context kosong untuk authenticated user ditolak; production tanpa identity fail-closed | `test_branding_local_path_rejects_traversal_absolute_backslash_and_missing_paths`; `test_production_branding_resolution_fails_closed_without_a_valid_local_installation_identity` | PASS |
| ADM03-06 | Guard aktif menjadi sumber user; perubahan guard tidak memakai user guard lama | `test_legacy_shared_database_tenant_context_switch_remains_scoped_for_compatibility`; `test_authenticated_branding_user_must_match_local_installation_identity` | PASS |
| ADM03-07 | Resolver/request stale tidak mengalahkan active guard dan TenantContext | `test_authenticated_branding_user_must_match_local_installation_identity` | PASS |
| ADM03-08 | Service dipanggil langsung tanpa Livewire tetap memvalidasi konteks dan ownership | Pemanggilan langsung `TenantBrandingService` pada test upload, `localPath`, `deleteManaged`, dan PDF | PASS |
| ADM03-09 | Jalur non-request yang didukung hanya memakai local installation identity | `test_branding_resolution_isolated_between_two_disposable_installations_with_separate_databases_and_storage_roots` | PASS |
| ADM03-10 | Managed path normal dapat di-resolve hanya dalam root instalasi yang benar | `test_branding_local_path_rejects_traversal_absolute_backslash_and_missing_paths`; dua-installation test | PASS |
| ADM03-11 | Path traversal `../` ditolak | `test_branding_local_path_rejects_traversal_absolute_backslash_and_missing_paths` | PASS |
| ADM03-12 | Absolute path ditolak | Test yang sama | PASS |
| ADM03-13 | Backslash path ditolak | Test yang sama | PASS |
| ADM03-14 | Path kosong, null, atau file tidak tersedia ditolak | Test yang sama | PASS |
| ADM03-15 | Internal symlink hanya dapat di-resolve dalam managed branding root; delete hanya menghapus entri link | Test localPath/symlink | PASS |
| ADM03-16 | External symlink ditolak | Test localPath/symlink | PASS |
| ADM03-17 | Symlink lintas tenant legacy ditolak | Test localPath/symlink | PASS |
| ADM03-18 | Symlink dari storage instalasi B ke file instalasi A ditolak; delete tidak menghapus target A | `test_branding_resolution_isolated_between_two_disposable_installations_with_separate_databases_and_storage_roots` | PASS |
| ADM03-19 | Broken/dangling symlink ditolak dan entri link dapat dihapus tanpa target | Test localPath/symlink | PASS |
| ADM03-20 | Direktori `branding` yang merupakan symlink ditolak untuk read/upload/delete | Test localPath/symlink | PASS |
| ADM03-21 | Public storage root/target di luar instalasi ditolak; penghapusan symlink tidak mengikuti target | Test localPath/symlink | PASS |
| ADM03-22 | Upload logo aplikasi valid | `test_admin_can_upload_tenant_isolated_logo_document_logo_and_square_favicon`; `test_admin_can_replace_and_remove_app_logo_through_branding_management` | PASS |
| ADM03-23 | Upload logo kop dokumen valid | `test_admin_can_upload_tenant_isolated_logo_document_logo_and_square_favicon`; `test_admin_can_replace_and_remove_document_logo_and_favicon` | PASS |
| ADM03-24 | Upload favicon valid | `test_admin_can_upload_tenant_isolated_logo_document_logo_and_square_favicon` | PASS |
| ADM03-25 | Ekstensi tidak cocok dengan konten gambar ditolak | `test_branding_service_rejects_mismatched_file_extension_and_image_type` | PASS |
| ADM03-26 | MIME spoof / client MIME tidak dipercaya; konten aktual diperiksa | `test_branding_service_rejects_mismatched_file_extension_and_image_type`; `test_branding_service_rejects_invalid_image_content_and_oversized_uploads` | PASS |
| ADM03-27 | Konten bukan gambar atau gambar rusak ditolak | `test_branding_service_rejects_invalid_image_content_and_oversized_uploads`; PDF fallback tests | PASS |
| ADM03-28 | Ukuran upload berlebih ditolak | `test_branding_service_rejects_invalid_image_content_and_oversized_uploads` | PASS |
| ADM03-29 | Dimensi favicon tidak valid ditolak | `test_favicon_validation_rejects_non_square_image` | PASS |
| ADM03-30 | Replace/delete app logo, document logo, dan favicon berjalan pada scope instalasi sendiri | `test_admin_can_replace_and_remove_app_logo_through_branding_management`; `test_admin_can_replace_and_remove_document_logo_and_favicon` | PASS |
| ADM03-31 | Aset lama yang tidak lagi direferensikan dapat dihapus | `test_admin_can_remove_only_managed_asset_path`; replace/delete tests | PASS |
| ADM03-32 | Aset yang masih direferensikan tidak ikut terhapus | `test_replacing_app_logo_keeps_old_file_when_document_logo_still_references_it` | PASS |
| ADM03-33 | PDF memakai document logo jika tersedia dan valid | `test_pdf_report_header_prefers_dedicated_document_logo` | PASS |
| ADM03-34 | PDF fallback ke app logo ketika document logo hilang atau rusak | `test_pdf_header_falls_back_when_document_logo_is_missing_or_corrupt_and_returns_null_without_app_logo` (service fallback + rendered Blade header assertions); invalid-logo fallback test | PASS |
| ADM03-35 | PDF fallback ke app logo ketika document logo ada tetapi tidak dapat dibaca | `test_pdf_report_header_falls_back_to_app_logo_when_document_logo_is_unreadable` | PASS |
| ADM03-36 | PDF menggunakan text fallback ketika semua logo tidak tersedia | `test_pdf_header_falls_back_when_document_logo_is_missing_or_corrupt_and_returns_null_without_app_logo` | PASS |
| ADM03-37 | Favicon PNG: preview, MIME konten, dan HTML link type | `test_favicon_png_and_webp_preview_use_content_mime_and_fallback_when_unavailable` | PARTIAL — konten dan HTML terbukti; HTTP Content-Type dari Nginx staging belum diuji |
| ADM03-38 | Favicon WebP: preview, MIME konten, dan HTML link type | Test yang sama | PARTIAL — konten dan HTML terbukti; HTTP Content-Type dari Nginx staging belum diuji |
| ADM03-39 | Fallback favicon ketika aset hilang atau rusak | `test_favicon_png_and_webp_preview_use_content_mime_and_fallback_when_unavailable`; `public/favicon.svg` | PASS |
| ADM03-40 | Migration matrix enam kondisi skema; up idempotent; indeks non-unik; data/indeks lama dipertahankan; down fail-closed | `test_branding_migration_up_is_idempotent_and_repairs_legacy_partial_schema`; `test_branding_migration_rollback_is_fail_closed_and_preserves_columns` | PASS |
| ADM03-41 | Dua instalasi dengan DB dan storage terpisah: metadata, read/upload/replace/delete, tanpa operasi silang; regression login/dashboard/profile/PDF | `test_branding_resolution_isolated_between_two_disposable_installations_with_separate_databases_and_storage_roots`; `WebAdminAuthTest`; `RoleDashboardVerificationTest`; `TenantProfileManagementTest`; PDF tests | PASS |

## Ringkasan

- PASS: 38 acceptance.
- PASS — LEGACY REGRESSION ONLY: 1 acceptance.
- PARTIAL: 2 acceptance (ADM03-37 dan ADM03-38).
- FAIL: 0.
- BLOCKED/NOT TESTED: pemeriksaan HTTP response header aktual pada Nginx/aaPanel staging.

ADM-03 **belum ACCEPTED** karena dua acceptance favicon baru membuktikan MIME berdasarkan konten dan deklarasi HTML `type`, belum membuktikan header HTTP yang benar dari Nginx pada storage URL. Pengujian tersebut harus dilakukan pada staging disposable; tidak boleh menggunakan VPS/database produksi.

## Arsitektur instalasi

- Acceptance antar-madrasah memakai dua instalasi disposable dengan database dan storage terpisah.
- Shared database dua tenant hanya dipertahankan sebagai regression test legacy.
- `tenant_id`, `TenantScoped`, `TenantContext`, dan mekanisme tenancy tidak dihapus massal; identitas lokal menjadi otoritas instalasi.
- Central Super Admin, payload lisensi, dan larangan akses data akademik oleh pusat merupakan acceptance arsitektur terpisah dan belum dinyatakan selesai oleh matriks ADM-03 ini.

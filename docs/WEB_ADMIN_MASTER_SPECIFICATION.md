# SPESIFIKASI INDUK WEB ADMIN CBT MADRASAH

**Versi:** 1.0-draft  
**Tanggal:** 21 November 2026 (WIB)  
**Status:** Entry point normatif dokumentasi Web Admin; belum merupakan bukti implementasi atau persetujuan penutupan W0/W1/W6.  
**Repository:** `zane0184/cbt_november`  
**Basis branch dokumen:** `docs/web-admin-master-spec-20261121`  
**Base commit:** `2b4321ce032fd5e67cf022da8564a5287b8cef6f`  
**Ruang lingkup:** Web Admin Madrasah W0–W10. Portal Guru/Siswa, Android, Publisher/Super Admin, licensing, VPS/deployment, dan Device Lease/Fencing tidak termasuk implementasi dokumen ini.

> Dokumen ini merangkum keputusan proyek yang telah disepakati dan menunjuk dokumen audit/keputusan rinci. Status implementasi harus selalu dibaca dari source GitHub dan bukti pengujian aktual, bukan dari rancangan ini.

---

## 0. Aturan membaca dokumen

Gunakan tiga kategori yang tidak boleh dicampur:

- **KEPUTUSAN NORMATIF:** ketentuan desain yang wajib dipatuhi implementasi.
- **KONDISI SOURCE:** keadaan yang benar-benar ditemukan pada branch/commit yang disebut.
- **STATUS PENGUJIAN:** hasil yang benar-benar dijalankan, dengan bukti dan batasnya.

Label **OPEN** berarti gap belum dibuktikan selesai. Label **PASS TERDOKUMENTASI** berarti ada bukti dari pengujian terdahulu pada commit dan lingkungan tertentu; bukan jaminan bahwa semua commit berikutnya otomatis lulus.

Jika dokumen historis bertentangan dengan keputusan proyek yang lebih baru, keputusan proyek terbaru mengendalikan desain. Jangan menghapus dokumen historis; beri catatan tanggal/status dan tautkan ke keputusan yang berlaku.

## 1. Sumber kebenaran dan kendali perubahan

Hierarki yang berlaku:

1. Keputusan proyek terbaru yang disepakati pengguna — acuan desain dan persyaratan.
2. GitHub working source yang ditetapkan — acuan kondisi source aktual.
3. Branch, commit, tag, PR — riwayat perubahan.
4. Laporan audit dan checkpoint — progres, temuan, serta handover.
5. Manifest, backup, dan snapshot GitHub Actions — bukti integritas dan pemulihan.

Repository aktif: `zane0184/cbt_november`.

Pada saat dokumen ini dibuat, referensi yang telah diperiksa:

| Fungsi | Branch | HEAD |
|---|---|---|
| Working branch Web Admin W0 | `work/web-admin-w0-20261121` | `2b4321ce032fd5e67cf022da8564a5287b8cef6f` |
| Dokumentasi persyaratan Kurmer-KBC | `docs/curriculum-kbc-w6-requirements-20261121` | `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4` |
| Source pin/recovery | `recovery/source-preservation-20261121` | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Branch dokumen induk ini | `docs/web-admin-master-spec-20261121` | Dibuat dari W0 HEAD di atas; periksa ref untuk HEAD terkini |

**Pembedaan penting:** recovery commit `7fc45bf...` adalah source pin yang menjadi dasar audit/backup. Branch `work/web-admin-w0-20261121` adalah working branch Web Admin yang memuat empat path dokumentasi W0 di atas source pin. Branch dokumentasi Kurmer-KBC terpisah dan divergen dari W0; jangan menganggap file pada branch itu sudah ada pada branch W0.

ZIP baseline 6 November adalah arsip historis saja. Jangan membuka, membandingkan, atau menjadikannya blocker. Jangan menurunkan atau mengganti source GitHub dengan arsip lama.

Setiap perubahan source harus:
- dilakukan pada branch kerja yang tepat;
- didahului backup file dan pencatatan path/hash;
- memiliki diff yang dapat ditinjau;
- diuji pada lingkungan disposable;
- memperbarui laporan dan checkpoint;
- tidak mengubah production DB/.env/VPS/deployment tanpa prosedur dan persetujuan eksplisit.

Tidak boleh melakukan merge, rebase, reset, force-push, penghapusan dokumen, migration produksi, atau deployment tanpa persetujuan yang sesuai.

## 2. Ruang lingkup dan arsitektur Web Admin

### 2.1 Stack yang disepakati

- Backend: Laravel 13, PHP 8.3.
- Web: Blade dan Livewire.
- API/session: Laravel Sanctum sesuai kebutuhan.
- Database: MySQL 8+ dengan InnoDB.
- Arsitektur: modular monolith.
- Storage: Laravel Storage dengan pemisahan penyimpanan instalasi.
- Redis bersifat opsional sesuai kebutuhan operasional.

Versi dependency aktual harus dibaca dari lockfile dan konfigurasi source; daftar stack ini adalah keputusan target, bukan pengganti pemeriksaan versi aktual.

### 2.2 Model instalasi dan isolasi

**Satu madrasah = satu instalasi mandiri = satu database operasional mandiri.**

Setiap instalasi mempunyai domain, database, APP_KEY, konfigurasi, storage, dan backup tersendiri. Tidak ada database akademik bersama untuk pertukaran data operasional lintas madrasah.

Tenant context, tenant_id, middleware, global scope, policy, audit, dan composite foreign key tetap digunakan sebagai defense-in-depth. Instalasi tunggal tidak membenarkan query tanpa batas. TenantContext kosong harus gagal tertutup (fail-closed). Seluruh jalur query User, relasi, export, cache, binding, dan penggunaan `withoutGlobalScopes` wajib diaudit.

Tidak boleh ada akses lintas instalasi/tenant kecuali mekanisme otorisasi yang secara eksplisit disetujui dan dibuktikan.

## 3. Role, portal, dan kewenangan

### 3.1 Role aktif

- `ADMIN_MADRASAH`: satu-satunya role Admin aktif.
- `GURU`: pemilik operasional CBT sesuai penugasan dan permission.
- `SISWA`: peserta ujian dan pembelajaran sesuai haknya.
- Kamad tetap terpisah sesuai desain produk.
- Proktor bukan role atau portal permanen; Proktor adalah penugasan operasional sementara kepada Guru aktif.
- Super Admin/Publisher berada di luar scope Web Admin saat ini dan ditunda sampai Juni 2027.

### 3.2 Admin Madrasah = Operator

Admin Madrasah dan Operator adalah satu dan sama: satu identitas pengguna, satu akun per orang, satu role aktif `ADMIN_MADRASAH`, satu portal, satu dashboard, satu sidebar, satu permission profile, dan satu kewenangan.

`OPERATOR` adalah role legacy yang harus direkonsiliasi/migrasi secara aman; bukan role aktif kedua dan bukan alias runtime. Jangan menggabungkan dua user berbeda atau menghilangkan ID, password hash, tenant, profil, dan histori. Alias role `ADMIN` juga harus ditangani secara terencana.

### 3.3 Kewenangan Guru

Guru mengelola operasional CBT sesuai assignment dan permission:
- bank soal dan media;
- pembuatan/pengaturan ujian;
- pemilihan mapel untuk ujian;
- jadwal, peserta, dan snapshot;
- penerbitan ujian dan token;
- pelaksanaan, jawaban, penilaian, hasil, analitik, dan laporan kelasnya.

Guru dapat menerbitkan ujian tanpa persetujuan Admin apabila permission dan penugasan mengizinkan.

### 3.4 Kewenangan Admin

Admin mengelola administrasi dan master data dalam lingkup kewenangannya. Untuk ujian Guru, Admin hanya boleh melihat metadata minimum dan mengelola penugasan Guru sebagai Proktor sementara.

Admin tidak boleh:
- membuat atau mengubah ujian Guru;
- mengubah mapel yang dipilih Guru untuk ujian;
- mengubah snapshot, token, peserta, jawaban, nilai, score, analisis, atau hasil operasional;
- membaca isi bank soal Guru, opsi, kunci, rubrik, atau pembahasan;
- melakukan monitoring operasional ujian;
- close, finalize, reopen, reset attempt, force submit, takeover perangkat, atau memperpanjang waktu;
- mengambil alih kewenangan Guru.

Jika kelak diperlukan agregat eksekutif untuk Admin, batas field dan tujuan harus ditetapkan tersendiri; jangan membuka detail jawaban/attempt/score.

### 3.5 Proktor sementara

Penugasan Proktor hanya dapat diberikan kepada Guru aktif dari madrasah/instalasi yang sama. Guru yang sama tidak boleh mengawasi lebih dari satu kelas dalam interval waktu yang tumpang tindih.

Interval bersifat setengah terbuka `[start,end)`. Konflik:
`existing.start < new.end AND existing.end > new.start`.

Penugasan harus memvalidasi tenant, status Guru, assignment, kelas/rombel, ujian, dan interval. Lock transaksi MySQL/InnoDB harus konsisten, termasuk mutex pada baris Guru dan query konflik. Status `NEEDS_REVIEW`, `REVOKED`, `EXPIRED`, dan `COMPLETED` tidak memberi capability. Waktu server adalah acuan. Perubahan jadwal ujian harus melalui satu service bersama, mencatat audit dan menandai assignment terdampak `NEEDS_REVIEW`; tidak boleh memindahkan jadwal Proktor secara diam-diam.

## 4. Portal dan area fungsional Admin

Web Admin mengelola:
- dashboard administratif;
- identitas pengguna, status akun, dan penugasan Guru/Siswa;
- tahun/periode akademik;
- kelas, fase, rombel, penempatan siswa, promosi, tinggal kelas, kelulusan, alumni dan histori;
- master mata pelajaran, kurikulum, CP/TP/ATP dan referensi karakter;
- penugasan Guru sebagai Proktor sementara;
- metadata minimum ujian;
- audit log, health, backup/restore, profil dan preferensi sesuai tahap.

Menu dan route harus mencerminkan satu portal Admin. Tidak boleh ada portal Operator atau Proktor permanen.

## 5. Master akademik dan Kurikulum Merdeka + KBC

### 5.1 Kedudukan Kurmer dan KBC

Kurikulum Merdeka adalah kerangka akademik/pembelajaran: mapel, fase, CP, TP, ATP, perencanaan, pembelajaran, asesmen, dan hasil belajar.

Kurikulum Berbasis Cinta (KBC) adalah ruh, landasan nilai, dan penguatan karakter yang diintegrasikan ke dalam Kurmer. **Keduanya satu kesatuan; tidak ada pilihan “Kurmer ATAU KBC”.** KBC bukan kurikulum alternatif dan tidak membentuk struktur mapel, fase, CP, TP, ATP, atau kode RDM tandingan.

Pembelajaran Mendalam dan 8 Dimensi Profil Lulusan terintegrasi ke perencanaan, pembelajaran, asesmen, dan evaluasi.

Rujukan kebijakan utama yang dicatat dokumen W6:
- KMA Nomor 1503 Tahun 2025.
- Kepdirjen Pendidikan Islam Nomor 6077 Tahun 2025 tentang Panduan Kurikulum Berbasis Cinta.

Verifikasi akses dokumen sumber dan nomenklatur harus dipelihara. Jangan mengarang CP/TP/ATP, kode RDM, deskripsi resmi, atau tanggal berlaku.

### 5.2 Fase MI

- Fase A: kelas 1–2.
- Fase B: kelas 3–4.
- Fase C: kelas 5–6.

### 5.3 Master mata pelajaran

Mendukung:
- mata pelajaran umum MI;
- rumpun PAI: Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab;
- Mulok.

Kode RDM hanya boleh berstatus VERIFIED setelah dibandingkan dengan referensi RDM resmi yang relevan untuk MI dan versi berlaku. Jika belum dapat diverifikasi, statusnya PENDING_VERIFICATION; jangan tampilkan sebagai kode resmi.

### 5.4 Master CP, TP, ATP

- CP terikat pada kurikulum, fase, dan mapel yang valid.
- TP terikat pada CP induk yang valid.
- ATP menyusun TP yang konsisten dengan mapel/fase/kurikulum.
- Setiap referensi menyimpan provenance: penerbit, nomor/nama dokumen, sumber/URL/arsip, versi, tahun/tanggal berlaku, status, aktor dan histori perubahan.
- Guru memilih referensi melalui dropdown bertingkat mapel → fase → CP → TP; Guru tidak mengetik ulang atau memutasi master.
- Perubahan master tidak boleh merusak histori soal, asesmen, atau laporan. Gunakan versioning/snapshot dan deactivation, bukan hard delete.

### 5.5 Panca Cinta

Gunakan lima nomenklatur:
1. Cinta Allah dan Rasul-Nya.
2. Cinta Ilmu.
3. Cinta Lingkungan.
4. Cinta Diri dan Sesama Manusia.
5. Cinta Tanah Air.

Panca Cinta berlaku lintas mata pelajaran, bukan hanya mapel agama.

### 5.6 Delapan Dimensi Profil Lulusan (DPL)

Gunakan nomenklatur:
1. Keimanan dan ketakwaan kepada Tuhan YME.
2. Kewargaan.
3. Penalaran kritis.
4. Kreativitas.
5. Kolaborasi.
6. Kemandirian.
7. Kesehatan.
8. Komunikasi.

Panca Cinta dan DPL menjadi referensi untuk pembelajaran, label soal/aktivitas, asesmen, survei karakter, dan laporan sesuai desain akademik. Simpan sumber, versi, status, dan histori.

### 5.7 Hak akses dan lifecycle master

- Admin Madrasah menambah dan mengubah master.
- Guru hanya membaca/memilih referensi.
- Guru tidak memutasi master.
- Tidak ada hard delete master yang sudah tersimpan/digunakan.
- Referensi usang dinonaktifkan/obsolete dengan alasan dan histori.
- Perubahan substantif menghasilkan versi baru atau histori yang mempertahankan snapshot lama.
- Cascade delete tidak boleh menghilangkan histori CP/TP/ATP, mapping soal, asesmen, atau laporan.
- Setiap perubahan menyimpan actor, tenant, waktu server, before/after, alasan, dan correlation/request ID bila tersedia.

## 6. Integrasi akademik dan CBT

Integrasi Kurmer+KBC tidak boleh berhenti pada halaman Bank Soal. Harus konsisten pada:
- data akademik dan identitas kelas/fase/mapel;
- perencanaan dan pembelajaran;
- Bank Soal;
- asesmen dan ujian;
- hasil belajar;
- Panca Cinta dan DPL;
- survei karakter;
- laporan akademik dan evaluasi.

Bank Soal Guru harus memvalidasi semua referensi, versi, tenant, mapel, fase, CP/TP, Panca Cinta, dan DPL. Simpan ID referensi serta snapshot label/versi agar histori tetap terbaca. Jangan membuat mapping palsu untuk data lama yang belum memiliki mapping.

Audit statis awal menemukan fondasi legacy `subjects`, `curricula`, `phases`, `learning_outcomes`, `learning_objectives`, `learning_paths` dan versi/association; juga struktur `multi_curriculums`, `multi_learning_outcomes`, dan `question_multi_curriculum_mappings`. Namun KBC masih direpresentasikan sebagai kode sejajar `MERDEKA` pada `multi_curriculums`; hubungan legacy CP/TP dengan mapping soal belum terbukti konsisten. Jangan menghapus atau merekonstruksi tabel secara asumtif. Audit data/provenance/migration/model/service/policy/API/Livewire/view/seeder/test dahulu.

## 7. Prinsip keamanan dan integritas CBT

- Validasi role, permission, tenant, owner, assignment, dan lingkup data pada backend untuk setiap route/API/Livewire action.
- UI hiding bukan authorization.
- Waktu server adalah sumber waktu resmi.
- Attempt, Answer, Score, Device Lease, dan fencing harus konsisten.
- Idempotensi dan transaksi mencegah duplikasi/race condition.
- Offline dan pemulihan sinkronisasi harus aman pada fase Android berikutnya.
- Kamera bukan syarat CBT standar.
- Reset Attempt, Force Submit, takeover perangkat, dan perpanjangan waktu memerlukan otorisasi serta audit log.
- Device Lease/Fencing tetap HOLD dan feature flag OFF sampai uji integrasi, concurrency, multi-device, offline, dan recovery berhasil.
- Tenant isolation harus diuji negatif pada query, relasi, binding, export, cache, serta akses URL/API langsung.
- Audit log failure pada operasi kritis harus menggagalkan transaksi, bukan membiarkan perubahan tanpa jejak.

## 8. Tahapan Web Admin W0–W10

| Tahap | Sasaran | Gate utama |
|---|---|---|
| W0 | Source pin, backup, manifest, audit route/action-permission | HEAD/source pin jelas, manifest/restore terbukti, temuan dan provenance tercatat, pengguna menilai penutupan |
| W1 | Konsolidasi role dan tenant guards | Satu ADMIN_MADRASAH, migrasi legacy aman, tidak ada role aktif OPERATOR/PROKTOR, uji tenant fail-closed |
| W2 | Dashboard, shell, sidebar, routes | Satu portal/dashboard/sidebar Admin |
| W3 | Akademik, periode, kelas, fase, rombel, mapel | Data induk dan relasi tenant konsisten; histori utuh |
| W4 | Data Guru/Siswa, akun, status, assignment | Lifecycle aman dan tanpa duplikasi identitas |
| W5 | Penempatan, promosi, tinggal kelas, kelulusan, alumni | Transaksi atomik dan histori akademik utuh |
| W6 | Master Kurmer-KBC, CP/TP/ATP, Mulok, Panca Cinta, DPL | Sumber resmi/versioning/provenance dan acceptance test W6 terbukti |
| W7 | Metadata ujian Admin dan assignment Guru-Proktor | Admin metadata-only; konflik/reschedule/expiry/revoke aman |
| W8 | Importer dan laporan administratif | Preview, validasi, idempotensi, rollback/retry, tidak bocor hasil ujian |
| W9 | Audit log, health, backup/restore, profil/preferensi | Audit lengkap, checksum, restore disposable |
| W10 | Integrasi, security, browser, performance, UAT | Acceptance test lulus, bukti tersedia, persetujuan pengguna |

Persetujuan pembuatan dokumen ini **bukan** persetujuan otomatis untuk memulai W1/W6, merge, migration, atau deployment. Urutan dan gate harus dipatuhi.

## 9. Matriks status awal

| Area | Status saat dokumen dibuat | Catatan |
|---|---|---|
| Git refs W0/docs/recovery | PASS pada pemeriksaan 21-11-2026 | Periksa ulang sebelum pekerjaan berikutnya |
| Recovery → W0 | PASS pada compare; 18 ahead, 0 behind | Empat path tambahan; tidak ada source app berubah |
| Snapshot/restore 531/531 | PASS terdokumentasi | Run sebelumnya; bukan tes baru dokumen ini |
| Konsolidasi Admin/Operator | OPEN | Role/permission legacy masih terpisah |
| Alias ADMIN | OPEN | Ditemukan di resolver/komponen |
| Role/portal Proktor permanen | OPEN | Perlu assignment Guru sementara |
| Batas akses Bank Soal Admin | OPEN | Admin masih dapat membaca konten |
| Batas akses exam/answer/result Admin | OPEN | Close/monitoring/detail attempt/score/finalize/reopen perlu dibatasi |
| Konflik jadwal Proktor | OPEN | Overlap lintas assignment belum terbukti dicegah |
| Review assignment saat reschedule | OPEN | Shared schedule service belum ditemukan |
| Tenant isolation seluruh jalur | OPEN | User query/relasi/withoutGlobalScopes perlu audit dan tes |
| Integritas multi_curriculums/mapping | OPEN | KBC sebagai peer curriculum dan cascade/mapping perlu rekonsiliasi |
| Master Panca Cinta/DPL resmi di source | NOT PROVEN | Audit source awal belum membuktikan katalog terimplementasi |
| Kode RDM | NOT VERIFIED | Jangan menebak |
| CP/TP/ATP resmi | NOT SEEDED/NOT VERIFIED | Import hanya dari sumber resmi terverifikasi |
| W0 formal closure | MENUNGGU PENILAIAN PENGGUNA | Belum dinyatakan selesai |
| W1 | NOT APPROVED / NOT STARTED | Tidak boleh dimulai tanpa persetujuan |
| W6 | NOT STARTED | Acceptance criteria ada di dokumen sumber |

## 10. Acceptance dan pengujian

Setiap tahap memiliki matriks acceptance terpisah. Minimal:
- role × route × action matrix;
- direct URL/API dan Livewire action tests;
- negative cross-tenant tests;
- MySQL 8+ InnoDB disposable integration tests;
- race/concurrency tests untuk assignment dan operasi ujian;
- regression tests untuk histori akademik/soal/attempt;
- browser E2E dan UAT;
- bukti log, commit, environment, serta hasil test yang dapat diulang.

SQLite/unit tests saja tidak cukup untuk membuktikan composite FK, locking, isolation, dan perilaku MySQL. Jangan menguji migration pada DB produksi.

Status PASS hanya untuk tes yang benar-benar dijalankan dan memiliki bukti. Catat FAIL, PARTIAL, NOT RUN, dan OPEN apa adanya.

## 11. Batas platform dan jadwal

- Fokus saat ini Web Admin dan backend sesuai tahap yang disetujui.
- Portal Guru dan Siswa mengikuti kontrak akademik/role yang sama pada tahap berikutnya.
- Android native untuk peserta Android tetap keputusan produk, tetapi pekerjaan Android ditunda sampai Juni 2027.
- Saat Android dilanjutkan, build wajib melalui Android Studio; jangan menjalankan Gradle via terminal.
- iOS sementara melalui Web.
- Super Admin/Publisher, licensing, dan distribusi final berada di tahap mendatang.
- VPS/aaPanel/Nginx, production migration, dan deployment tidak termasuk dokumen/branch dokumentasi ini.

## 12. Dokumen sumber dan referensi

### Dokumen W0 — branch `work/web-admin-w0-20261121`

- [Laporan Audit W0](https://github.com/zane0184/cbt_november/blob/work/web-admin-w0-20261121/docs/LAPORAN_AUDIT_W0_WEB_ADMIN_2026-11-21.md)
- [Checkpoint W0](https://github.com/zane0184/cbt_november/blob/work/web-admin-w0-20261121/docs/CHECKPOINT_W0_WEB_ADMIN_2026-11-21.md)
- [Manifest source tree](https://github.com/zane0184/cbt_november/blob/work/web-admin-w0-20261121/docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv)
- [Workflow snapshot/restore](https://github.com/zane0184/cbt_november/blob/work/web-admin-w0-20261121/.github/workflows/w0-source-snapshot-verify.yml)

### Dokumen Kurmer-KBC — branch `docs/curriculum-kbc-w6-requirements-20261121`, dipatok pada HEAD `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4`

- [Addendum Spesifikasi Master Kurmer-KBC](https://github.com/zane0184/cbt_november/blob/4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4/docs/SUPPLEMENTAL_SPEC_MASTER_KURIKULUM_KURMER_KBC_W6.md)
- [Register Keputusan Kurikulum](https://github.com/zane0184/cbt_november/blob/4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4/docs/REGISTER_KEPUTUSAN_WEB_ADMIN_KURIKULUM.md)
- [Matriks Audit dan Pengujian W6](https://github.com/zane0184/cbt_november/blob/4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4/docs/MATRIKS_AUDIT_DAN_PENGUJIAN_MASTER_KURIKULUM_W6.md)
- [Checkpoint Kurmer-KBC W6](https://github.com/zane0184/cbt_november/blob/4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4/docs/CHECKPOINT_KURIKULUM_KURMER_KBC_W6_2026-11-21.md)

### Dokumen pendukung

Dokumen historis P0, handover, backup, dan README tetap dipertahankan pada source pin/branch turunan. Dokumen tersebut adalah bukti sejarah/provenance, bukan otoritas untuk membatalkan keputusan proyek terbaru. Inventaris lengkapnya dicatat pada dokumen rekonsiliasi W0.

## 13. Change control

Setiap perubahan terhadap spesifikasi induk harus mencantumkan:
- ID keputusan/requirement yang berubah;
- alasan dan persetujuan;
- dampak pada role, data, tenant, API, UI, migration, dan histori;
- dokumen terkait yang perlu diperbarui;
- acceptance criteria dan test baru;
- versi, tanggal, author, commit, serta parent.

Jangan mengedit diam-diam keputusan lama. Gunakan addendum/riwayat perubahan. Bila keputusan baru bertentangan dengan dokumen lama, tandai konflik dan minta keputusan; jangan menebak.

---

**Status dokumen:** dibuat sebagai entry point spesifikasi Web Admin. Tidak mengubah source aplikasi, database, migration, deployment, atau status OPEN pada temuan keamanan. W0 tetap menunggu penilaian formal; W1 dan W6 belum dimulai.

# CHECKPOINT P0-02 — Audit Akademik & CP Provenance
Tanggal: 2026-11-25

## 1. Identitas dan batas audit
- Repository: `zane0184/cbt_november`
- Branch: `work/limited-zip-p0-20261125`
- Exact HEAD yang diaudit: `150b5ffec865230038aae37e0c9a1c94f151ed8d`
- P0-01: PASS pada exact commit `150b5ffec865230038aae37e0c9a1c94f151ed8d`, Run #114.
- `main` tidak diubah; tidak ada PR merge; tidak ada DB produksi, migration produksi, atau VPS disentuh.
- Audit ini read-only. Tidak ada source code atau migration yang diubah dan tidak ada test yang dijalankan pada tahap ini.

## 2. AUDIT — provisioning dan alur administrasi
### Provisioning dasar dipertahankan
`TenantAcademicBootstrapper` telah menyediakan Kurikulum Merdeka, Fase A/B/C, kelas MI 1–6, tahun pelajaran berjalan, dua semester, dan satu semester aktif. Installer E2E P0-01 memeriksa jumlah tahun, semester, fase, dan kelas. Tidak dibuat ulang dan tidak membuat CP.

### Implementasi source yang ditemukan
- Guru/Siswa: `UserManagement::createUser()`; role dan permission `user.manage`; tenant-scoped identifier, profil, transaksi, audit.
- Rombel: `AcademicManagement::createRombel()`; permission `academic.manage`; referensi tahun dan kelas divalidasi untuk tenant.
- Penempatan siswa: `StudentRombelManagement::addStudentToRombel()`; validasi tenant, role SISWA, Rombel aktif, transaksi, lock, pivot, audit. Migration `2026_11_09_230003_harden_rombel_student_tenant_integrity.php` menambah composite foreign keys.
- Mapel: `AcademicManagement::createSubject()`; permission `academic.manage`; model `Subject` tenant-scoped.
- Registrasi versi dan mapping mapel: `CurriculumRegistryManagement`; permission `curriculum.registry.manage`; versi global System Tenant dan data lokal tenant dipisahkan.
- Penugasan Guru: `TeacherSubjectAssignmentManagement::createAssignment()`; permission `academic.manage`; validasi akun Guru, mapel, rombel, semester, registrasi, fase/kelas, node mapel, transaksi, snapshot konteks, audit.
- TP/ATP: `TeacherLearningOutcomeService`; permission `curriculum.manage`; terikat pada assignment/registrasi/versi. CP harus berstatus `ARTIFACT_CHECKSUM_VERIFIED` dan checksum sumber harus sama dengan checksum CP.
- Bank Soal: `QuestionBank`; Gate, role, assignment, tenant dan validasi CP/TP; publish mengharuskan CP checksum verified serta TP published/current.
- Exam Builder: `ExamManagement` dan `ExamPolicy`; role/permission Guru, assignment dan tenant. Token dibuat setelah publikasi/penguncian. Snapshot memeriksa checksum CP.

### Gap penerimaan
Feature tests membuktikan beberapa operasi Livewire/API secara terpisah, tetapi tidak ada satu E2E yang menjalankan seluruh delapan tahap Admin secara berurutan melalui UI aktual. Belum ditemukan test khusus `CurriculumRegistryManagement` atau `TeacherSubjectAssignmentManagement` untuk aksi register/map/create assignment. Test roster yang ada menggunakan fixture langsung untuk menyiapkan akun, profil, dan rombel. Ini adalah gap coverage, bukan bukti bahwa fitur source tidak ada.

## 3. AUDIT — CP provenance
- Registry `CurriculumReferenceSourceSeeder` mempertahankan Kepdirjen Pendis 9941 Tahun 2025 dalam status `PENDING`, tanpa checksum; belum ada CP resmi di registry System Tenant.
- BSKAP 046/H/KR/2025: PDF ada pada domain resmi Kemendikdasmen dan menetapkan CP umum SD/MI untuk Fase A (I–II), B (III–IV), C (V–VI). File bytes belum diarsipkan di branch/workspace dan SHA-256 belum dihitung.
- Kepdirjen Pendis 9941/2025: ditemukan salinan PDF 141 halaman pada situs MIN 2 SBB, termasuk surat pengantar Kemenag B-606 tanggal 1 Desember 2025 dan lampiran. Cakupannya menyebut RA/MI/MTs/MA/MAK; lampiran berisi CP PAI dan Bahasa Arab. Karena host merupakan situs madrasah pihak ketiga dan byte asli/tanda tangan digital belum dapat diverifikasi secara independen, dokumen ini hanya artefak kandidat, bukan sumber terverifikasi untuk impor.
- BKPDM 020 Tahun 2026: siaran pers resmi Kemendikdasmen tanggal 23 Juni 2026 menyatakan perubahan CP terbatas pada Agama dan Budi Pekerti. PDF yang ditemukan merupakan siaran pers dua halaman, bukan naskah keputusan dan lampiran lengkap.
- Pemeriksaan recursive Git tree pada branch (1.695 entri) dan main (43 entri) tidak menemukan PDF/DOC/DOCX/XLS/XLSX/ZIP/7z/RAR. Library diperiksa melalui daftar 377 item dalam empat halaman; beberapa arsip handover/arsip reassembled ditemukan, tetapi materialisasi raw bytes ditolak sistem Library. Isi ZIP bersarang karena itu belum dapat diperiksa.
- Tidak ada status `ARTIFACT_CHECKSUM_VERIFIED`, checksum sumber, checksum CP, ataupun status `PENDING` yang diubah.

## 4. DESAIN — gap minimum dan prioritas
### P0
1. Pertahankan fail-closed CP provenance; jangan membuat konten CP pengganti.
2. Siapkan E2E non-CP dengan disposable MySQL: Admin membuat Guru/Siswa, rombel, placement siswa, mapel, registrasi metadata kurikulum, subject mapping, penugasan Guru; verifikasi tenant, role/permission, audit, transaksi, idempotensi.
3. Siapkan negative acceptance test: 9941 tetap PENDING, opsi CP tidak selectable, create TP dan publish TP/ATP ditolak, tidak meninggalkan row parsial.
4. Tambahkan test terfokus untuk register version, mapSubject, dan createAssignment, termasuk tenant mismatch, permission denied, rollback, idempotensi dan audit.

### P1
1. E2E dengan CP resmi baru setelah artefak asli 9941/046/020 yang relevan diarsipkan, SHA-256 dan provenance diverifikasi.
2. Uji browser penuh dengan aplikasi Laravel dan DB disposable (bukan hanya runner JS dengan API mock).
3. Uji transfer/riwayat Rombel dan aturan satu keanggotaan aktif bila aturan produk tersebut disahkan.
4. Uji konflik penugasan Guru yang tanggalnya tumpang tindih serta konkurensi pada MySQL.

### P2
- Penguatan observabilitas/administrasi arsip sumber, rekonsiliasi metadata lintas versi, dan penyempurnaan pelaporan akademik setelah sumber CP sah tersedia.

## 5. PENGUJIAN — bukti dan batas
Tidak ada pengujian baru yang dijalankan pada audit ini. Pemeriksaan source bukan PASS test.
- Unit test: belum dijalankan pada P0-02.
- Feature test: source test yang ditemukan ada untuk Admin UI, Rombel/student pivot, Question Bank, Exam Builder, Student Portal, Exam Answer, dan curriculum metadata. Banyak skenario CP positif memakai `CreatesCanonicalCurriculumContext`, yang sengaja membuat CP sintetis bertanda verified untuk PHPUnit; ini bukan bukti CP resmi.
- Installer E2E: Run #114 P0-01 PASS, tetapi hanya membuktikan instalasi dan provisioning dasar, bukan alur Admin→Guru→Siswa.
- Browser E2E: `runner-browser-e2e.mjs` menggunakan Playwright dengan mock API/local fixture untuk menguji runner, bukan backend Laravel end-to-end.
- Uji operasional madrasah: belum tersedia bukti.

## 6. BLOCKER
1. Byte asli SK Dirjen Pendis 9941/2025 dan seluruh lampiran CP MI belum dapat diarsipkan dan diverifikasi tanda tangan/provenance/checksum.
2. Naskah keputusan BKPDM 020/2026 beserta lampiran lengkap belum tersedia pada sumber yang berhasil dibaca.
3. PDF asli BSKAP 046/H/KR/2025 belum tersimpan/di-hash dalam artefak project walaupun official URL ditemukan.
4. Isi arsip Library yang menolak raw materialization belum dapat diperiksa secara recursive.
5. Tanpa CP resmi yang lolos gate, TP/ATP resmi, publikasi soal yang terhubung CP/TP dan ujian resmi berbasis CP tetap BLOCKED. Alur CBT yang tidak membutuhkan CP tetap dapat diuji.

## 7. CHECKPOINT / NEXT
- Status P0-02: AUDIT READ-ONLY SELESAI UNTUK SOURCE YANG DAPAT DIAKSES; IMPLEMENTASI BELUM DIMULAI.
- Branch/HEAD tetap sebagaimana identitas di atas.
- Source changes: none.
- Backup: none required (no source edit).
- Tests run in this stage: none.
- Next: run non-CP academic feature/E2E matrix on disposable MySQL; obtain and verify original CP artifacts; only then run full CP→TP→ATP→question→exam→attempt→answer→submit→score E2E.

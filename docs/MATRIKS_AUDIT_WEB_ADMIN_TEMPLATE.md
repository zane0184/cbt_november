# MATRIKS AUDIT WEB ADMIN — TEMPLATE WAJIB

Gunakan salinan tabel/format ini untuk setiap modul. Jangan mengisi status berdasarkan nama route atau keberadaan file saja.

## 1. Klasifikasi status

- **SUDAH ADA DAN BERFUNGSI:** UI + backend + authorization + validasi + tenant guard + persistensi + audit + test relevan terbukti.
- **ADA TAPI BELUM LENGKAP**
- **BACKEND ONLY**
- **UI ONLY**
- **BELUM ADA**
- **BERMASALAH/BERISIKO**
- **BELUM TERVERIFIKASI**

## 2. Bukti per fitur

| ID | Modul/fitur | Status | Route/URL | UI file/component | Action/Livewire method | Controller/service | Model/tabel/query | Permission/policy | Tenant guard | Validation | Transaction/idempotency | Audit log | Test file/case | Test aktual (PASS/FAIL/BLOCKED/NOT RUN) | Bukti commit/path/line | Gap/risiko | Rekomendasi |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| ADM-001 | Dashboard | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-002 | Identitas Madrasah | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-003 | Tahun ajaran/semester | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-004 | Kelas/fase/rombel/wali kelas | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-005 | Data Guru administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-006 | Penugasan Guru | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-007 | Data Siswa administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-008 | Penempatan/promosi/tinggal kelas | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-009 | Kelulusan kelas 6/alumni/histori | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-010 | Master mapel MI | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-011 | Mulok | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-012 | Kurmer/KBC/versioning | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-013 | CP/TP/ATP | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-014 | Panca Cinta/Pembelajaran Mendalam/DPL | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-015 | Impor Excel Siswa | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-016 | Impor Excel Guru | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-017 | Impor Excel Mata Pelajaran | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-018 | Laporan administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-019 | Backup/restore | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-020 | Profil/password/preferensi | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-021 | Audit log/kesehatan sistem | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-022 | Sidebar/topbar/responsive shell | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-023 | Daftar Ujian & Penugasan Proktor | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |

## 3. Matriks authorization

Buat satu baris untuk setiap kombinasi role × route/action. Jangan mengasumsikan label UI sama dengan permission backend.

| Role | Route/action | Allowed? | Permission/policy | Tenant binding | Assignment/scope | Negative test | Evidence |
|---|---|---|---|---|---|---|---|
| Super Admin/publisher | Akses operasional tenant | Belum ditentukan pada scope ini | | | | | |
| Admin Madrasah/Operator | Modul administratif | Harus sesuai permission | | | | | |
| Admin Madrasah/Operator | Authoring soal/ujian Guru | **Dilarang** | | | | | |
| Admin Madrasah/Operator | List ujian Guru read-only | Diizinkan sesuai scope | | | | | |
| Admin Madrasah/Operator | Assign Guru pengawas | Diizinkan sesuai scope | | | | | |
| Guru | Portal Guru | Di luar scope sekarang | | | | | |
| Siswa | Portal Siswa | Di luar scope sekarang | | | | | |
| Proktor sementara | Akses hanya dari assignment aktif | Desain integrasi; bukan portal permanen | | | | | |

## 4. Jalur penelusuran wajib

Untuk tiap fitur telusuri dan catat:

1. Menu/navigation → route.
2. Middleware/authentication.
3. Livewire component/controller dan action.
4. Policy/gate/permission.
5. Validasi request/form.
6. TenantContext dan query scoping.
7. Service/domain logic.
8. Transaction, lock, idempotency, uniqueness.
9. Model/DB schema/persistence.
10. Audit log/event.
11. Response/UI state.
12. Unit/feature/security/regression test.
13. Bukti test aktual: command, exit code, runtime, commit.

## 5. Audit lintas modul

- [ ] Role Admin = Operator satu role; tidak ada duplikasi portal.
- [ ] Tidak ada link Admin yang membuka authoring Guru.
- [ ] Tidak ada route Admin yang dapat mengubah soal/jadwal akademik ujian Guru.
- [ ] Daftar ujian Admin membaca record ujian Guru yang sama; bukan tabel kalender duplikat.
- [ ] Penetapan Proktor hanya Guru aktif tenant yang sama.
- [ ] Konflik jadwal ditangani secara atomik.
- [ ] Perubahan jadwal oleh Guru menandai assignment terdampak perlu ditinjau.
- [ ] Riwayat perubahan assignment tercatat.
- [ ] Data siswa/Guru dibatasi tenant dan permission.
- [ ] Import tidak membuat duplikasi; preview dan commit dipisahkan.
- [ ] Promosi/kelulusan tidak menghapus histori.
- [ ] Seed kurikulum idempotent dan tidak menimpa custom lokal.
- [ ] Ekspor/download memeriksa permission dan scope.
- [ ] Tidak ada kebocoran PII pada dashboard/log/error.

## 6. Prioritas risiko

- **P0:** kebocoran lintas tenant/PII, privilege escalation, kehilangan/korupsi data, destructive migration, bypass auth.
- **P1:** alur akademik salah, import duplikat, histori hilang, konflik assignment, audit tidak lengkap.
- **P2:** UX, performa, aksesibilitas, inkonsistensi visual yang tidak memblokir integritas.

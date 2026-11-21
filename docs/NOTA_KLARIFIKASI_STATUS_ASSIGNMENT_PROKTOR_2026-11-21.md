# KEPUTUSAN FINAL — STATUS ASSIGNMENT PROKTOR
Tanggal keputusan: 21 November 2026
Status: ACTIVE — keputusan eksplisit pengguna; final, tidak menunggu klarifikasi.

## 1. Status assignment kanonis
Status assignment yang diperbolehkan:
- `PENDING`
- `CONFIRMED`
- `NEEDS_REVIEW`
- `REVOKED`
- `EXPIRED`
- `COMPLETED`

Status `ACTIVE` **bukan** status assignment dan tidak boleh ditambahkan sebagai enum/status pengganti atau status capability.

## 2. Matriks capability final

| Status | Memberikan capability Proktor? | Ketentuan |
|---|---|---|
| `PENDING` | Tidak | Menunggu konfirmasi; seluruh request Proktor ditolak |
| `CONFIRMED` | Ya, bersyarat | Hanya setelah semua validasi di bagian 3 lulus dan waktu berada dalam interval |
| `NEEDS_REVIEW` | Tidak | Capability assignment lama langsung tidak berlaku |
| `REVOKED` | Tidak | Dicabut; request berikutnya langsung ditolak |
| `EXPIRED` | Tidak | Kedaluwarsa; request ditolak berdasarkan waktu server |
| `COMPLETED` | Tidak | Tugas selesai; tidak lagi memberi capability |

Hanya `CONFIRMED` merupakan status assignment yang dapat memberikan capability. Status akun Guru `ACTIVE` tetap boleh digunakan dalam konteks akun, tetapi bukan status assignment Proktor.

## 3. Validasi wajib setiap request
Assignment `CONFIRMED` hanya memberikan capability jika seluruh kondisi berikut terpenuhi:
1. User adalah Guru yang masih aktif.
2. TeacherProfile valid dan aktif.
3. Tenant Guru, assignment, ujian, dan rombel sesuai.
4. Assignment mengacu pada ujian dan rombel yang benar.
5. Schedule revision assignment sama dengan revision jadwal ujian terkini.
6. Assignment belum dicabut dan tidak berada dalam kondisi konflik yang melarang.
7. Permission dan lingkup penugasan sah.
8. Waktu server memenuhi interval setengah terbuka:

`starts_at <= serverNow < expires_at`

Pemeriksaan dilakukan pada setiap request yang menggunakan capability. Jangan bergantung pada cron/scheduler untuk menentukan boleh atau tidaknya akses. Pada saat `serverNow >= expires_at`, akses harus ditolak walaupun label status database belum diperbarui menjadi `EXPIRED`.

## 4. Reschedule
Perubahan jadwal hanya dilakukan Guru melalui `ExamScheduleChangeService`. Perubahan yang berdampak pada assignment membuat assignment terkait menjadi `NEEDS_REVIEW` secara atomik dan langsung mencabut capability lama. Admin hanya meninjau dan mengonfirmasi ulang assignment setelah semua validasi konflik/revision terpenuhi. Setelah konfirmasi ulang, status kanonisnya adalah `CONFIRMED`, bukan `ACTIVE`.

## 5. Overlap dan concurrency
- Interval setengah terbuka `[starts_at, expires_at)`.
- Predikat overlap: `existing.starts_at < new.expires_at AND existing.expires_at > new.starts_at`.
- Interval bersebelahan diterima; interval yang bertumpang tindih ditolak.
- Pemeriksaan lintas seluruh assignment Guru, ujian, dan rombel.
- Lock order MySQL 8+ InnoDB konsisten: Exam → Guru row sebagai mutex → assignment terkait dengan locking/current read → validasi → simpan assignment/event/audit dalam satu transaksi.
- Kegagalan audit menggagalkan transaksi.

## 6. Status implementasi dan pengujian
Keputusan desain di atas ACTIVE. Implementasi source masih OPEN sampai diubah dengan persetujuan terpisah. Test T-001–T-168 tetap memiliki status eksekusi sebelumnya; test baru untuk larangan status ACTIVE dan validasi capability CONFIRMED ditambahkan setelah T-168. Semua test yang belum dijalankan tetap NOT RUN.

## 7. Riwayat klarifikasi
Dokumen ini menggantikan status OPEN dan rekomendasi PROPOSED pada versi nota sebelumnya. Tidak ada pertanyaan keputusan yang tersisa untuk semantik status assignment Proktor. Addendum 34 pada spesifikasi rinci ditandai SUPERSEDED oleh Addendum 35.
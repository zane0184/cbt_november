# NOTA KLARIFIKASI DESAIN — STATUS ASSIGNMENT PROKTOR
Tanggal: 21 November 2026
Status: OPEN — memerlukan penegasan pengguna sebelum implementasi status/capability.

## 1. Yang sudah diputuskan pengguna
Pengguna menetapkan status assignment minimum:
- `PENDING`
- `CONFIRMED`
- `NEEDS_REVIEW`
- `REVOKED`
- `EXPIRED`
- `COMPLETED`

Pengguna secara eksplisit menetapkan status yang tidak memberikan hak Proktor:
- `NEEDS_REVIEW`
- `REVOKED`
- `EXPIRED`
- `COMPLETED`

Pengguna juga menetapkan hak Proktor aktif hanya bila assignment valid dan waktu server memenuhi:
`starts_at <= serverNow < expires_at`.

## 2. Hal yang belum dinyatakan secara eksplisit
Instruksi pengguna belum secara eksplisit menjelaskan:
1. Apakah `PENDING` memberikan capability atau tidak.
2. Apakah `CONFIRMED` adalah satu-satunya status yang dapat memberikan capability.
3. Apakah `ACTIVE` tetap diperlukan sebagai status tambahan, atau digantikan oleh `CONFIRMED`.
4. Transisi status yang tepat: PENDING → CONFIRMED → COMPLETED/EXPIRED/REVOKED, serta kondisi masuk NEEDS_REVIEW.

Dokumen terdahulu yang menyatakan “hanya CONFIRMED memberi capability” dan “PENDING tidak memberi capability” harus dipahami sebagai rekomendasi fail-closed, bukan keputusan eksplisit pengguna. Jangan mengimplementasikannya sebagai keputusan final tanpa konfirmasi.

## 3. Rekomendasi keamanan sementara
Sampai ada penegasan, rekomendasi paling aman adalah fail-closed: `PENDING` tidak memberi capability; capability baru aktif setelah assignment dikonfirmasi dan semua validasi tenant/Guru/exam/rombel/revision/waktu terpenuhi. Namun rekomendasi ini tetap berstatus PROPOSED, bukan ACTIVE.

Status `ACTIVE` pada source/dokumen historis tidak boleh otomatis dipertahankan atau dihapus. Petakan transisinya setelah status kanonis disetujui.

## 4. Dampak
- Jangan membuat migration enum/status.
- Jangan mengubah model/service/policy/middleware/Livewire/API.
- Jangan menulis test sebagai acceptance final untuk status PENDING/CONFIRMED sebelum keputusan status dikunci.
- Test T-159 dan T-160 pada matriks pengujian adalah test usulan berbasis rekomendasi fail-closed; tandai PENDING DECISION sampai pengguna menegaskan semantik.
- Semua status lain yang secara eksplisit dinyatakan tidak memberi hak tetap ditolak.

## 5. Pertanyaan keputusan yang spesifik
Apakah Anda menetapkan `PENDING` tidak memberi hak Proktor dan hanya `CONFIRMED` yang dapat memberi capability (dengan syarat tenant, Guru aktif, exam/rombel, revision, dan interval waktu valid)? Atau ada status lain, misalnya `ACTIVE`, yang dimaksud sebagai status pemberi hak?
